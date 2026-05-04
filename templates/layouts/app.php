<?php
/** @var string $title */
/** @var string $content */
/** @var string|null $active */

use App\Core\Auth;

$active = $active ?? '';
$nav = [
    ['key' => 'dashboard',    'label' => 'Dashboard',    'route' => '/dashboard'],
    ['key' => 'condominios',  'label' => 'Condominios',  'route' => '/condominios'],
    ['key' => 'unidades',     'label' => 'Unidades',     'route' => '/unidades'],
    ['key' => 'moradores',    'label' => 'Moradores',    'route' => '/moradores'],
    ['key' => 'visitantes',   'label' => 'Visitantes',   'route' => '/visitantes'],
    ['key' => 'avisos',       'label' => 'Avisos',       'route' => '/avisos'],
    ['key' => 'documentos',   'label' => 'Documentos',   'route' => '/documentos'],
    ['key' => 'encomendas',   'label' => 'Encomendas',   'route' => '/encomendas'],
    ['key' => 'manutencao',   'label' => 'Manutencao',   'route' => '/manutencao'],
    ['key' => 'pagamentos',   'label' => 'Pagamentos',   'route' => '/pagamentos'],
    ['key' => 'reservas',     'label' => 'Reservas',     'route' => '/reservas'],
    ['key' => 'areas',        'label' => 'Areas comuns', 'route' => '/areas'],
    ['key' => 'mensagens',    'label' => 'Mensagens',    'route' => '/mensagens'],
    ['key' => 'perfil',       'label' => 'Perfil',       'route' => '/perfil'],
];
$me = Auth::user();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($title ?? 'Sistema Sindico') ?></title>
<link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<header class="topbar">
  <h1>Sistema Sindico</h1>
  <div class="topbar-right">
    <?php if ($me): ?>
      <span class="chip"><?= htmlspecialchars((string) $me['name']) ?> &middot; <?= htmlspecialchars((string) $me['role']) ?></span>
      <a class="chip link" href="/logout">Sair</a>
    <?php endif; ?>
  </div>
</header>
<div class="layout">
  <nav class="sidebar">
    <?php foreach ($nav as $item): ?>
      <a href="<?= htmlspecialchars($item['route']) ?>" class="<?= $active === $item['key'] ? 'active' : '' ?>"><?= htmlspecialchars($item['label']) ?></a>
    <?php endforeach; ?>
  </nav>
  <main><?= $content ?></main>
</div>
<footer>
  Versao <?= htmlspecialchars(trim((string) @file_get_contents(dirname(__DIR__, 2) . '/VERSION'))) ?>
  &middot; UI sera refinada conforme docs/print/
</footer>
</body>
</html>
