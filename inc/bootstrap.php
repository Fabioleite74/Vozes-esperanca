<?php
/**
 * Coral Vozes da Esperança — núcleo da aplicação.
 * Configuração, banco de dados (SQLite), sessão, autenticação e utilitários.
 */
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
define('DATA_DIR', APP_ROOT . '/data');
define('UPLOAD_DIR', APP_ROOT . '/uploads');
define('DB_FILE', DATA_DIR . '/coral.sqlite');

/** Nome exibido no site. */
const SITE_NOME = 'Coral Vozes da Esperança';

/** Naipes disponíveis (chave interna => rótulo). Para incluir outro, basta adicionar aqui. */
const VOZES = [
    'soprano'  => 'Soprano',
    'contralto' => 'Contralto',
    'tenor'    => 'Tenor',
    'baixo'    => 'Baixo',
];

const AUDIO_EXTS = ['mp3', 'm4a', 'aac', 'ogg', 'oga', 'opus', 'wav', 'webm', 'flac'];

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_name('coral_sess');
    session_start();
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }
    foreach ([DATA_DIR, UPLOAD_DIR] as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }
    $pdo = new PDO('sqlite:' . DB_FILE);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('CREATE TABLE IF NOT EXISTS usuarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario TEXT NOT NULL UNIQUE,
        senha_hash TEXT NOT NULL
    )');
    $pdo->exec('CREATE TABLE IF NOT EXISTS musicas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        titulo TEXT NOT NULL,
        autor TEXT NOT NULL DEFAULT \'\',
        descricao TEXT NOT NULL DEFAULT \'\',
        partitura TEXT,
        criado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');
    $pdo->exec('CREATE TABLE IF NOT EXISTS audios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        musica_id INTEGER NOT NULL REFERENCES musicas(id) ON DELETE CASCADE,
        voz TEXT NOT NULL,
        arquivo TEXT NOT NULL,
        UNIQUE (musica_id, voz)
    )');
    return $pdo;
}

/** Escapa texto para HTML. */
function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/* ---------- Mensagens rápidas (flash) ---------- */

function flash(string $tipo, string $msg): void
{
    $_SESSION['flash'][] = [$tipo, $msg];
}

function flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_ok(): bool
{
    return isset($_POST['csrf']) && hash_equals(csrf_token(), (string) $_POST['csrf']);
}

/* ---------- Autenticação ---------- */

function usuario_logado(): ?array
{
    if (empty($_SESSION['uid'])) {
        return null;
    }
    $st = db()->prepare('SELECT id, usuario FROM usuarios WHERE id = ?');
    $st->execute([$_SESSION['uid']]);
    return $st->fetch() ?: null;
}

function exigir_login(): array
{
    $u = usuario_logado();
    if (!$u) {
        redirect('login.php');
    }
    return $u;
}

function existe_usuario(): bool
{
    return (int) db()->query('SELECT COUNT(*) FROM usuarios')->fetchColumn() > 0;
}

/* ---------- Músicas ---------- */

function buscar_musica(int $id): ?array
{
    $st = db()->prepare('SELECT * FROM musicas WHERE id = ?');
    $st->execute([$id]);
    $m = $st->fetch();
    if (!$m) {
        return null;
    }
    $m['audios'] = audios_da_musica($id);
    return $m;
}

/** @return array<string,string> voz => arquivo */
function audios_da_musica(int $id): array
{
    $st = db()->prepare('SELECT voz, arquivo FROM audios WHERE musica_id = ?');
    $st->execute([$id]);
    $out = [];
    foreach ($st->fetchAll() as $r) {
        $out[$r['voz']] = $r['arquivo'];
    }
    return $out;
}

/** Transforma um texto em nome de arquivo amigável ("Ave Maria" => "ave-maria"). */
function slug(string $s): string
{
    $t = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    $t = strtolower((string) $t);
    $t = preg_replace('/[^a-z0-9]+/', '-', $t);
    $t = trim((string) $t, '-');
    return $t !== '' ? $t : 'arquivo';
}

function extensao(string $arquivo): string
{
    return strtolower(pathinfo($arquivo, PATHINFO_EXTENSION));
}

/* ---------- Upload ---------- */

/**
 * Valida e grava um arquivo enviado. Retorna o nome gravado em uploads/.
 * @param 'audio'|'pdf' $tipo
 * @throws RuntimeException com mensagem amigável
 */
function salvar_upload(array $f, string $tipo): string
{
    if ($f['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException(match ($f['error']) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE =>
                'o arquivo "' . $f['name'] . '" é maior que o limite do servidor (' . ini_get('upload_max_filesize') . ').',
            UPLOAD_ERR_PARTIAL => 'o envio de "' . $f['name'] . '" foi interrompido. Tente novamente.',
            default => 'não foi possível receber "' . $f['name'] . '" (código ' . $f['error'] . ').',
        });
    }
    if (!is_uploaded_file($f['tmp_name'])) {
        throw new RuntimeException('envio inválido.');
    }

    $ext = extensao($f['name']);
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) ?: '';

    if ($tipo === 'pdf') {
        if ($ext !== 'pdf' || $mime !== 'application/pdf') {
            throw new RuntimeException('"' . $f['name'] . '" não é um PDF válido.');
        }
    } else {
        $mimeOk = str_starts_with($mime, 'audio/')
            || in_array($mime, ['video/mp4', 'video/webm', 'application/ogg', 'application/octet-stream'], true);
        if (!in_array($ext, AUDIO_EXTS, true) || !$mimeOk) {
            throw new RuntimeException('"' . $f['name'] . '" não é um arquivo de áudio aceito (' . implode(', ', AUDIO_EXTS) . ').');
        }
    }

    $nome = bin2hex(random_bytes(12)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . '/' . $nome)) {
        throw new RuntimeException('falha ao gravar "' . $f['name'] . '" no servidor. Verifique as permissões da pasta uploads/.');
    }
    @chmod(UPLOAD_DIR . '/' . $nome, 0644);
    return $nome;
}

function apagar_arquivo(?string $nome): void
{
    if ($nome && basename($nome) === $nome && is_file(UPLOAD_DIR . '/' . $nome)) {
        @unlink(UPLOAD_DIR . '/' . $nome);
    }
}

function tamanho_legivel(string $nome): string
{
    $p = UPLOAD_DIR . '/' . $nome;
    if (!is_file($p)) {
        return '';
    }
    $b = (float) filesize($p);
    $u = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($b >= 1024 && $i < count($u) - 1) {
        $b /= 1024;
        $i++;
    }
    return number_format($b, $i ? 1 : 0, ',', '.') . ' ' . $u[$i];
}
