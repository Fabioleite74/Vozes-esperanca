<?php
// Verifica a sintaxe de todos os arquivos PHP do projeto.  Uso: php scripts/verificar.php
if (PHP_SAPI !== 'cli') {
    exit('Execute pelo terminal.');
}
$raiz = dirname(__DIR__);
$erros = 0;
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz, FilesystemIterator::SKIP_DOTS));
foreach ($it as $arq) {
    $p = $arq->getPathname();
    if ($arq->getExtension() !== 'php' || preg_match('#[/\\\\](\.git|data|uploads|vendor)[/\\\\]#', $p)) {
        continue;
    }
    exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($p) . ' 2>&1', $saida, $codigo);
    if ($codigo !== 0) {
        $erros++;
        echo implode(PHP_EOL, $saida), PHP_EOL;
    }
    $saida = [];
}
echo $erros ? "$erros arquivo(s) com erro.\n" : "Tudo certo: nenhum erro de sintaxe.\n";
exit($erros ? 1 : 0);
