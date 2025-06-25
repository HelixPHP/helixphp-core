<?php
// Exemplo de uso isolado das rotas de blog
$app = require __DIR__ . '/snippets/app_base.php';
$blogRouter = require __DIR__ . '/snippets/blog_routes.php';
$app->use($blogRouter);
$app->run();
