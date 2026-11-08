<?php
declare(strict_types=1);
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/layout.php';

exigir_login();

$id = (int) ($_GET['id'] ?? 0);
$m = $id ? buscar_musica($id) : null;
if ($id && !$m) {
    flash('erro', 'Música não encontrada.');
    redirect('index.php');
}
$m ??= ['id' => 0, 'titulo' => '', 'autor' => '', 'descricao' => '', 'partitura' => null, 'audios' => []];
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Quando o envio ultrapassa post_max_size, o PHP descarta todo o formulário.
    if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $erros[] = 'O envio total ultrapassou o limite do servidor (' . ini_get('post_max_size')
            . '). Envie menos arquivos por vez ou aumente o limite (veja o README).';
    } elseif (!csrf_ok()) {
        $erros[] = 'Sessão expirada. Recarregue a página e tente novamente.';
    } else {
        $m['titulo'] = trim((string) ($_POST['titulo'] ?? ''));
        $m['autor'] = trim((string) ($_POST['autor'] ?? ''));
        $m['descricao'] = trim((string) ($_POST['descricao'] ?? ''));

        if ($m['titulo'] === '') {
            $erros[] = 'Informe o título da música.';
        } else {
            $pdo = db();
            if ($m['id']) {
                $pdo->prepare('UPDATE musicas SET titulo = ?, autor = ?, descricao = ?, atualizado_em = CURRENT_TIMESTAMP WHERE id = ?')
                    ->execute([$m['titulo'], $m['autor'], $m['descricao'], $m['id']]);
            } else {
                $pdo->prepare('INSERT INTO musicas (titulo, autor, descricao) VALUES (?, ?, ?)')
                    ->execute([$m['titulo'], $m['autor'], $m['descricao']]);
                $m['id'] = (int) $pdo->lastInsertId();
            }

            // Áudios por naipe
            foreach (VOZES as $chave => $rotulo) {
                $atual = $m['audios'][$chave] ?? null;
                $f = $_FILES['audio_' . $chave] ?? null;
                $enviou = $f && $f['error'] !== UPLOAD_ERR_NO_FILE;

                if ($enviou) {
                    try {
                        $novo = salvar_upload($f, 'audio');
                        $pdo->prepare('INSERT INTO audios (musica_id, voz, arquivo) VALUES (?, ?, ?)
                                ON CONFLICT (musica_id, voz) DO UPDATE SET arquivo = excluded.arquivo')
                            ->execute([$m['id'], $chave, $novo]);
                        apagar_arquivo($atual);
                    } catch (RuntimeException $ex) {
                        $erros[] = $rotulo . ': ' . $ex->getMessage();
                    }
                } elseif ($atual && !empty($_POST['remover_' . $chave])) {
                    $pdo->prepare('DELETE FROM audios WHERE musica_id = ? AND voz = ?')->execute([$m['id'], $chave]);
                    apagar_arquivo($atual);
                }
            }

            // Partitura
            $f = $_FILES['partitura'] ?? null;
            if ($f && $f['error'] !== UPLOAD_ERR_NO_FILE) {
                try {
                    $novo = salvar_upload($f, 'pdf');
                    $pdo->prepare('UPDATE musicas SET partitura = ? WHERE id = ?')->execute([$novo, $m['id']]);
                    apagar_arquivo($m['partitura']);
                } catch (RuntimeException $ex) {
                    $erros[] = 'Partitura: ' . $ex->getMessage();
                }
            } elseif ($m['partitura'] && !empty($_POST['remover_partitura'])) {
                $pdo->prepare('UPDATE musicas SET partitura = NULL WHERE id = ?')->execute([$m['id']]);
                apagar_arquivo($m['partitura']);
            }

            foreach ($erros as $err) {
                flash('erro', $err);
            }
            flash('ok', $erros ? 'Música salva, mas alguns arquivos não foram aceitos.' : 'Música salva com sucesso.');
            redirect('editar.php?id=' . $m['id']);
        }
    }
}

cabecalho($m['id'] ? 'Editar música' : 'Nova música', '../', true);
?>
<p class="voltar"><a href="index.php">← Voltar à lista</a></p>
<h1><?= $m['id'] ? 'Editar: ' . e($m['titulo']) : 'Nova música' ?></h1>

<?php foreach ($erros as $err): ?><div class="alerta alerta-erro"><?= e($err) ?></div><?php endforeach; ?>

<form method="post" enctype="multipart/form-data" class="formulario formulario-musica">
  <?= csrf_field() ?>

  <fieldset>
    <legend>Dados da música</legend>
    <label>Título *
      <input name="titulo" required maxlength="200" value="<?= e($m['titulo']) ?>">
    </label>
    <label>Autor / Arranjo
      <input name="autor" maxlength="200" value="<?= e($m['autor']) ?>" placeholder="Ex.: Letra e música de Fulano · Arr. Ciclano">
    </label>
    <label>Recado para os coralistas
      <textarea name="descricao" rows="3" placeholder="Tom, andamento, trechos para estudar antes do ensaio…"><?= e($m['descricao']) ?></textarea>
    </label>
  </fieldset>

  <fieldset>
    <legend>Áudios por naipe</legend>
    <p class="ajuda">Formatos aceitos: <?= e(strtoupper(implode(', ', AUDIO_EXTS))) ?>. Limite por arquivo neste servidor: <?= e(ini_get('upload_max_filesize')) ?>.</p>
    <div class="grade-arquivos">
      <?php foreach (VOZES as $chave => $rotulo):
          $atual = $m['audios'][$chave] ?? null; ?>
        <div class="campo-arquivo voz-<?= e($chave) ?>">
          <h3><?= e($rotulo) ?></h3>
          <?php if ($atual): ?>
            <audio controls preload="none" src="../uploads/<?= rawurlencode($atual) ?>"></audio>
            <label class="check"><input type="checkbox" name="remover_<?= e($chave) ?>" value="1"> Remover áudio atual</label>
            <span class="ajuda">Ou envie outro arquivo para substituir:</span>
          <?php else: ?>
            <span class="ajuda">Nenhum áudio enviado.</span>
          <?php endif; ?>
          <input type="file" name="audio_<?= e($chave) ?>" aria-label="Arquivo de áudio: <?= e($rotulo) ?>" accept="audio/*,.<?= implode(',.', AUDIO_EXTS) ?>">
        </div>
      <?php endforeach; ?>
    </div>
  </fieldset>

  <fieldset>
    <legend>Partitura (PDF)</legend>
    <div class="campo-arquivo">
      <?php if ($m['partitura']): ?>
        <p><a href="../uploads/<?= rawurlencode($m['partitura']) ?>" target="_blank">Ver partitura atual</a> (<?= e(tamanho_legivel($m['partitura'])) ?>)</p>
        <label class="check"><input type="checkbox" name="remover_partitura" value="1"> Remover partitura atual</label>
        <span class="ajuda">Ou envie outro PDF para substituir:</span>
      <?php else: ?>
        <span class="ajuda">Nenhuma partitura enviada.</span>
      <?php endif; ?>
      <input type="file" name="partitura" accept="application/pdf,.pdf" aria-label="Arquivo PDF da partitura">
    </div>
  </fieldset>

  <div class="acoes">
    <button type="submit" class="botao botao-verde">Salvar música</button>
    <a class="botao botao-sec" href="index.php">Cancelar</a>
  </div>
</form>
<?php
rodape('../');
