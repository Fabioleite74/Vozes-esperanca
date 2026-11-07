<?php
declare(strict_types=1);

/**
 * Cabeçalho comum.
 * @param string $base prefixo relativo até a raiz do site ('' na raiz, '../' no admin)
 */
function cabecalho(string $titulo, string $base = '', bool $admin = false): void
{
    $u = $admin ? usuario_logado() : null;
    ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulo) ?> · <?= e(SITE_NOME) ?></title>
<link rel="stylesheet" href="<?= $base ?>assets/style.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎵</text></svg>">
</head>
<body class="<?= $admin ? 'admin' : 'publico' ?>">
<header class="topo">
  <div class="container topo-inner">
    <a class="marca" href="<?= $base ?>index.php">
      <span class="marca-icone" aria-hidden="true">♪</span>
      <span>
        <strong><?= e(SITE_NOME) ?></strong>
        <small><?= $admin ? 'Área administrativa' : 'Áudios por naipe e partituras' ?></small>
      </span>
    </a>
    <nav class="menu">
      <?php if ($admin && $u): ?>
        <a href="index.php">Músicas</a>
        <a href="editar.php">+ Nova música</a>
        <a href="senha.php">Minha senha</a>
        <a href="../index.php" target="_blank">Ver site</a>
        <a href="logout.php">Sair</a>
      <?php elseif (!$admin): ?>
        <a href="index.php">Repertório</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="container">
<?php foreach (flashes() as [$tipo, $msg]): ?>
  <div class="alerta alerta-<?= e($tipo) ?>"><?= e($msg) ?></div>
<?php endforeach;
}

function rodape(string $base = ''): void
{
    ?>
</main>
<footer class="rodape">
  <div class="container">
    © <?= date('Y') ?> <?= e(SITE_NOME) ?>
  </div>
</footer>
<script src="<?= $base ?>assets/app.js"></script>
</body>
</html>
<?php
}
