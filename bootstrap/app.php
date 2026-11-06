<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

if (empty($_ENV['VERCEL']) && empty($_SERVER['VERCEL']) && ! getenv('VERCEL') && file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2) + [1 => ''];
        $name = trim($name);
        $value = trim($value, "\"' ");
        if (!empty($name)) {
            $value = preg_replace_callback('/\$\{([A-Z0-9_]+)\}/', function ($m) {
                return $_ENV[$m[1]] ?? (getenv($m[1]) !== false ? getenv($m[1]) : $m[0]);
            }, $value);
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
            putenv("$name=$value");
        }
    }
}

return tap(Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'tenant' => \App\Http\Middleware\IdentifyTenant::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\IdentifyTenant::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $handleCsrfExpiration = function ($request) {
            if ($request->expectsJson()) {
                return null;
            }

            if ($request->is('logout')) {
                \Illuminate\Support\Facades\Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect('/');
            }

            return redirect()->route('login')->with('error', 'Tu sesión ha expirado por inactividad. Por favor, ingresa nuevamente.');
        };

        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) use ($handleCsrfExpiration) {
            return $handleCsrfExpiration($request);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) use ($handleCsrfExpiration) {
            if ($e->getStatusCode() === 419) {
                return $handleCsrfExpiration($request);
            }
        });
    })->create(), function (Application $app): void {
        // Soporte nativo para entorno Serverless de solo lectura (Vercel / AWS Lambda)
        if (! empty($_ENV['VERCEL']) || ! empty($_SERVER['VERCEL']) || getenv('VERCEL')) {
            $tmpStorage = '/tmp/storage';
            foreach ([
                $tmpStorage . '/framework/views',
                $tmpStorage . '/framework/cache/data',
                $tmpStorage . '/framework/sessions',
                $tmpStorage . '/logs',
                $tmpStorage . '/app/public',
            ] as $dir) {
                if (! is_dir($dir)) {
                    @mkdir($dir, 0777, true);
                }
            }
            $app->useStoragePath($tmpStorage);
        }
    });
