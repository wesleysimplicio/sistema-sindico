<?php
/** @var string $title */
/** @var string|null $error */
/** @var string $csrf */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($title) ?></title>
<link rel="stylesheet" href="/assets/app.css">
</head>
<body class="auth">
  <main class="auth-wrap">
    <div class="auth-card">
      <h1>Sistema Sindico</h1>
      <p class="muted">Crie uma nova senha para sua conta.</p>
      <?php if (!empty($error)): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="post" action="/reset-password" novalidate>
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
        <label>
          Nova senha
          <input type="password" name="new_password" required autofocus autocomplete="new-password"
                 minlength="8" placeholder="Minimo 8 caracteres">
        </label>
        <label>
          Confirmar nova senha
          <input type="password" name="confirm_password" required autocomplete="new-password"
                 minlength="8" placeholder="Repita a senha">
        </label>
        <button type="submit" class="btn primary">Salvar nova senha</button>
      </form>
      <p class="muted small" style="margin-top:12px;">
        A senha deve ter no minimo 8 caracteres, com pelo menos uma letra e um numero.
      </p>
      <p class="muted small">
        <a href="/login" class="link-muted">Cancelar</a>
      </p>
    </div>
  </main>
</body>
</html>
