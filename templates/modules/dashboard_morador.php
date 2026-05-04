<?php
/** @var array|null $user */
/** @var array|null $condo */
/** @var array<int,array> $notices */
/** @var array<int,array> $maintenance */
/** @var array<int,array> $deliveries */
/** @var int $openMaint */
/** @var int $pendingDeliv */
?>
<div class="card">
  <h2>Olá, <?= htmlspecialchars($user['name'] ?? 'morador') ?> &#128075;</h2>
  <p class="muted">
    <?php if ($condo): ?>
      <?= htmlspecialchars($condo['name']) ?>
      <?php if (!empty($user['unit_id'])): ?> &middot; Unidade <?= (int) $user['unit_id'] ?><?php endif; ?>
    <?php else: ?>
      Nenhum condomínio vinculado. Fale com o síndico.
    <?php endif; ?>
  </p>
</div>

<div class="grid stats">
  <div class="stat info">
    <div class="value"><?= count($notices) ?></div>
    <div class="label">Avisos no condomínio</div>
  </div>
  <div class="stat warn">
    <div class="value"><?= $openMaint ?></div>
    <div class="label">Manutenções abertas</div>
  </div>
  <div class="stat alert">
    <div class="value"><?= $pendingDeliv ?></div>
    <div class="label">Encomendas aguardando</div>
  </div>
</div>

<div class="card">
  <div class="row between" style="margin-bottom:12px">
    <h2 style="margin:0">Avisos recentes</h2>
    <a class="btn" href="/avisos">Ver todos</a>
  </div>
  <?php if (empty($notices)): ?>
    <p class="muted">Nenhum aviso publicado.</p>
  <?php else: ?>
    <ul class="list">
      <?php foreach ($notices as $n): ?>
        <li>
          <strong><?= htmlspecialchars($n['title'] ?? '-') ?></strong>
          <?php if (!empty($n['pinned']) && (int) $n['pinned'] === 1): ?>
            <span class="tag">Fixado</span>
          <?php endif; ?>
          <div class="muted small"><?= htmlspecialchars((string) ($n['published_at'] ?? '')) ?></div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>

<div class="card">
  <div class="row between" style="margin-bottom:12px">
    <h2 style="margin:0">Minhas manutenções</h2>
    <a class="btn" href="/manutencao">Ver todas</a>
  </div>
  <?php if (empty($maintenance)): ?>
    <p class="muted">Nenhum chamado aberto.</p>
  <?php else: ?>
    <ul class="list">
      <?php foreach ($maintenance as $m): ?>
        <li>
          <strong><?= htmlspecialchars($m['title'] ?? '-') ?></strong>
          <span class="tag"><?= htmlspecialchars((string) ($m['status'] ?? '')) ?></span>
          <div class="muted small"><?= htmlspecialchars((string) ($m['created_at'] ?? '')) ?></div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>

<div class="card">
  <div class="row between" style="margin-bottom:12px">
    <h2 style="margin:0">Minhas encomendas</h2>
    <a class="btn" href="/encomendas">Ver todas</a>
  </div>
  <?php if (empty($deliveries)): ?>
    <p class="muted">Nenhuma encomenda registrada.</p>
  <?php else: ?>
    <ul class="list">
      <?php foreach ($deliveries as $d): ?>
        <li>
          <strong><?= htmlspecialchars($d['sender'] ?? '-') ?></strong>
          <span class="tag"><?= htmlspecialchars((string) ($d['status'] ?? '')) ?></span>
          <div class="muted small"><?= htmlspecialchars((string) ($d['received_at'] ?? '')) ?></div>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
