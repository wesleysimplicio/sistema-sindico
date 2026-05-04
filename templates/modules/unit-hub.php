<?php
/** @var array $unit */
/** @var array<int,array<string,mixed>> $residents */
/** @var array<int,array<string,mixed>> $vehicles */
/** @var array<int,array<string,mixed>> $contractors */
/** @var array<int,array<string,mixed>> $porterNotes */

$h = static fn(?string $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
?>
<div class="card">
  <h2>Unidade <?= $h($unit['block'] ?? null) ?> &middot; <?= $h($unit['number'] ?? null) ?></h2>
  <p class="muted">Andar <?= $h(isset($unit['floor']) ? (string) $unit['floor'] : null) ?> &middot; Tipo <?= $h($unit['type'] ?? null) ?></p>
  <p><a href="/unidades" class="btn">Voltar para unidades</a></p>
</div>

<div class="card">
  <h3>Residentes</h3>
  <?php if (empty($residents)): ?>
    <p class="muted">Nenhum residente cadastrado.</p>
  <?php else: ?>
    <table class="data">
      <thead>
        <tr>
          <th>Nome</th><th>Relacionamento</th><th>Responsavel</th><th>Documento</th><th>Nascimento</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($residents as $r): ?>
          <tr>
            <td><?= $h($r['full_name'] ?? null) ?></td>
            <td><?= $h($r['relationship'] ?? null) ?></td>
            <td><?= !empty($r['is_responsible']) ? 'Sim' : 'Nao' ?></td>
            <td><?= $h($r['document'] ?? null) ?></td>
            <td><?= $h($r['birth_date'] ?? null) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<div class="card">
  <h3>Veiculos</h3>
  <?php if (empty($vehicles)): ?>
    <p class="muted">Nenhum veiculo cadastrado.</p>
  <?php else: ?>
    <table class="data">
      <thead>
        <tr>
          <th>Placa</th><th>Tipo</th><th>Marca</th><th>Modelo</th><th>Cor</th><th>Vaga</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($vehicles as $v): ?>
          <tr>
            <td><?= $h($v['plate'] ?? null) ?></td>
            <td><?= $h($v['vehicle_type'] ?? null) ?></td>
            <td><?= $h($v['brand'] ?? null) ?></td>
            <td><?= $h($v['model'] ?? null) ?></td>
            <td><?= $h($v['color'] ?? null) ?></td>
            <td><?= $h($v['parking_spot'] ?? null) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<div class="card">
  <h3>Prestadores</h3>
  <?php if (empty($contractors)): ?>
    <p class="muted">Nenhum prestador cadastrado.</p>
  <?php else: ?>
    <table class="data">
      <thead>
        <tr>
          <th>Nome</th><th>Servico</th><th>Status</th><th>Inicio</th><th>Fim</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($contractors as $c): ?>
          <tr>
            <td><?= $h($c['full_name'] ?? null) ?></td>
            <td><?= $h($c['service_type'] ?? null) ?></td>
            <td><?= $h($c['status'] ?? null) ?></td>
            <td><?= $h($c['access_starts_at'] ?? null) ?></td>
            <td><?= $h($c['access_ends_at'] ?? null) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<div class="card">
  <h3>Notas da portaria</h3>
  <?php if (empty($porterNotes)): ?>
    <p class="muted">Nenhuma nota.</p>
  <?php else: ?>
    <table class="data">
      <thead>
        <tr><th>Quando</th><th>Autor</th><th>Mensagem</th></tr>
      </thead>
      <tbody>
        <?php foreach ($porterNotes as $n): ?>
          <tr>
            <td><?= $h($n['created_at'] ?? null) ?></td>
            <td><?= $h($n['author_name'] ?? null) ?></td>
            <td><?= $h($n['body'] ?? null) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
