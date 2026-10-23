<?php

namespace App\Services;

use App\Models\Product;

class SkuGeneratorService
{
    /**
     * Resuelve y valida el SKU provisto o genera uno automático según nombre y categoría.
     */
    public function resolveSku(?string $sku, string $name, string $category, ?int $productId = null): string
    {
        $sku = trim((string) ($sku ?? ''));

        if ($sku === '' || !preg_match('/^[A-Z]{2,}-\d{2,}$/', strtoupper($sku))) {
            return $this->generateProductSku($name, $category, $productId);
        }

        return $this->normalizeSku($sku);
    }

    /**
     * Genera un SKU con prefijo temático y correlativo único.
     */
    public function generateProductSku(string $name, string $category, ?int $productId = null): string
    {
        $prefix = $this->skuPrefixFromCategoryOrName($name, $category);
        $prefix = strtoupper($this->normalizeSku($prefix));

        $existingSkus = Product::where('sku', 'like', "$prefix-%")
            ->when($productId, fn($query) => $query->where('id', '<>', $productId))
            ->pluck('sku')
            ->toArray();

        $nextNumber = 1;
        while (in_array(sprintf('%s-%03d', $prefix, $nextNumber), $existingSkus, true)) {
            $nextNumber++;
        }

        return sprintf('%s-%03d', $prefix, $nextNumber);
    }

    /**
     * Determina el prefijo según palabras clave o iniciales de la categoría/nombre.
     */
    public function skuPrefixFromCategoryOrName(string $name, string $category): string
    {
        $keywords = [
            'cemento' => 'CEM',
            'clavo' => 'CLA',
            'perno' => 'PER',
            'fijacion' => 'FIJ',
            'herramienta' => 'HER',
            'eléctrica' => 'ELE',
            'electrica' => 'ELE',
            'plomería' => 'PLO',
            'plomeria' => 'PLO',
            'hidráulica' => 'HID',
            'hidraulica' => 'HID',
            'pintura' => 'PIN',
            'alambre' => 'ALM',
            'madera' => 'MAD',
            'abrasivo' => 'ABR',
            'cementos' => 'CEM',
            'pinturas' => 'PIN',
        ];

        $searchText = mb_strtolower($name . ' ' . $category);
        foreach ($keywords as $term => $code) {
            if (str_contains($searchText, $term)) {
                return $code;
            }
        }

        $words = preg_split('/\s+/', preg_replace('/[^\p{L}\p{N}]+/u', ' ', $name));
        $words = array_filter($words);

        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 3) . substr($words[1], 0, 1));
        }

        $first = $words[0] ?? $category;
        return strtoupper(substr($first, 0, 3));
    }

    /**
     * Normaliza un SKU a mayúsculas y guiones estándar.
     */
    public function normalizeSku(string $sku): string
    {
        $sku = strtoupper($sku);
        $sku = str_replace([' ', '_'], '-', $sku);
        $sku = preg_replace('/[^A-Z0-9\-]/', '', $sku);
        return preg_replace('/-+/', '-', trim($sku, '-'));
    }
}
