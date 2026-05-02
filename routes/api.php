<?php

declare(strict_types=1);

/**
 * @var \App\Core\Router $router
 *
 * Mobile-app-ready JSON endpoints.
 * Each resource currently returns scaffold/stub data.
 * Real persistence will be wired through src/Core/Database.php and repositories
 * once UI flows from docs/print/ are confirmed.
 */

use App\Controllers\Api\HealthController;
use App\Controllers\Api\AuthController;
use App\Controllers\Api\CondominiumController;
use App\Controllers\Api\UnitController;
use App\Controllers\Api\ResidentController;
use App\Controllers\Api\NoticeController;
use App\Controllers\Api\MaintenanceController;
use App\Controllers\Api\PaymentController;

$router->get('/api/health', [HealthController::class, 'index']);

$router->post('/api/auth/login',  [AuthController::class, 'login']);
$router->post('/api/auth/logout', [AuthController::class, 'logout']);

$router->get('/api/condominiums', [CondominiumController::class, 'index']);
$router->get('/api/units',        [UnitController::class,        'index']);
$router->get('/api/residents',    [ResidentController::class,    'index']);
$router->get('/api/notices',      [NoticeController::class,      'index']);
$router->get('/api/maintenance',  [MaintenanceController::class, 'index']);
$router->get('/api/payments',     [PaymentController::class,     'index']);
