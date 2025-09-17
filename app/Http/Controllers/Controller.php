<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\JsonResponse;
use App\Models\AuditLog;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Réponse JSON normalisée (succès)
     */
    protected function success($data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    /**
     * Réponse JSON normalisée (erreur)
     */
    protected function error(string $message, array $errors = [], int $status = 400): JsonResponse
    {
        return response()->json([
            'status'  => 'error',
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }

    /**
     * Réponse JSON paginée
     */
    protected function paginated($collection, string $message = 'OK'): JsonResponse
    {
        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $collection->items(),
            'meta'    => [
                'current_page' => $collection->currentPage(),
                'per_page'     => $collection->perPage(),
                'total'        => $collection->total(),
                'last_page'    => $collection->lastPage(),
            ],
        ]);
    }

    /**
     * Logger une action métier dans audit_logs
     */
    protected function audit(string $action, array $details = [], ?int $status = null): void
    {
        AuditLog::create([
            'user_id'    => auth()->id(),
            'action'     => $action,
            'details'    => $details,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'http_status'=> $status,
        ]);
    }
}
