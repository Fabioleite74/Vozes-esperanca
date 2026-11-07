<?php
// Roteador apenas para desenvolvimento local:  php -S localhost:8000 router.php
// Reproduz as proteções dos arquivos .htaccess (que o servidor embutido ignora).
$path = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
if (preg_match('#^/(data|inc)(/|$)#', $path) || preg_match('#^/uploads/.*\.(php\d?|phtml|phar|html?|svg|js)$#i', $path)) {
    http_response_code(403);
    exit('Acesso negado');
}
return false;
