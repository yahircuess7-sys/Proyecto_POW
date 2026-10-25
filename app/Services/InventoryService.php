<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    /**
     * Registra el stock inicial al dar de alta un producto.
     */
    public function recordInitialStock(Product $product, int $quantity, ?User $user = null): ?InventoryMovement
    {
        if ($quantity <= 0) {
            return null;
        }

        return DB::transaction(function () use ($product, $quantity, $user) {
            /** @var Product $lockedProduct */
            $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->firstOrFail();

            $previousStock = 0;
            $newStock = $quantity;

            $lockedProduct->stock = $newStock;
            $lockedProduct->save();

            $movement = InventoryMovement::create([
                'business_id' => $lockedProduct->business_id,
                'product_id' => $lockedProduct->id,
                'user_id' => $user?->id,
                'type' => InventoryMovement::TYPE_INITIAL,
                'quantity' => $quantity,
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'unit_cost' => $lockedProduct->cost,
                'reason' => 'Carga inicial de inventario',
            ]);

            AuditLogger::record(
                'inventory.initial_stock',
                "Carga inicial de {$quantity} {$lockedProduct->unit} para el producto {$lockedProduct->name}.",
                $movement,
                [
                    'sku' => $lockedProduct->sku,
                    'quantity' => $quantity,
                    'stock' => $newStock,
                ],
                $lockedProduct->business_id
            );

            return $movement;
        });
    }

    /**
     * Deduce stock de forma atómica y concurrente para una venta en POS con trazabilidad completa.
     */
    public function recordSaleDeduction(Product $product, int $quantity, Sale $sale, ?User $user = null): InventoryMovement
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException("La cantidad a vender debe ser mayor a cero.");
        }

        /** @var Product $lockedProduct */
        $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->firstOrFail();

        if (! $lockedProduct->isActive()) {
            throw new InvalidArgumentException("El producto '{$lockedProduct->name}' se encuentra desactivado para ventas.");
        }

        $previousStock = $lockedProduct->stock;

        if ($previousStock < $quantity) {
            throw new InvalidArgumentException(
                "Stock insuficiente para '{$lockedProduct->name}'. Existencias actuales: {$previousStock}, solicitadas: {$quantity}."
            );
        }

        $newStock = $previousStock - $quantity;
        $lockedProduct->stock = $newStock;
        $lockedProduct->save();

        return InventoryMovement::create([
            'business_id' => $lockedProduct->business_id,
            'product_id' => $lockedProduct->id,
            'user_id' => $user?->id ?: $sale->user_id,
            'type' => InventoryMovement::TYPE_SALE,
            'quantity' => -$quantity,
            'previous_stock' => $previousStock,
            'new_stock' => $newStock,
            'unit_cost' => $lockedProduct->cost,
            'reason' => "Venta en caja comprobante #{$sale->invoice_number}",
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
        ]);
    }

    /**
     * Realiza un ajuste manual de stock (entrada, salida o conteo físico) con motivo obligatorio.
     */
    public function adjustStock(
        Product $product,
        string $type,
        int $amount,
        string $reason,
        ?User $user = null
    ): InventoryMovement {
        $reason = trim($reason);
        if (mb_strlen($reason) < 3) {
            throw new InvalidArgumentException("El motivo del movimiento es obligatorio y debe tener al menos 3 caracteres.");
        }

        return DB::transaction(function () use ($product, $type, $amount, $reason, $user) {
            /** @var Product $lockedProduct */
            $lockedProduct = Product::where('id', $product->id)->lockForUpdate()->firstOrFail();
            $previousStock = $lockedProduct->stock;

            switch ($type) {
                case InventoryMovement::TYPE_IN:
                    if ($amount <= 0) {
                        throw new InvalidArgumentException("La cantidad de entrada debe ser superior a cero.");
                    }
                    $delta = $amount;
                    $newStock = $previousStock + $amount;
                    break;

                case InventoryMovement::TYPE_OUT:
                    if ($amount <= 0) {
                        throw new InvalidArgumentException("La cantidad de salida debe ser superior a cero.");
                    }
                    if ($previousStock < $amount) {
                        throw new InvalidArgumentException(
                            "No se pueden retirar {$amount} unidades porque solo hay {$previousStock} en existencia. No se permite stock negativo."
                        );
                    }
                    $delta = -$amount;
                    $newStock = $previousStock - $amount;
                    break;

                case InventoryMovement::TYPE_ADJUSTMENT:
                    if ($amount < 0) {
                        throw new InvalidArgumentException("El stock de ajuste físico no puede ser negativo.");
                    }
                    $delta = $amount - $previousStock;
                    $newStock = $amount;
                    break;

                default:
                    throw new InvalidArgumentException("Tipo de movimiento no válido: {$type}.");
            }

            $lockedProduct->stock = $newStock;
            $lockedProduct->save();

            $movement = InventoryMovement::create([
                'business_id' => $lockedProduct->business_id,
                'product_id' => $lockedProduct->id,
                'user_id' => $user?->id,
                'type' => $type,
                'quantity' => $delta,
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'unit_cost' => $lockedProduct->cost,
                'reason' => $reason,
            ]);

            AuditLogger::record(
                'inventory.manual_adjustment',
                "Ajuste manual de stock ({$type}) en {$lockedProduct->name}: {$previousStock} -> {$newStock} ({$delta}). Motivo: {$reason}.",
                $movement,
                [
                    'sku' => $lockedProduct->sku,
                    'type' => $type,
                    'delta' => $delta,
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                    'reason' => $reason,
                ],
                $lockedProduct->business_id
            );

            return $movement;
        });
    }
}
