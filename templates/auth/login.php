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
      <p class="muted">Entre para acessar o painel.</p>
      <?php if (!empty($error)): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="post" action="/login" novalidate>
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
        <label>
          Email
          <input type="email" name="email" required autofocus>
        </label>
        <label>
          Senha
          <input type="password" name="password" required>
        </label>
        <button type="submit" class="btn primary">Entrar</button>
      </form>
      <p class="muted small">Use as credenciais semeadas em <code>database/seed.sql</code>.</p>
    </div>
  </main>
</body>
</html>
