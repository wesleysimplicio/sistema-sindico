<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Request;
use App\Core\Response;

final class SystemController
{
    private const MIN_REQUIRED = [
        'ios'     => '1.0.0',
        'android' => '1.0.0',
        'web'     => '1.0.0',
    ];

    public function version(): void
    {
        $version  = self::readVersion();
        $platform = (string) Request::input('platform', '');
        $current  = (string) Request::input('current', '');

        $minRequired = self::MIN_REQUIRED[$platform] ?? null;
        $updateRequired = false;
        if ($minRequired !== null && $current !== '') {
            $updateRequired = version_compare($current, $minRequired, '<');
        }

        Response::json([
            'version'         => $version,
            'platform'        => $platform !== '' ? $platform : null,
            'min_required'    => $minRequired,
            'update_required' => $updateRequired,
            'release_notes'   => 'https://github.com/wsimplicio/sistema-sindico/blob/main/CHANGELOG.md',
        ]);
    }

    public function permissions(): void
    {
        Response::json([
            'permissions' => [
                [
                    'key'         => 'notifications',
                    'title'       => 'Notificacoes',
                    'description' => 'Avisos, manutencoes e ocorrencias do condominio em tempo real.',
                    'platforms'   => ['ios', 'android', 'web'],
                    'required'    => false,
                ],
                [
                    'key'         => 'camera',
                    'title'       => 'Camera',
                    'description' => 'Tirar foto para registrar entregas, encomendas e ocorrencias.',
                    'platforms'   => ['ios', 'android'],
                    'required'    => false,
                ],
                [
                    'key'         => 'photos',
                    'title'       => 'Galeria',
                    'description' => 'Anexar imagens em ocorrencias e mensagens.',
                    'platforms'   => ['ios', 'android'],
                    'required'    => false,
                ],
                [
                    'key'         => 'location',
                    'title'       => 'Localizacao',
                    'description' => 'Validar entrega no portao e auxiliar acionamento de portaria remota.',
                    'platforms'   => ['ios', 'android'],
                    'required'    => false,
                ],
                [
                    'key'         => 'biometrics',
                    'title'       => 'Biometria',
                    'description' => 'Acelerar login e proteger acoes sensiveis (Face ID / Touch ID).',
                    'platforms'   => ['ios', 'android'],
                    'required'    => false,
                ],
                [
                    'key'         => 'contacts',
                    'title'       => 'Contatos',
                    'description' => 'Compartilhar visitantes pre-aprovados com a portaria.',
                    'platforms'   => ['ios', 'android'],
                    'required'    => false,
                ],
            ],
            'policy_url'  => 'https://example.com/privacidade',
            'support_url' => 'https://example.com/suporte',
        ]);
    }

    private static function readVersion(): string
    {
        $path = dirname(__DIR__, 3) . '/VERSION';
        if (!is_file($path)) {
            return '0.0.0';
        }
        $raw = (string) file_get_contents($path);
        $raw = trim($raw);
        return $raw !== '' ? $raw : '0.0.0';
    }
}
