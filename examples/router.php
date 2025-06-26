<?php
// Router para o servidor PHP built-in
// Use: php -S localhost:8000 router.php

$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// Remove query string da URL
$cleanPath = strtok($path, '?');

// Se é uma requisição para /app ou /app/...
if (preg_match('#^/app(/.*)?$#', $cleanPath, $matches)) {
    // Define PATH_INFO para o Express PHP
    $_SERVER['PATH_INFO'] = $matches[1] ?? '';
    $_SERVER['SCRIPT_NAME'] = '/app.php';

    // Inclui o arquivo app.php
    return include __DIR__ . '/app.php';
}

// Para outros arquivos, tenta encontrar o arquivo .php correspondente
$phpFile = __DIR__ . $cleanPath . '.php';
if (file_exists($phpFile)) {
    return include $phpFile;
}

// Se o arquivo existe como está, serve normalmente
if (file_exists(__DIR__ . $cleanPath)) {
    return false; // Serve o arquivo estático
}

// Retorna 404 se não encontrar nada
http_response_code(404);
echo "404 - Página não encontrada";
return true;
?>
