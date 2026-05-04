<?php
/** @var array|null $user */
/** @var array|null $condo */
/** @var array<int,array> $deliveriesToday */
/** @var array<int,array> $expectedVisitors */
?>
<div class="card">
  <h2>Portaria <?php if ($condo): ?>&mdash; <?= htmlspecialchars($condo['name']) ?><?php endif; ?></h2>
  <p class="muted">Olá, <?= htmlspecialchars($user['name'] ?? 'porteiro') ?>. Aqui está o resumo do seu turno.</p>
</div>

<div class="grid stats">
  <div class="stat info">
    <div class="value"><?= count($deliveriesToday) ?></div>
    <div class="label">Encomendas hoje</div>
  </div>
  <div class="stat warn">
    <div class="value"><?= count($expectedVisitors) ?></div>
    <div class="label">Visitantes esperados</div>
  </div>
</div>

<div class="card">
  <div class="row between" style="margin-bottom:12px">
    <h2 style="margin:0">Encomendas recebidas hoje</h2>
    <a class="btn" href="/encomendas">Todas as encomendas</a>
  </div>
  <?php if (empty($deliveriesToday)): ?>
    <p class="muted">Nenhuma encomenda recebida hoje.</p>
  <?php else: ?>
    <table class="data">
      <thead>
        <tr>
          <th>Remetente</th>
          <th>Unidade</th>
          <th>Status</th>
          <th>Recebido em</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($deliveriesToday as $d): ?>
          <tr>
            <td><?= htmlspecialchars($d['sender'] ?? '-') ?></td>
            <td>
              <?php
              $bloco  = htmlspecialchars((string) ($d['block']       ?? ''));
              $unid   = htmlspecialchars((string) ($d['unit_number'] ?? ''));
              echo $bloco !== '' ? "Bl. $bloco / Unid. $unid" : ($unid !== '' ? "Unid. $unid" : '-');
              ?>
            </td>
            <td><?= htmlspecialchars((string) ($d['status'] ?? '')) ?></td>
            <td><?= htmlspecialchars((string) ($d['received_at'] ?? '')) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<div class="card">
  <div class="row between" style="margin-bottom:12px">
    <h2 style="margin:0">Visitantes esperados</h2>
    <a class="btn" href="/visitantes">Todos os visitantes</a>
  </div>
  <?php if (empty($expectedVisitors)): ?>
    <p class="muted">Nenhum visitante esperado no momento.</p>
  <?php else: ?>
    <table class="data">
      <thead>
        <tr>
          <th>Visitante</th>
          <th>Anfitrião</th>
          <th>Unidade</th>
          <th>Previsão de chegada</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($expectedVisitors as $v): ?>
          <tr>
            <td><?= htmlspecialchars($v['name'] ?? '-') ?></td>
            <td><?= htmlspecialchars($v['host_name'] ?? '-') ?></td>
            <td>
              <?php
              $bloco = htmlspecialchars((string) ($v['block']       ?? ''));
              $unid  = htmlspecialchars((string) ($v['unit_number'] ?? ''));
              echo $bloco !== '' ? "Bl. $bloco / Unid. $unid" : ($unid !== '' ? "Unid. $unid" : '-');
              ?>
            </td>
            <td><?= htmlspecialchars((string) ($v['expected_at'] ?? '')) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
