<?php

declare(strict_types=1);

/**
 * @var \App\Core\Router $router
 *
 * Web routes:
 * - Public: GET/POST /login, GET /logout
 * - Authenticated admin (AdminOnly: admin|sindico): everything else
 */

use App\Controllers\Web\DashboardController;
use App\Controllers\Web\LoginController;
use App\Controllers\Web\ModuleController;
use App\Middleware\AdminOnly;

$router->get('/login',   [LoginController::class, 'show']);
$router->post('/login',  [LoginController::class, 'submit']);
$router->get('/logout',  [LoginController::class, 'logout']);

$router->get('/forgot-password',   [LoginController::class, 'forgotPasswordShow']);
$router->post('/forgot-password',  [LoginController::class, 'forgotPasswordSubmit']);
$router->get('/verify-code',       [LoginController::class, 'verifyCodeShow']);
$router->post('/verify-code',      [LoginController::class, 'verifyCodeSubmit']);
$router->get('/reset-password',    [LoginController::class, 'resetPasswordShow']);
$router->post('/reset-password',   [LoginController::class, 'resetPasswordSubmit']);

$router->group([AdminOnly::class], function ($router): void {
    $router->get('/',           [DashboardController::class, 'index']);
    $router->get('/dashboard',  [DashboardController::class, 'index']);

    $router->get('/condominios', [ModuleController::class, 'condominios']);
    $router->get('/unidades',    [ModuleController::class, 'unidades']);
    $router->get('/moradores',   [ModuleController::class, 'moradores']);
    $router->get('/visitantes',  [ModuleController::class, 'visitantes']);
    $router->get('/avisos',      [ModuleController::class, 'avisos']);
    $router->get('/documentos',  [ModuleController::class, 'documentos']);
    $router->get('/encomendas',  [ModuleController::class, 'encomendas']);
    $router->get('/manutencao',  [ModuleController::class, 'manutencao']);
    $router->get('/pagamentos',  [ModuleController::class, 'pagamentos']);
    $router->get('/reservas',    [ModuleController::class, 'reservas']);
    $router->get('/areas',       [ModuleController::class, 'areas']);
    $router->get('/mensagens',   [ModuleController::class, 'mensagens']);
    $router->get('/perfil',      [ModuleController::class, 'perfil']);
});
