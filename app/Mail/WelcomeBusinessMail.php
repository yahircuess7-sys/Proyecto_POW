<?php

namespace App\Mail;

use App\Models\Business;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeBusinessMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Business $business
    ) {}

    public function build(): self
    {
        return $this->subject("¡Bienvenido a PuntoÁgil! — {$this->business->name}")
            ->html("
                <div style='font-family: Arial, sans-serif; color: #0f172a; max-width: 600px; margin: 0 auto; padding: 24px;'>
                    <h2 style='color: #059669;'>¡Bienvenido a PuntoÁgil, {$this->user->name}!</h2>
                    <p>Tu negocio <strong>{$this->business->name}</strong> ha sido registrado exitosamente en la plataforma SaaS PuntoÁgil.</p>
                    <p>Cuentas con <strong>14 días de prueba</strong> en el Plan PuntoÁgil (\$30.00 USD/mes) con acceso completo a punto de venta (POS), control de caja, inventario, compras y devoluciones.</p>
                    <p style='margin-top: 24px;'>
                        <a href='" . url('/dashboard') . "' style='background: #059669; color: #ffffff; text-decoration: none; padding: 10px 18px; border-radius: 8px; font-weight: bold;'>
                            Ir al Panel de Control
                        </a>
                    </p>
                </div>
            ");
    }
}
