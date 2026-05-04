<?php
/** @var string $title */
/** @var string|null $error */
/** @var string|null $info */
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
      <p class="muted">Informe seu CPF ou documento cadastrado para receber o código de verificação.</p>
      <?php if (!empty($error)): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if (!empty($info)): ?>
        <div class="alert info"><?= htmlspecialchars($info) ?></div>
      <?php endif; ?>
      <form method="post" action="/forgot-password" novalidate>
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
        <label>
          CPF / Documento
          <input type="text" name="document" required autofocus autocomplete="off" placeholder="000.000.000-00">
        </label>
        <button type="submit" class="btn primary">Enviar código</button>
      </form>
      <p class="muted small" style="margin-top:12px;">
        <a href="/login" class="link-muted">Voltar para o login</a>
      </p>
    </div>
  </main>
</body>
</html>
