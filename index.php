<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/layout.php';

$q = trim((string) ($_GET['q'] ?? ''));

$sql = 'SELECT m.*, (SELECT group_concat(voz) FROM audios a WHERE a.musica_id = m.id) AS vozes FROM musicas m';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE m.titulo LIKE ? OR m.autor LIKE ?';
    $params = ["%$q%", "%$q%"];
}
$sql .= ' ORDER BY m.titulo COLLATE NOCASE';
$st = db()->prepare($sql);
$st->execute($params);
$musicas = $st->fetchAll();
$total = count($musicas);

cabecalho('Repertório');
?>
<section class="hero">
  <div class="hero-texto">
    <span class="manuscrito">Arte na Casa Espírita</span>
    <h1>Ouça, estude e cante com a gente</h1>
    <p>Aqui ficam os áudios de cada naipe (soprano, contralto, tenor e baixo) e as partituras do nosso repertório. Escolha uma música para ensaiar em casa.</p>
    <form class="busca" method="get" role="search">
      <label for="busca" class="sr-only">Buscar música</label>
      <input id="busca" type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por título ou autor…">
      <button type="submit"><?= icone('busca') ?> Buscar</button>
    </form>
  </div>
  <div class="hero-imagem">
    <img src="assets/img/arte-coral.webp" width="640" height="640" alt="Ilustração do Coral Vozes da Esperança: coralistas de várias idades cantando com partituras nas mãos">
  </div>
</section>

<section aria-labelledby="t-repertorio">
  <div class="secao-titulo">
    <h2 id="t-repertorio"><?= $q !== '' ? 'Resultados para “' . e($q) . '”' : 'Repertório' ?></h2>
    <span><?= $total ?> música<?= $total === 1 ? '' : 's' ?></span>
  </div>

<?php if (!$musicas): ?>
  <p class="vazio">
    <?= $q !== '' ? 'Nenhuma música encontrada. Tente outro título ou <a href="index.php">veja o repertório completo</a>.' : 'Nenhuma música cadastrada ainda.' ?>
  </p>
<?php else: ?>
  <ul class="lista-musicas">
    <?php foreach ($musicas as $m):
        $tem = $m['vozes'] ? explode(',', $m['vozes']) : []; ?>
      <li>
        <a class="cartao" href="musica.php?id=<?= (int) $m['id'] ?>">
          <span>
            <h2><?= e($m['titulo']) ?></h2>
            <?php if ($m['autor'] !== ''): ?><p class="autor"><?= e($m['autor']) ?></p><?php endif; ?>
          </span>
          <?php if ($tem): ?>
            <span class="etiquetas">
              <?php foreach (VOZES as $chave => $rotulo):
                  if (in_array($chave, $tem, true)): ?>
                <span class="etiqueta voz-<?= e($chave) ?>"><?= e($rotulo) ?></span>
              <?php endif; endforeach; ?>
            </span>
          <?php endif; ?>
          <span class="cartao-rodape">
            <span><?php if ($m['partitura']): ?><?= icone('documento', 16) ?> Partitura em PDF<?php else: ?>Sem partitura<?php endif; ?></span>
            <strong>Abrir →</strong>
          </span>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
</section>
<?php
rodape();
