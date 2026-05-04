<?php

declare(strict_types=1);

/**
 * @var \App\Core\Router $router
 *
 * Mobile-app-ready JSON endpoints.
 * Public: health + login. All others require Bearer JWT (ApiAuth).
 * Tenant scoping is enforced inside controllers via Auth::condominiumId().
 */

use App\Controllers\Api\AuthController;
use App\Controllers\Api\BookingController;
use App\Controllers\Api\DashboardController;
use App\Controllers\Api\CommonAreaController;
use App\Controllers\Api\CondominiumController;
use App\Controllers\Api\DeliveryController;
use App\Controllers\Api\DocumentController;
use App\Controllers\Api\HealthController;
use App\Controllers\Api\MaintenanceController;
use App\Controllers\Api\MessageController;
use App\Controllers\Api\NoticeController;
use App\Controllers\Api\PaymentController;
use App\Controllers\Api\ProfileController;
use App\Controllers\Api\ResidentController;
use App\Controllers\Api\UnitController;
use App\Controllers\Api\VisitorController;
use App\Middleware\ApiAuth;

$router->get('/api/health',       [HealthController::class, 'index']);
$router->post('/api/auth/login',  [AuthController::class,   'login']);

$router->group([ApiAuth::class], function ($router): void {
    $router->post('/api/auth/logout', [AuthController::class,      'logout']);
    $router->get('/api/auth/me',      [AuthController::class,      'me']);
    $router->get('/api/profile',      [ProfileController::class,   'show']);
    $router->get('/api/dashboard',    [DashboardController::class, 'index']);

    $router->get('/api/condominiums',         [CondominiumController::class, 'index']);
    $router->get('/api/condominiums/{id}',    [CondominiumController::class, 'show']);

    $router->get('/api/units',                [UnitController::class, 'index']);
    $router->get('/api/residents',            [ResidentController::class, 'index']);

    $router->get('/api/notices',              [NoticeController::class, 'index']);
    $router->get('/api/notices/{id}',         [NoticeController::class, 'show']);
    $router->post('/api/notices',             [NoticeController::class, 'store']);

    $router->get('/api/maintenance',          [MaintenanceController::class, 'index']);
    $router->get('/api/maintenance/mine',     [MaintenanceController::class, 'mine']);
    $router->post('/api/maintenance',         [MaintenanceController::class, 'store']);
    $router->patch('/api/maintenance/{id}',   [MaintenanceController::class, 'updateStatus']);

    $router->get('/api/payments',             [PaymentController::class, 'index']);
    $router->get('/api/payments/mine',        [PaymentController::class, 'mine']);
    $router->get('/api/payments/summary',     [PaymentController::class, 'summary']);
    $router->patch('/api/payments/{id}/pay',  [PaymentController::class, 'markPaid']);

    $router->get('/api/visitors',             [VisitorController::class, 'index']);
    $router->get('/api/visitors/mine',        [VisitorController::class, 'mine']);
    $router->post('/api/visitors',            [VisitorController::class, 'store']);
    $router->patch('/api/visitors/{id}',      [VisitorController::class, 'updateStatus']);
    $router->get('/api/visitors/qr/{token}',  [VisitorController::class, 'byQr']);

    $router->get('/api/deliveries',           [DeliveryController::class, 'index']);
    $router->get('/api/deliveries/mine',      [DeliveryController::class, 'mine']);
    $router->post('/api/deliveries',          [DeliveryController::class, 'store']);
    $router->patch('/api/deliveries/{id}/withdraw', [DeliveryController::class, 'withdraw']);

    $router->get('/api/common-areas',         [CommonAreaController::class, 'index']);

    $router->get('/api/bookings',             [BookingController::class, 'index']);
    $router->get('/api/bookings/mine',        [BookingController::class, 'mine']);
    $router->post('/api/bookings',            [BookingController::class, 'store']);
    $router->patch('/api/bookings/{id}',      [BookingController::class, 'updateStatus']);

    $router->get('/api/documents',            [DocumentController::class, 'index']);

    $router->get('/api/messages',             [MessageController::class, 'index']);
    $router->get('/api/messages/inbox',       [MessageController::class, 'inbox']);
    $router->post('/api/messages',            [MessageController::class, 'store']);
    $router->patch('/api/messages/{id}/read', [MessageController::class, 'read']);
});
