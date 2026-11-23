php artisan optimize:clear<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ResourceController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\IntegrationController;

Route::prefix('v1')->group(function () {

    // AUTHENTICATION
    Route::post('/login', [AuthController::class, 'login']);

    // PROTECTED API ROUTES
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // DASHBOARD
        Route::get('/dashboard', [InventoryController::class, 'dashboard']);

        // INVENTORY ADJUSTMENTS
        Route::post('/stock/adjustments', [InventoryController::class, 'adjust']);

        // PURCHASE ORDER ITEMS
        Route::post(
            '/purchase-orders/{id}/items',
            [ResourceController::class, 'addPurchaseOrderItem']
        )->whereNumber('id');

        // API INTEGRATIONS
        Route::get('/integrations', [IntegrationController::class, 'index']);

        Route::post('/integrations/tokens', [IntegrationController::class, 'createToken']);

        Route::put(
            '/integrations/hr/employees/{externalId}',
            [IntegrationController::class, 'hrEmployee']
        );

        Route::post(
            '/integrations/crater/purchase-orders/{purchaseOrder}/export',
            [IntegrationController::class, 'craterExport']
        );

        // DYNAMIC INVENTORY RESOURCES
        $resources = [
            'products',
            'categories',
            'warehouses',
            'suppliers',
            'stock-movements',
            'purchase-orders'
        ];

        Route::get('/{resource}', [ResourceController::class, 'index'])
            ->whereIn('resource', $resources);

        Route::post('/{resource}', [ResourceController::class, 'store'])
            ->whereIn('resource', $resources);

        Route::get('/{resource}/{id}', [ResourceController::class, 'show'])
            ->whereIn('resource', $resources)
            ->whereNumber('id');

        Route::match(['put', 'patch'], '/{resource}/{id}', [ResourceController::class, 'update'])
            ->whereIn('resource', $resources)
            ->whereNumber('id');

        Route::delete('/{resource}/{id}', [ResourceController::class, 'destroy'])
            ->whereIn('resource', $resources)
            ->whereNumber('id');

    });

    // PUBLIC WEBHOOK
    Route::post('/integrations/webhooks', [IntegrationController::class, 'webhook']);

});
