<?php
// Exemplo de uso isolado das rotas de produto
$app = require __DIR__ . '/snippets/app_base.php';
$produtoRouter = require __DIR__ . '/snippets/produto_routes.php';
$app->use($produtoRouter);
use Express\SRC\Services\RequestValidationMiddleware;
$app->use(new RequestValidationMiddleware());
$app->run();
