<?php
// Exemplo de uso isolado das rotas de admin
$app = require __DIR__ . '/snippets/app_base.php';
$adminRouter = require __DIR__ . '/snippets/admin_routes.php';
$app->use($adminRouter);
$app->run();
