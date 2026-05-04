<?php
/** @var array|null $user */
/** @var array|null $condo */
/** @var array{residents:int,units:int,open_maintenance:int,pending_payments:int,pending_deliveries:int} $stats */
/** @var array<int,array> $notices */
/** @var array<int,array> $payments */
?>
<div class="card">
  <h2>Bem-vindo, <?= htmlspecialchars($user['name'] ?? 'sindico') ?></h2>
  <p class="muted">
    <?php if ($condo): ?>
      Condominio: <strong><?= htmlspecialchars($condo['name']) ?></strong>
      <?php if (!empty($condo['city'])): ?> &middot; <?= htmlspecialchars($condo['city']) ?><?php endif; ?>
    <?php else: ?>
      Nenhum condominio vinculado ao seu usuario ainda.
    <?php endif; ?>
  </p>
</div>

<div class="grid stats">
  <div class="stat">
    <div class="value"><?= (int) $stats['residents'] ?></div>
    <div class="label">Moradores</div>
  </div>
  <div class="stat">
    <div class="value"><?= (int) $stats['units'] ?></div>
    <div class="label">Unidades</div>
  </div>
  <div class="stat warn">
    <div class="value"><?= (int) $stats['open_maintenance'] ?></div>
    <div class="label">Manutencoes abertas</div>
  </div>
  <div class="stat alert">
    <div class="value"><?= (int) $stats['pending_payments'] ?></div>
    <div class="label">Pagamentos pendentes</div>
  </div>
  <div class="stat info">
    <div class="value"><?= (int) $stats['pending_deliveries'] ?></div>
    <div class="label">Encomendas aguardando</div>
  </div>
</div>

<div class="card">
  <h2>Avisos recentes</h2>
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
  <h2>Pagamentos por status</h2>
  <?php if (empty($payments)): ?>
    <p class="muted">Sem dados de pagamentos.</p>
  <?php else: ?>
    <table class="data">
      <thead><tr><th>Status</th><th>Total</th><th>Valor (R$)</th></tr></thead>
      <tbody>
        <?php foreach ($payments as $p): ?>
          <tr>
            <td><?= htmlspecialchars((string) $p['status']) ?></td>
            <td><?= (int) $p['total'] ?></td>
            <td><?= number_format((float) ($p['amount'] ?? 0), 2, ',', '.') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
