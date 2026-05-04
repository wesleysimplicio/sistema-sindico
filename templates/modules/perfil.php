<?php
/** @var array|null $user */
/** @var array|null $condo */
/** @var array|null $unit */
?>
<div class="card">
  <h2>Perfil</h2>
  <?php if ($user === null): ?>
    <p class="muted">Usuario nao autenticado.</p>
  <?php else: ?>
    <table class="data">
      <tbody>
        <tr><th>Nome</th>     <td><?= htmlspecialchars((string) $user['name']) ?></td></tr>
        <tr><th>Email</th>    <td><?= htmlspecialchars((string) $user['email']) ?></td></tr>
        <tr><th>Telefone</th> <td><?= htmlspecialchars((string) ($user['phone'] ?? '-')) ?></td></tr>
        <tr><th>Perfil</th>   <td><?= htmlspecialchars((string) $user['role']) ?></td></tr>
        <?php if ($condo): ?>
          <tr><th>Condominio</th><td><?= htmlspecialchars((string) $condo['name']) ?></td></tr>
        <?php endif; ?>
        <?php if ($unit): ?>
          <tr><th>Unidade</th><td>Bloco <?= htmlspecialchars((string) $unit['block']) ?> &middot; <?= htmlspecialchars((string) $unit['number']) ?></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Sessao</h2>
  <p><a href="/logout" class="btn">Sair</a></p>
</div>
