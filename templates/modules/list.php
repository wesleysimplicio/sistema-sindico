<?php
/** @var string $moduleTitle */
/** @var string $description */
/** @var string $apiEndpoint */
/** @var array<int,array<string,mixed>> $rows */
/** @var array<int,array{key:string,label:string,format?:string}> $columns */
?>
<div class="card">
  <div class="row between">
    <div>
      <h2><?= htmlspecialchars($moduleTitle) ?></h2>
      <p class="muted"><?= htmlspecialchars($description) ?></p>
    </div>
    <div class="muted small">API: <code><?= htmlspecialchars($apiEndpoint) ?></code></div>
  </div>
</div>

<div class="card">
  <?php if (empty($rows)): ?>
    <p class="muted">Sem registros.</p>
  <?php else: ?>
    <table class="data">
      <thead>
        <tr>
          <?php foreach ($columns as $col): ?>
            <th><?= htmlspecialchars($col['label']) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
          <tr>
            <?php foreach ($columns as $col): ?>
              <?php $val = $row[$col['key']] ?? null; ?>
              <td>
                <?php if (($col['format'] ?? '') === 'money'): ?>
                  R$ <?= number_format((float) $val, 2, ',', '.') ?>
                <?php elseif (($col['format'] ?? '') === 'date'): ?>
                  <?= htmlspecialchars(substr((string) $val, 0, 10)) ?>
                <?php elseif (($col['format'] ?? '') === 'datetime'): ?>
                  <?= htmlspecialchars(substr((string) $val, 0, 16)) ?>
                <?php else: ?>
                  <?= htmlspecialchars((string) ($val ?? '')) ?>
                <?php endif; ?>
              </td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
