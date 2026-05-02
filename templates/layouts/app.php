<?php
/** @var string $title */
/** @var string $content */
/** @var string|null $active */
$active = $active ?? '';
$nav = [
    ['key' => 'dashboard',    'label' => 'Dashboard',    'route' => '/dashboard'],
    ['key' => 'condominios',  'label' => 'Condominios',  'route' => '/condominios'],
    ['key' => 'unidades',     'label' => 'Unidades',     'route' => '/unidades'],
    ['key' => 'moradores',    'label' => 'Moradores',    'route' => '/moradores'],
    ['key' => 'visitantes',   'label' => 'Visitantes',   'route' => '/visitantes'],
    ['key' => 'prestadores',  'label' => 'Prestadores',  'route' => '/prestadores'],
    ['key' => 'veiculos',     'label' => 'Veiculos',     'route' => '/veiculos'],
    ['key' => 'avisos',       'label' => 'Avisos',       'route' => '/avisos'],
    ['key' => 'documentos',   'label' => 'Documentos',   'route' => '/documentos'],
    ['key' => 'encomendas',   'label' => 'Encomendas',   'route' => '/encomendas'],
    ['key' => 'solicitacoes', 'label' => 'Solicitacoes', 'route' => '/solicitacoes'],
    ['key' => 'ocorrencias',  'label' => 'Ocorrencias',  'route' => '/ocorrencias'],
    ['key' => 'acessos',      'label' => 'Acessos',      'route' => '/acessos'],
    ['key' => 'convites',     'label' => 'Convites QR',  'route' => '/convites'],
    ['key' => 'portaria',     'label' => 'Portaria',     'route' => '/portaria'],
    ['key' => 'manutencao',   'label' => 'Manutencao',   'route' => '/manutencao'],
    ['key' => 'pagamentos',   'label' => 'Pagamentos',   'route' => '/pagamentos'],
    ['key' => 'configuracoes','label' => 'Configuracoes','route' => '/configuracoes'],
    ['key' => 'perfil',       'label' => 'Perfil',       'route' => '/perfil'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($title ?? 'Sistema Sindico') ?></title>
<style>
  :root { --primary:#5b3da9; --primary-dark:#3f2c79; --bg:#f5f5f8; --text:#222; --muted:#666; --card:#fff; --border:#e6e6ee; }
  * { box-sizing:border-box; }
  body { margin:0; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; background:var(--bg); color:var(--text); }
  header.topbar { background:var(--primary); color:#fff; padding:14px 20px; display:flex; align-items:center; justify-content:space-between; }
  header.topbar h1 { font-size:18px; margin:0; font-weight:600; }
  .layout { display:flex; min-height:calc(100vh - 96px); }
  nav.sidebar { width:240px; background:#fff; border-right:1px solid var(--border); padding:16px 0; overflow-y:auto; }
  nav.sidebar a { display:block; padding:10px 20px; color:var(--text); text-decoration:none; font-size:14px; border-left:3px solid transparent; }
  nav.sidebar a:hover { background:#f0eef9; }
  nav.sidebar a.active { background:#efeafc; border-left-color:var(--primary); color:var(--primary-dark); font-weight:600; }
  main { flex:1; padding:24px 32px; }
  .card { background:var(--card); border:1px solid var(--border); border-radius:10px; padding:20px; margin-bottom:16px; }
  .grid { display:grid; gap:16px; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); }
  .shortcut { background:#fff; border:1px solid var(--border); border-radius:10px; padding:18px; text-decoration:none; color:var(--text); display:flex; flex-direction:column; gap:8px; transition:transform .1s; }
  .shortcut:hover { transform:translateY(-2px); border-color:var(--primary); }
  .shortcut .icon { width:36px; height:36px; border-radius:8px; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; }
  .shortcut .label { font-weight:600; }
  h2 { margin-top:0; color:var(--primary-dark); }
  code { background:#f0eef9; padding:2px 6px; border-radius:4px; font-size:13px; }
  .muted { color:var(--muted); font-size:13px; }
  footer { padding:14px 20px; text-align:center; color:var(--muted); font-size:12px; border-top:1px solid var(--border); }
</style>
</head>
<body>
<header class="topbar">
  <h1>Sistema Sindico</h1>
  <div style="font-size:13px; opacity:.85;">Painel administrativo (scaffold)</div>
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
