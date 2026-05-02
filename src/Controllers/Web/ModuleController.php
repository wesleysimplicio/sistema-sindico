<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Core\View;

/**
 * Generic placeholder controller for every module screen.
 * Visual refinement of each list/detail/form follows docs/print/ references.
 */
final class ModuleController
{
    /** @var array<string, array{title:string, description:string, api:string}> */
    private static array $modules = [
        'condominios'   => ['title' => 'Condominios',          'description' => 'Cadastro de condominios gerenciados pelo sindico.',                  'api' => '/api/condominiums'],
        'unidades'      => ['title' => 'Unidades',             'description' => 'Apartamentos, casas e blocos vinculados aos condominios.',           'api' => '/api/units'],
        'moradores'     => ['title' => 'Moradores',            'description' => 'Residentes ativos, dependentes e proprietarios.',                    'api' => '/api/residents'],
        'visitantes'    => ['title' => 'Visitantes',           'description' => 'Visitantes esperados, recorrentes e historico de entrada.',          'api' => '/api/visitors'],
        'prestadores'   => ['title' => 'Prestadores',          'description' => 'Prestadores de servico e equipe interna.',                           'api' => '/api/providers'],
        'veiculos'      => ['title' => 'Veiculos',             'description' => 'Veiculos cadastrados de moradores e visitantes.',                    'api' => '/api/vehicles'],
        'avisos'        => ['title' => 'Avisos',               'description' => 'Mural de avisos e comunicados oficiais.',                            'api' => '/api/notices'],
        'documentos'    => ['title' => 'Documentos',           'description' => 'Atas, regulamentos e documentos do condominio.',                     'api' => '/api/documents'],
        'encomendas'    => ['title' => 'Encomendas',           'description' => 'Recebimento e retirada de encomendas na portaria.',                  'api' => '/api/deliveries'],
        'solicitacoes'  => ['title' => 'Solicitacoes',         'description' => 'Pedidos formais ao sindico ou administradora.',                      'api' => '/api/requests'],
        'ocorrencias'   => ['title' => 'Ocorrencias',          'description' => 'Reclamacoes, incidentes e registros de ocorrencia.',                 'api' => '/api/occurrences'],
        'acessos'       => ['title' => 'Historico de acessos', 'description' => 'Log de entradas e saidas, inclusive por reconhecimento facial.',     'api' => '/api/access-history'],
        'convites'      => ['title' => 'Convites QR',          'description' => 'Convites com QR-code para visitantes e prestadores.',                'api' => '/api/invitations'],
        'portaria'      => ['title' => 'Portaria',             'description' => 'Alertas e fila de atendimento da portaria.',                         'api' => '/api/portaria'],
        'manutencao'    => ['title' => 'Manutencao',           'description' => 'Chamados de manutencao das areas comuns e privativas.',              'api' => '/api/maintenance'],
        'pagamentos'    => ['title' => 'Pagamentos',           'description' => 'Boletos, recebimentos e inadimplencia.',                              'api' => '/api/payments'],
        'configuracoes' => ['title' => 'Configuracoes',        'description' => 'Preferencias, notificacoes e permissoes do app.',                    'api' => '/api/settings'],
        'perfil'        => ['title' => 'Perfil',               'description' => 'Dados do usuario logado e seguranca.',                                'api' => '/api/profile'],
    ];

    public static function condominios(): void   { self::renderModule('condominios'); }
    public static function unidades(): void      { self::renderModule('unidades'); }
    public static function moradores(): void     { self::renderModule('moradores'); }
    public static function visitantes(): void    { self::renderModule('visitantes'); }
    public static function prestadores(): void   { self::renderModule('prestadores'); }
    public static function veiculos(): void      { self::renderModule('veiculos'); }
    public static function avisos(): void        { self::renderModule('avisos'); }
    public static function documentos(): void    { self::renderModule('documentos'); }
    public static function encomendas(): void    { self::renderModule('encomendas'); }
    public static function solicitacoes(): void  { self::renderModule('solicitacoes'); }
    public static function ocorrencias(): void   { self::renderModule('ocorrencias'); }
    public static function acessos(): void       { self::renderModule('acessos'); }
    public static function convites(): void      { self::renderModule('convites'); }
    public static function portaria(): void      { self::renderModule('portaria'); }
    public static function manutencao(): void    { self::renderModule('manutencao'); }
    public static function pagamentos(): void    { self::renderModule('pagamentos'); }
    public static function configuracoes(): void { self::renderModule('configuracoes'); }
    public static function perfil(): void        { self::renderModule('perfil'); }

    private static function renderModule(string $key): void
    {
        $module = self::$modules[$key] ?? [
            'title'       => ucfirst($key),
            'description' => 'Modulo ainda nao mapeado.',
            'api'         => '/api/' . $key,
        ];

        View::render('modules/placeholder', [
            'title'       => $module['title'] . ' | Sistema Sindico',
            'active'      => $key,
            'moduleTitle' => $module['title'],
            'description' => $module['description'],
            'apiEndpoint' => $module['api'],
        ]);
    }
}
