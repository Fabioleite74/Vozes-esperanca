<?php
declare(strict_types=1);
require __DIR__ . '/../inc/bootstrap.php';

exigir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_ok()) {
    flash('erro', 'Requisição inválida.');
    redirect('index.php');
}

$m = buscar_musica((int) ($_POST['id'] ?? 0));
if ($m) {
    foreach ($m['audios'] as $arq) {
        apagar_arquivo($arq);
    }
    apagar_arquivo($m['partitura']);
    db()->prepare('DELETE FROM musicas WHERE id = ?')->execute([$m['id']]);
    flash('ok', 'Música “' . $m['titulo'] . '” excluída.');
}
redirect('index.php');
