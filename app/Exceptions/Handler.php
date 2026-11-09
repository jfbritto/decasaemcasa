<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // 404 da API sem o nome do model: "Não encontrado." (inscrição de outro
        // e-mail cai aqui, AM12).
        $this->renderable(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Não encontrado.', 'code' => 'not_found'], 404);
            }
        });
    }

    /**
     * A API do app sempre responde JSON (401, 422, 429), mesmo sem Accept
     * (spec 2026-11-08-app-mobile-design, AM9).
     */
    protected function shouldReturnJson($request, Throwable $e)
    {
        return $request->is('api/*') || parent::shouldReturnJson($request, $e);
    }
}
