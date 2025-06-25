<?php
// Exemplo de uso do helper de sanitização em middleware e rota
use Express\SRC\Helpers\Utils;

$router->use(function($req, $res, $next) {
    // Sanitiza todos os parâmetros de query
    foreach ($req->query() as $k => $v) {
        $req->query[$k] = Utils::sanitize($v, 'string');
    }
    $next();
});

$router->post('/comentario', function($req, $res) {
    $comentario = Utils::sanitize($req->body['comentario'] ?? '', 'string');
    // ... salvar comentário ...
    return $res->json(['comentario' => $comentario]);
});
