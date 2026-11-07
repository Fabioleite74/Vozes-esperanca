<?php
declare(strict_types=1);
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/layout.php';

$u = exigir_login();
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $atual = (string) ($_POST['atual'] ?? '');
    $nova = (string) ($_POST['nova'] ?? '');
    $st = db()->prepare('SELECT senha_hash FROM usuarios WHERE id = ?');
    $st->execute([$u['id']]);
    $hash = (string) $st->fetchColumn();

    if (!csrf_ok()) {
        $erro = 'Sessão expirada. Tente novamente.';
    } elseif (!password_verify($atual, $hash)) {
        $erro = 'A senha atual está incorreta.';
    } elseif (strlen($nova) < 8) {
        $erro = 'A nova senha deve ter pelo menos 8 caracteres.';
    } elseif ($nova !== ($_POST['nova2'] ?? '')) {
        $erro = 'As novas senhas não conferem.';
    } else {
        db()->prepare('UPDATE usuarios SET senha_hash = ? WHERE id = ?')
            ->execute([password_hash($nova, PASSWORD_DEFAULT), $u['id']]);
        flash('ok', 'Senha alterada com sucesso.');
        redirect('index.php');
    }
}

cabecalho('Minha senha', '../', true);
?>
<div class="caixa-login">
  <h1>Alterar senha</h1>
  <?php if ($erro): ?><div class="alerta alerta-erro"><?= e($erro) ?></div><?php endif; ?>
  <form method="post" class="formulario">
    <?= csrf_field() ?>
    <label>Senha atual <input type="password" name="atual" required autocomplete="current-password"></label>
    <label>Nova senha <input type="password" name="nova" required minlength="8" autocomplete="new-password"></label>
    <label>Repita a nova senha <input type="password" name="nova2" required minlength="8" autocomplete="new-password"></label>
    <button class="botao" type="submit">Salvar</button>
  </form>
</div>
<?php
rodape('../');
