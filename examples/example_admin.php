<?php
// Example of isolated admin routes usage
$app = require __DIR__ . '/snippets/app_base.php';
$adminRouter = require __DIR__ . '/snippets/admin_routes.php';
$app->use($adminRouter);
$app->run();
