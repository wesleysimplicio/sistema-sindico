<?php
/** @var array<int, array{label:string, route:string, icon:string}> $shortcuts */
?>
<div class="card">
  <h2>Visao geral</h2>
  <p class="muted">
    Scaffold inicial do painel. As telas finais serao refinadas conforme as referencias
    visuais em <code>docs/print/</code>. Endpoints REST disponiveis em <code>/api</code>
    para consumo futuro pelo app mobile.
  </p>
</div>

<div class="card">
  <h2>Atalhos</h2>
  <div class="grid">
    <?php foreach ($shortcuts as $s): ?>
      <a class="shortcut" href="<?= htmlspecialchars($s['route']) ?>">
        <div class="icon"><?= htmlspecialchars($s['icon']) ?></div>
        <div class="label"><?= htmlspecialchars($s['label']) ?></div>
        <div class="muted"><?= htmlspecialchars($s['route']) ?></div>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<div class="card">
  <h2>Status do scaffold</h2>
  <ul>
    <li>Roteamento web e API ativos.</li>
    <li>Envelope JSON consistente em <code>App\Core\Response::json</code>.</li>
    <li>Conexao MySQL configuravel via <code>.env</code>.</li>
    <li>Schema inicial em <code>database/schema.sql</code> e seeds em <code>database/seed.sql</code>.</li>
    <li>Refinamento visual seguira screenshots de <code>docs/print/</code>.</li>
  </ul>
</div>
