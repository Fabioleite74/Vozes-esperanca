<?php
declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/layout.php';

$q = trim((string) ($_GET['q'] ?? ''));

$sql = 'SELECT m.*, (SELECT COUNT(*) FROM audios a WHERE a.musica_id = m.id) AS qtd_audios FROM musicas m';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE m.titulo LIKE ? OR m.autor LIKE ?';
    $params = ["%$q%", "%$q%"];
}
$sql .= ' ORDER BY m.titulo COLLATE NOCASE';
$st = db()->prepare($sql);
$st->execute($params);
$musicas = $st->fetchAll();

cabecalho('Repertório');
?>
<section class="hero">
  <h1>Repertório</h1>
  <p>Ouça, estude e baixe os áudios de cada naipe e as partituras das músicas do coral.</p>
  <form class="busca" method="get" role="search">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por título ou autor…" aria-label="Buscar música">
    <button type="submit">Buscar</button>
  </form>
</section>

<?php if (!$musicas): ?>
  <p class="vazio">
    <?= $q !== '' ? 'Nenhuma música encontrada para "' . e($q) . '".' : 'Nenhuma música cadastrada ainda.' ?>
  </p>
<?php else: ?>
  <ul class="lista-musicas">
    <?php foreach ($musicas as $m): ?>
      <li>
        <a class="cartao" href="musica.php?id=<?= (int) $m['id'] ?>">
          <h2><?= e($m['titulo']) ?></h2>
          <?php if ($m['autor'] !== ''): ?><p class="autor"><?= e($m['autor']) ?></p><?php endif; ?>
          <p class="etiquetas">
            <?php if ($m['qtd_audios'] > 0): ?>
              <span class="etiqueta">🎧 <?= (int) $m['qtd_audios'] ?> áudio<?= $m['qtd_audios'] > 1 ? 's' : '' ?></span>
            <?php endif; ?>
            <?php if ($m['partitura']): ?><span class="etiqueta">📄 Partitura</span><?php endif; ?>
          </p>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif;

rodape();
