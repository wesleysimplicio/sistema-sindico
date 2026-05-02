<?php

declare(strict_types=1);

/**
 * @var \App\Core\Router $router
 *
 * Web routes render server-side templates that scaffold the admin panel.
 * Visual refinement of each screen will follow references in docs/print/.
 */

use App\Controllers\Web\HomeController;
use App\Controllers\Web\ModuleController;

$router->get('/', [HomeController::class, 'index']);
$router->get('/dashboard', [HomeController::class, 'index']);

$router->get('/condominios',     [ModuleController::class, 'condominios']);
$router->get('/unidades',        [ModuleController::class, 'unidades']);
$router->get('/moradores',       [ModuleController::class, 'moradores']);
$router->get('/visitantes',      [ModuleController::class, 'visitantes']);
$router->get('/prestadores',     [ModuleController::class, 'prestadores']);
$router->get('/veiculos',        [ModuleController::class, 'veiculos']);
$router->get('/avisos',          [ModuleController::class, 'avisos']);
$router->get('/documentos',      [ModuleController::class, 'documentos']);
$router->get('/encomendas',      [ModuleController::class, 'encomendas']);
$router->get('/solicitacoes',    [ModuleController::class, 'solicitacoes']);
$router->get('/ocorrencias',     [ModuleController::class, 'ocorrencias']);
$router->get('/acessos',         [ModuleController::class, 'acessos']);
$router->get('/convites',        [ModuleController::class, 'convites']);
$router->get('/portaria',        [ModuleController::class, 'portaria']);
$router->get('/manutencao',      [ModuleController::class, 'manutencao']);
$router->get('/pagamentos',      [ModuleController::class, 'pagamentos']);
$router->get('/configuracoes',   [ModuleController::class, 'configuracoes']);
$router->get('/perfil',          [ModuleController::class, 'perfil']);
