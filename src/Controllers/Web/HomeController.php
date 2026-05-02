<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\View;

final class HomeController
{
    public static function index(): void
    {
        View::render('modules/dashboard', [
            'title'     => 'Dashboard | Sistema Sindico',
            'active'    => 'dashboard',
            'shortcuts' => [
                ['label' => 'Moradores',     'route' => '/moradores',     'icon' => 'M'],
                ['label' => 'Visitantes',    'route' => '/visitantes',    'icon' => 'V'],
                ['label' => 'Veiculos',      'route' => '/veiculos',      'icon' => 'C'],
                ['label' => 'Avisos',        'route' => '/avisos',        'icon' => 'A'],
                ['label' => 'Encomendas',    'route' => '/encomendas',    'icon' => 'E'],
                ['label' => 'Documentos',    'route' => '/documentos',    'icon' => 'D'],
                ['label' => 'Solicitacoes',  'route' => '/solicitacoes',  'icon' => 'S'],
                ['label' => 'Ocorrencias',   'route' => '/ocorrencias',   'icon' => 'O'],
                ['label' => 'Acessos',       'route' => '/acessos',       'icon' => 'H'],
                ['label' => 'Convites QR',   'route' => '/convites',      'icon' => 'Q'],
                ['label' => 'Portaria',      'route' => '/portaria',      'icon' => 'P'],
                ['label' => 'Configuracoes', 'route' => '/configuracoes', 'icon' => 'G'],
            ],
        ]);
    }
}
