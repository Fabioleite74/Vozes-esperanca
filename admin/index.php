<?php
declare(strict_types=1);
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/layout.php';

exigir_login();

$musicas = db()->query('SELECT m.id, m.titulo, m.autor, m.partitura, m.atualizado_em,
        (SELECT group_concat(voz) FROM audios a WHERE a.musica_id = m.id) AS vozes
    FROM musicas m ORDER BY m.titulo COLLATE NOCASE')->fetchAll();

cabecalho('Músicas', '../', true);
?>
<div class="titulo-acoes">
  <h1>Músicas cadastradas</h1>
  <a class="botao botao-verde" href="editar.php"><?= icone('mais') ?> Nova música</a>
</div>

<?php if (!$musicas): ?>
  <p class="vazio">Nenhuma música ainda. Clique em <strong>Nova música</strong> para começar.</p>
<?php else: ?>
  <div class="tabela-rolagem">
  <table class="tabela">
    <thead>
      <tr>
        <th>Título</th>
        <?php foreach (VOZES as $chave => $rotulo): ?><th class="centro voz-<?= e($chave) ?>"><?= e($rotulo) ?></th><?php endforeach; ?>
        <th class="centro">Partitura</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($musicas as $m):
          $tem = $m['vozes'] ? explode(',', $m['vozes']) : []; ?>
        <tr>
          <td>
            <strong><?= e($m['titulo']) ?></strong>
            <?php if ($m['autor'] !== ''): ?><br><small><?= e($m['autor']) ?></small><?php endif; ?>
          </td>
          <?php foreach (VOZES as $chave => $rotulo): ?>
            <td class="centro"><?= in_array($chave, $tem, true) ? '<span class="sim" title="Enviado" aria-label="Enviado">✓</span>' : '<span class="nao">Falta</span>' ?></td>
          <?php endforeach; ?>
          <td class="centro"><?= $m['partitura'] ? '<span class="sim" title="Enviada" aria-label="Enviada">✓</span>' : '<span class="nao">Falta</span>' ?></td>
          <td class="acoes-linha">
            <a class="botao botao-peq" href="editar.php?id=<?= (int) $m['id'] ?>">Editar</a>
            <a class="botao botao-peq botao-sec" href="../musica.php?id=<?= (int) $m['id'] ?>" target="_blank">Ver</a>
            <form method="post" action="excluir.php" data-confirmar="Excluir “<?= e($m['titulo']) ?>” e todos os seus arquivos?">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
              <button class="botao botao-peq botao-perigo" type="submit">Excluir</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
<?php endif;

rodape('../');
