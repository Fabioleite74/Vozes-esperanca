<?php
// Cria uma música de exemplo (com áudios de teste e uma partitura simples) no banco local.
// Uso: php scripts/exemplo.php     — apenas para desenvolvimento.
declare(strict_types=1);
require dirname(__DIR__) . '/inc/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    exit('Execute pelo terminal.');
}

/** Gera um WAV curto com um tom simples. */
function gerar_wav(string $caminho, float $freq): void
{
    $taxa = 8000;
    $amostras = $taxa * 2;
    $dados = '';
    for ($i = 0; $i < $amostras; $i++) {
        $dados .= pack('v', (int) (6000 * sin(2 * M_PI * $freq * $i / $taxa)) & 0xFFFF);
    }
    $cab = 'RIFF' . pack('V', 36 + strlen($dados)) . 'WAVEfmt ' . pack('VvvVVvv', 16, 1, 1, $taxa, $taxa * 2, 2, 16)
        . 'data' . pack('V', strlen($dados));
    file_put_contents($caminho, $cab . $dados);
}

$pdf = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
    . "3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]/Contents 4 0 R/Resources<</Font<</F1 5 0 R>>>>>>endobj\n"
    . "4 0 obj<</Length 58>>stream\nBT /F1 24 Tf 100 750 Td (Partitura de exemplo) Tj ET\nendstream endobj\n"
    . "5 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF";

$pdo = db();
$pdo->prepare('INSERT INTO musicas (titulo, autor, descricao) VALUES (?, ?, ?)')
    ->execute(['Música de exemplo', 'Arranjo de teste', 'Áudios de teste com tons simples. Apague esta música quando cadastrar as reais.']);
$id = (int) $pdo->lastInsertId();

foreach (['soprano' => 523.25, 'contralto' => 392.0, 'tenor' => 329.63, 'baixo' => 196.0] as $voz => $freq) {
    $nome = bin2hex(random_bytes(12)) . '.wav';
    gerar_wav(UPLOAD_DIR . '/' . $nome, $freq);
    $pdo->prepare('INSERT INTO audios (musica_id, voz, arquivo) VALUES (?, ?, ?)')->execute([$id, $voz, $nome]);
}
$nome = bin2hex(random_bytes(12)) . '.pdf';
file_put_contents(UPLOAD_DIR . '/' . $nome, $pdf);
$pdo->prepare('UPDATE musicas SET partitura = ? WHERE id = ?')->execute([$nome, $id]);

echo "Música de exemplo criada (id $id). Abra http://localhost:8000/musica.php?id=$id\n";
