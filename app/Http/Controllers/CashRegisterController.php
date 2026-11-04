<?php

namespace App\Http\Controllers;

use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\CashShift;
use App\Services\AuditLogger;
use App\Services\TenantManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CashRegisterController extends Controller
{
    public function __construct(
        protected TenantManager $tenantManager
    ) {}

    /**
     * Muestra el resumen de cajas registradoras y el historial de turnos del negocio.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', CashShift::class);

        $businessId = $this->tenantManager->getTenantId() ?: Auth::user()?->current_business_id;

        // Asegurar que exista al menos una caja por defecto para el negocio
        $registers = CashRegister::firstOrCreate(
            ['business_id' => $businessId, 'name' => 'Caja Principal'],
            ['status' => CashRegister::STATUS_ACTIVE, 'notes' => 'Caja registradora inicial del negocio']
        );

        $allRegisters = CashRegister::orderBy('name')->get();

        $shiftsQuery = CashShift::with(['cashRegister', 'user', 'closedByUser'])
            ->orderByDesc('opened_at');

        if ($request->filled('register_id')) {
            $shiftsQuery->where('cash_register_id', $request->input('register_id'));
        }

        if ($request->filled('status')) {
            $shiftsQuery->where('status', $request->input('status'));
        }

        if ($request->filled('date_from')) {
            $shiftsQuery->whereDate('opened_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $shiftsQuery->whereDate('opened_at', '<=', $request->input('date_to'));
        }

        $shifts = $shiftsQuery->paginate(15)->withQueryString();

        $currentShift = CashShift::with(['cashRegister', 'user'])
            ->where('status', CashShift::STATUS_OPEN)
            ->where('user_id', Auth::id())
            ->latest('opened_at')
            ->first();

        return view('cash.index', compact('allRegisters', 'shifts', 'currentShift'));
    }

    /**
     * Panel operativo de la caja actual para el cajero o administrador en turno.
     */
    public function current(): View
    {
        Gate::authorize('viewAny', CashShift::class);

        $businessId = $this->tenantManager->getTenantId() ?: Auth::user()?->current_business_id;

        // Buscar turno abierto para el usuario actual o en general para el negocio si es admin
        $shift = CashShift::with(['cashRegister', 'user'])
            ->where('status', CashShift::STATUS_OPEN)
            ->where('user_id', Auth::id())
            ->latest('opened_at')
            ->first();

        if (! $shift && (Auth::user()->isAdmin() || Auth::user()->isOwner())) {
            $shift = CashShift::with(['cashRegister', 'user'])
                ->where('status', CashShift::STATUS_OPEN)
                ->latest('opened_at')
                ->first();
        }

        $registers = CashRegister::where('status', '!=', 'archived')->orderBy('name')->get();

        $metrics = null;
        $recentSales = collect();
        $movements = collect();

        if ($shift) {
            $metrics = [
                'initial_cash' => (float) $shift->initial_cash,
                'cash_sales' => $shift->cashSalesTotal(),
                'card_sales' => $shift->cardSalesTotal(),
                'transfer_sales' => $shift->transferSalesTotal(),
                'cash_in' => $shift->cashInTotal(),
                'cash_out' => $shift->cashOutTotal(),
                'cash_returns' => $shift->cashReturnsTotal(),
                'expected_cash' => $shift->calculateExpectedCash(),
            ];

            $recentSales = $shift->sales()
                ->with(['customer', 'user'])
                ->orderByDesc('created_at')
                ->take(10)
                ->get();

            $movements = $shift->movements()
                ->with('user')
                ->orderByDesc('created_at')
                ->get();
        }

        return view('cash.current', compact('shift', 'registers', 'metrics', 'recentSales', 'movements'));
    }

    /**
     * Apertura de un nuevo turno de caja registradora.
     */
    public function openShift(Request $request): RedirectResponse
    {
        Gate::authorize('create', CashShift::class);

        $validated = $request->validate([
            'cash_register_id' => ['required', 'integer', 'exists:cash_registers,id'],
            'initial_cash' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $businessId = $this->tenantManager->getTenantId() ?: Auth::user()?->current_business_id;

        $register = CashRegister::findOrFail($validated['cash_register_id']);
        if ($register->business_id !== $businessId) {
            abort(403, 'No autorizado para operar en esta caja.');
        }

        // Verificar si la caja ya tiene un turno abierto
        $activeShiftOnRegister = CashShift::where('business_id', $businessId)
            ->where('cash_register_id', $register->id)
            ->where('status', CashShift::STATUS_OPEN)
            ->first();

        if ($activeShiftOnRegister) {
            return back()->withErrors(['cash_register_id' => 'Esta caja registradora ya cuenta con un turno abierto actualmente por ' . ($activeShiftOnRegister->user?->name ?? 'otro cajero') . '.']);
        }

        // Verificar si el usuario ya tiene un turno abierto en otra caja
        $userOpenShift = CashShift::where('business_id', $businessId)
            ->where('user_id', Auth::id())
            ->where('status', CashShift::STATUS_OPEN)
            ->first();

        if ($userOpenShift) {
            return back()->withErrors(['error' => 'Ya posees un turno abierto en la caja ' . ($userOpenShift->cashRegister?->name ?? '#') . '. Debes cerrarlo antes de iniciar otro.']);
        }

        $shift = DB::transaction(function () use ($businessId, $register, $validated) {
            $shift = CashShift::create([
                'business_id' => $businessId,
                'cash_register_id' => $register->id,
                'user_id' => Auth::id(),
                'opened_at' => now(),
                'initial_cash' => $validated['initial_cash'],
                'expected_cash' => $validated['initial_cash'],
                'status' => CashShift::STATUS_OPEN,
                'notes' => $validated['notes'] ?? null,
            ]);

            $register->update(['status' => CashRegister::STATUS_OPEN]);

            AuditLogger::record(
                'cash_shift_opened',
                "Apertura de turno en caja '{$register->name}' con fondo inicial $" . number_format($shift->initial_cash, 2),
                $shift,
                ['initial_cash' => $shift->initial_cash, 'cash_register_id' => $register->id]
            );

            return $shift;
        });

        return redirect()->route('cash.current')->with('success', 'Turno de caja abierto exitosamente con fondo inicial de $' . number_format($shift->initial_cash, 2));
    }

    /**
     * Registro de entrada o salida autorizada de efectivo.
     */
    public function recordMovement(Request $request, CashShift $shift): RedirectResponse
    {
        Gate::authorize('update', $shift);

        if (! $shift->isOpen()) {
            return back()->withErrors(['error' => 'No es posible registrar movimientos en un turno cerrado.']);
        }

        $validated = $request->validate([
            'type' => ['required', 'in:in,out'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'reason' => ['required', 'string', 'min:3', 'max:255'],
        ]);

        $movement = DB::transaction(function () use ($shift, $validated) {
            $movement = CashMovement::create([
                'business_id' => $shift->business_id,
                'cash_shift_id' => $shift->id,
                'user_id' => Auth::id(),
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'reason' => $validated['reason'],
            ]);

            $typeLabel = $validated['type'] === 'in' ? 'Entrada' : 'Salida';

            AuditLogger::record(
                'cash_movement_recorded',
                "{$typeLabel} de efectivo por $" . number_format($validated['amount'], 2) . " en turno #{$shift->id}. Motivo: {$validated['reason']}",
                $movement,
                ['type' => $validated['type'], 'amount' => $validated['amount'], 'shift_id' => $shift->id]
            );

            return $movement;
        });

        $tipo = $movement->type === 'in' ? 'Entrada' : 'Salida';
        return back()->with('success', "{$tipo} de efectivo por $" . number_format($movement->amount, 2) . " registrada correctamente.");
    }

    /**
     * Cierre de turno y arqueo con justificación de diferencias.
     */
    public function closeShift(Request $request, CashShift $shift): RedirectResponse
    {
        Gate::authorize('close', $shift);

        if (! $shift->isOpen()) {
            return back()->withErrors(['error' => 'El turno ya se encuentra cerrado.']);
        }

        $validated = $request->validate([
            'closing_cash' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $expectedCash = $shift->calculateExpectedCash();
        $closingCash = (float) $validated['closing_cash'];
        $difference = round($closingCash - $expectedCash, 2);

        // Si existe diferencia significativa (> 0.05) se exige observación explicativa
        if (abs($difference) > 0.05 && empty(trim($validated['notes'] ?? ''))) {
            return back()->withErrors(['notes' => 'Se detectó una diferencia de caja de $' . number_format($difference, 2) . '. Debe ingresar obligatoriamente una justificación u observación para asentar el arqueo.'])->withInput();
        }

        DB::transaction(function () use ($shift, $closingCash, $expectedCash, $difference, $validated) {
            $shift->update([
                'closed_by_user_id' => Auth::id(),
                'closed_at' => now(),
                'expected_cash' => $expectedCash,
                'closing_cash' => $closingCash,
                'difference' => $difference,
                'status' => CashShift::STATUS_CLOSED,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Si no hay más turnos abiertos en la registradora, marcarla como cerrada
            $otherOpenShifts = CashShift::where('cash_register_id', $shift->cash_register_id)
                ->where('status', CashShift::STATUS_OPEN)
                ->where('id', '!=', $shift->id)
                ->exists();

            if (! $otherOpenShifts) {
                $shift->cashRegister?->update(['status' => CashRegister::STATUS_CLOSED]);
            }

            AuditLogger::record(
                'cash_shift_closed',
                "Cierre y arqueo de turno #{$shift->id}. Esperado: $" . number_format($expectedCash, 2) . ", Arqueado: $" . number_format($closingCash, 2) . ", Diferencia: $" . number_format($difference, 2),
                $shift,
                [
                    'expected_cash' => $expectedCash,
                    'closing_cash' => $closingCash,
                    'difference' => $difference,
                    'cash_sales' => $shift->cashSalesTotal(),
                    'card_sales' => $shift->cardSalesTotal(),
                    'transfer_sales' => $shift->transferSalesTotal(),
                ]
            );
        });

        return redirect()->route('cash.show', $shift)->with('success', 'Turno cerrado y arqueo registrado correctamente.');
    }

    /**
     * Comprobante detallado de arqueo y cierre de turno.
     */
    public function show(CashShift $shift): View
    {
        Gate::authorize('view', $shift);

        $shift->load([
            'cashRegister',
            'user',
            'closedByUser',
            'movements.user',
            'returns.user',
            'sales.customer',
            'sales.user',
        ]);

        return view('cash.show', compact('shift'));
    }
}
