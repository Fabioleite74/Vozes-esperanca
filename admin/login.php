<?php
declare(strict_types=1);
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/layout.php';

if (usuario_logado()) {
    redirect('index.php');
}

$primeiroAcesso = !existe_usuario();
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim((string) ($_POST['usuario'] ?? ''));
    $senha = (string) ($_POST['senha'] ?? '');

    if (!csrf_ok()) {
        $erro = 'Sessão expirada. Recarregue a página e tente novamente.';
    } elseif ($primeiroAcesso) {
        $senha2 = (string) ($_POST['senha2'] ?? '');
        if ($usuario === '' || strlen($senha) < 8) {
            $erro = 'Informe um usuário e uma senha com pelo menos 8 caracteres.';
        } elseif ($senha !== $senha2) {
            $erro = 'As senhas não conferem.';
        } else {
            db()->prepare('INSERT INTO usuarios (usuario, senha_hash) VALUES (?, ?)')
                ->execute([$usuario, password_hash($senha, PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            $_SESSION['uid'] = (int) db()->lastInsertId();
            flash('ok', 'Administrador criado. Bem-vindo(a)!');
            redirect('index.php');
        }
    } else {
        $st = db()->prepare('SELECT id, senha_hash FROM usuarios WHERE usuario = ?');
        $st->execute([$usuario]);
        $u = $st->fetch();
        if ($u && password_verify($senha, $u['senha_hash'])) {
            session_regenerate_id(true);
            $_SESSION['uid'] = (int) $u['id'];
            redirect('index.php');
        }
        sleep(1); // dificulta tentativas em massa
        $erro = 'Usuário ou senha incorretos.';
    }
}

cabecalho($primeiroAcesso ? 'Criar administrador' : 'Entrar', '../', true);
?>
<div class="caixa-login">
  <h1><?= $primeiroAcesso ? 'Primeiro acesso' : 'Entrar' ?></h1>
  <?php if ($primeiroAcesso): ?>
    <p>Nenhum administrador cadastrado ainda. Crie agora o seu usuário de acesso.</p>
  <?php endif; ?>
  <?php if ($erro): ?><div class="alerta alerta-erro"><?= e($erro) ?></div><?php endif; ?>
  <form method="post" class="formulario">
    <?= csrf_field() ?>
    <label>Usuário
      <input name="usuario" required autofocus autocomplete="username" value="<?= e($_POST['usuario'] ?? '') ?>">
    </label>
    <label>Senha
      <input type="password" name="senha" required autocomplete="<?= $primeiroAcesso ? 'new-password' : 'current-password' ?>" <?= $primeiroAcesso ? 'minlength="8"' : '' ?>>
    </label>
    <?php if ($primeiroAcesso): ?>
      <label>Repita a senha
        <input type="password" name="senha2" required minlength="8" autocomplete="new-password">
      </label>
    <?php endif; ?>
    <button type="submit" class="botao"><?= $primeiroAcesso ? 'Criar e entrar' : 'Entrar' ?></button>
  </form>
</div>
<?php
rodape('../');
