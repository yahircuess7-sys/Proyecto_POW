<?php

use App\Models\BusinessInvitation;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('puntoagil:make-superadmin {email} {--name=Super Admin} {--password=}', function (string $email) {
    $normalizedEmail = strtolower(trim($email));
    $user = User::whereRaw('LOWER(email) = ?', [$normalizedEmail])->first();

    if (! $user) {
        $rawPassword = (string) ($this->option('password') ?: Str::random(16));
        $user = User::create([
            'name' => (string) $this->option('name'),
            'email' => $normalizedEmail,
            'password' => Hash::make($rawPassword),
            'role' => User::ROLE_SUPER_ADMIN,
            'is_super_admin' => true,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->info("Super Admin creado exitosamente: {$user->email}");
        if (! $this->option('password')) {
            $this->warn("Contraseña temporal generada: {$rawPassword}");
        }
    } else {
        $user->update([
            'is_super_admin' => true,
            'role' => User::ROLE_SUPER_ADMIN,
            'status' => 'active',
        ]);
        $this->info("Usuario promovido a Super Admin exitosamente: {$user->email}");
    }

    AuditLogger::record(
        'superadmin.cli_promoted',
        "Se otorgó privilegio de Super Admin mediante consola CLI a {$user->email}.",
        $user,
        ['email' => $user->email]
    );
})->purpose('Promueve o crea un usuario Super Administrador global de PuntoÁgil');

Artisan::command('puntoagil:subscriptions-check', function () {
    $expiredSubscriptions = Subscription::whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING])
        ->whereNotNull('ends_at')
        ->where('ends_at', '<', now())
        ->get();

    $count = 0;
    foreach ($expiredSubscriptions as $sub) {
        $sub->update(['status' => Subscription::STATUS_PAST_DUE]);
        AuditLogger::record(
            'subscription.expired_automatically',
            "La suscripción #{$sub->id} pasó automáticamente a estado vencida (past_due).",
            $sub,
            ['ends_at' => $sub->ends_at?->toIso8601String()],
            $sub->business_id
        );
        $count++;
    }

    $purgedInvitations = BusinessInvitation::whereNull('accepted_at')
        ->where('expires_at', '<', now()->subDays(30))
        ->delete();

    $this->info("Suscripciones marcadas como vencidas: {$count}. Invitaciones antiguas depuradas: {$purgedInvitations}.");
})->purpose('Actualiza automáticamente el estado de suscripciones vencidas y limpia invitaciones expiradas');

Schedule::command('puntoagil:subscriptions-check')->daily();
