<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
            'journal.manage' => \App\Http\Middleware\EnsureCanManageJournal::class,
            'journal.manage.api' => \App\Http\Middleware\EnsureCanManageJournalApi::class,
            'review.queue' => \App\Http\Middleware\EnsureCanAccessReviewQueue::class,
            'production.queue' => \App\Http\Middleware\EnsureCanAccessProductionQueue::class,
            'api.enabled' => \App\Http\Middleware\EnsureApiEnabled::class,
            'optional.sanctum' => \App\Http\Middleware\OptionalSanctumAuth::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'paystack/webhook',
        ]);
        $middleware->redirectUsersTo(function () {
            $user = auth()->user();
            if (! $user) {
                return route('dashboard');
            }

            $name = $user->homeRouteName();
            $params = $user->homeRouteParameters();

            return route($name, $params);
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
