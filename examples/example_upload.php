<?php
// Example of isolated upload routes usage
$app = require __DIR__ . '/snippets/app_base.php';
$uploadRouter = require __DIR__ . '/snippets/upload_routes.php';
$app->use($uploadRouter);
$app->run();
