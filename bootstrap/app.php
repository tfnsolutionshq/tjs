<?php

use App\Support\ApiNotFoundMessage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prependToGroup('api', \App\Http\Middleware\ForceApiJsonResponse::class);

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
            'journal.manage' => \App\Http\Middleware\EnsureCanManageJournal::class,
            'journal.manage.api' => \App\Http\Middleware\EnsureCanManageJournalApi::class,
            'review.queue' => \App\Http\Middleware\EnsureCanAccessReviewQueue::class,
            'production.queue' => \App\Http\Middleware\EnsureCanAccessProductionQueue::class,
            'api.enabled' => \App\Http\Middleware\EnsureApiEnabled::class,
            'api.docs' => \App\Http\Middleware\EnsureApiDocsEnabled::class,
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
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $throwable) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => ApiNotFoundMessage::forModel($exception->getModel()),
            ], 404);
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if (! $request->is('api/*') || $exception->getStatusCode() !== 404) {
                return null;
            }

            return response()->json([
                'message' => ApiNotFoundMessage::forHttpException($exception),
            ], 404);
        });
    })->create();
