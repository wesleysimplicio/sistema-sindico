<?php
/** @var string $moduleTitle */
/** @var string $description */
/** @var string $apiEndpoint */
?>
<div class="card">
  <h2><?= htmlspecialchars($moduleTitle) ?></h2>
  <p><?= htmlspecialchars($description) ?></p>
  <p class="muted">
    Tela placeholder. Layout final sera implementado a partir das referencias em
    <code>docs/print/</code>.
  </p>
</div>

<div class="card">
  <h2>Endpoint API</h2>
  <p>Consumo planejado pelo app mobile:</p>
  <p><code><?= htmlspecialchars($apiEndpoint) ?></code></p>
  <p class="muted">Resposta atual e um stub com envelope JSON consistente.</p>
</div>

<div class="card">
  <h2>Proximos passos</h2>
  <ul>
    <li>Definir colunas reais da listagem.</li>
    <li>Implementar formulario de criacao/edicao.</li>
    <li>Conectar endpoint a persistencia em MySQL.</li>
    <li>Refinar visual com base em <code>docs/print/</code>.</li>
  </ul>
</div>
