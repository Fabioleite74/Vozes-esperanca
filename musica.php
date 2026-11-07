<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/layout.php';

$m = buscar_musica((int) ($_GET['id'] ?? 0));
if (!$m) {
    http_response_code(404);
    cabecalho('Música não encontrada');
    echo '<p class="vazio">Música não encontrada. <a href="index.php">Voltar ao repertório</a></p>';
    rodape();
    exit;
}
$base = slug($m['titulo']);

cabecalho($m['titulo']);
?>
<p class="voltar"><a href="index.php">← Voltar ao repertório</a></p>

<article class="musica">
  <header>
    <h1><?= e($m['titulo']) ?></h1>
    <?php if ($m['autor'] !== ''): ?><p class="autor"><?= e($m['autor']) ?></p><?php endif; ?>
    <?php if ($m['descricao'] !== ''): ?><p class="descricao"><?= nl2br(e($m['descricao'])) ?></p><?php endif; ?>
  </header>

  <section class="bloco">
    <h2>Áudios por naipe</h2>
    <?php if (!$m['audios']): ?>
      <p class="vazio">Nenhum áudio disponível para esta música.</p>
    <?php else: ?>
      <div class="vozes">
        <?php foreach (VOZES as $chave => $rotulo):
            if (empty($m['audios'][$chave])) continue;
            $arq = $m['audios'][$chave];
            $url = 'uploads/' . rawurlencode($arq); ?>
          <div class="voz voz-<?= e($chave) ?>">
            <div class="voz-topo">
              <h3><?= e($rotulo) ?></h3>
              <a class="botao botao-sec" href="<?= $url ?>" download="<?= e($base . '-' . $chave . '.' . extensao($arq)) ?>">
                ⬇ Baixar <small>(<?= e(tamanho_legivel($arq)) ?>)</small>
              </a>
            </div>
            <audio controls preload="none" src="<?= $url ?>"></audio>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="bloco">
    <h2>Partitura</h2>
    <?php if (!$m['partitura']): ?>
      <p class="vazio">Partitura não disponível.</p>
    <?php else:
        $pdf = 'uploads/' . rawurlencode($m['partitura']); ?>
      <div class="acoes">
        <a class="botao" href="<?= $pdf ?>" target="_blank" rel="noopener">👁 Abrir em nova aba</a>
        <a class="botao botao-sec" href="<?= $pdf ?>" download="<?= e($base . '-partitura.pdf') ?>">
          ⬇ Baixar PDF <small>(<?= e(tamanho_legivel($m['partitura'])) ?>)</small>
        </a>
      </div>
      <object class="visualizador-pdf" data="<?= $pdf ?>" type="application/pdf">
        <p>Seu navegador não exibe PDFs aqui. <a href="<?= $pdf ?>" target="_blank" rel="noopener">Clique para abrir a partitura</a>.</p>
      </object>
    <?php endif; ?>
  </section>
</article>
<?php
rodape();
