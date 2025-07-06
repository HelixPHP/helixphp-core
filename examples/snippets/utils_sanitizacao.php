<?php
// Exemplo de uso do helper de sanitização em middleware e rota
use Helix\Utils\Utils;

$router->use(function($req, $res, $next) {
    // Sanitiza todos os parâmetros de query
    foreach ($req->query() as $k => $v) {
        $req->query[$k] = Utils::sanitizeString($v);
    }
    $next();
});

$router->post('/comentario', function($req, $res) {
    $comentario = Utils::sanitizeString($req->body['comentario'] ?? '');
    // ... salvar comentário ...
    return $res->json(['comentario' => $comentario]);
});
