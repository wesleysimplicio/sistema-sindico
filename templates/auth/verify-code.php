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
      <p class="muted">Digite o codigo de 6 digitos enviado para o seu contato cadastrado. O codigo expira em 10 minutos.</p>
      <?php if (!empty($error)): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="post" action="/verify-code" novalidate>
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
        <label>
          Codigo de verificacao
          <input type="text" name="code" required autofocus autocomplete="one-time-code"
                 inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="000000">
        </label>
        <button type="submit" class="btn primary">Verificar codigo</button>
      </form>
      <p class="muted small" style="margin-top:12px;">
        <a href="/forgot-password" class="link-muted">Reenviar codigo</a>
        &nbsp;·&nbsp;
        <a href="/login" class="link-muted">Voltar para o login</a>
      </p>
    </div>
  </main>
</body>
</html>
