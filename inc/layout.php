<?php
declare(strict_types=1);

/** Ícones de linha usados no site (traço 2px, cor do texto). */
function icone(string $nome, int $tam = 18): string
{
    $p = [
        'nota'      => '<path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>',
        'baixar'    => '<path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/>',
        'olho'      => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'documento' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>',
        'busca'     => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'mais'      => '<path d="M12 5v14M5 12h14"/>',
        'enviar'    => '<path d="M12 15V3"/><path d="m7 8 5-5 5 5"/><path d="M5 21h14"/>',
    ][$nome] ?? '';
    return '<svg class="icone" width="' . $tam . '" height="' . $tam . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Caveat:wght@700&display=swap">
<link rel="stylesheet" href="<?= $base ?>assets/style.css">
<link rel="icon" href="<?= $base ?>assets/img/icone.svg" type="image/svg+xml">
</head>
<body class="<?= $admin ? 'admin' : 'publico' ?>">
<header class="topo">
  <div class="container topo-inner">
    <a class="marca" href="<?= $admin ? 'index.php' : $base . 'index.php' ?>">
      <span class="marca-icone"><?= icone('nota', 22) ?></span>
      <span>
        <strong><?= e(SITE_NOME) ?></strong>
        <small><?= $admin ? 'Área administrativa' : e(SITE_INSTITUICAO) ?></small>
      </span>
    </a>
    <nav class="menu" aria-label="<?= $admin ? 'Administração' : 'Principal' ?>">
      <?php if ($admin && $u): ?>
        <a href="index.php">Músicas</a>
        <a href="editar.php">Nova música</a>
        <a href="senha.php">Minha senha</a>
        <a href="../index.php" target="_blank">Ver site</a>
        <a href="logout.php">Sair</a>
      <?php elseif (!$admin): ?>
        <a class="ativo" href="index.php">Repertório</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="container conteudo">
<?php foreach (flashes() as [$tipo, $msg]): ?>
  <div class="alerta alerta-<?= e($tipo) ?>" role="status"><?= e($msg) ?></div>
<?php endforeach;
}

function rodape(string $base = ''): void
{
    ?>
</main>
<footer class="rodape">
  <div class="container rodape-inner">
    <span><strong><?= e(SITE_NOME) ?></strong> · Arte na Casa Espírita</span>
    <span><?= e(SITE_INSTITUICAO) ?></span>
  </div>
</footer>
<script src="<?= $base ?>assets/app.js"></script>
</body>
</html>
<?php
}
