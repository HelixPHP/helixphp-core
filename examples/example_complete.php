<?php
// Complete example integrating all sub-routers
$app = require __DIR__ . '/snippets/app_base.php';
$userRouter = require __DIR__ . '/snippets/user_routes.php';
$produtoRouter = require __DIR__ . '/snippets/produto_routes.php';
$uploadRouter = require __DIR__ . '/snippets/upload_routes.php';
$adminRouter = require __DIR__ . '/snippets/admin_routes.php';
$blogRouter = require __DIR__ . '/snippets/blog_routes.php';

use Express\Validation\RequestValidationMiddleware;
$app->use(new RequestValidationMiddleware());

$app->use($userRouter);
$app->use($produtoRouter);
$app->use($uploadRouter);
$app->use($adminRouter);
$app->use($blogRouter);

// Ativa documentação automática
use Express\Routing\Router;
use Express\Validation\OpenApiDocsMiddleware;
new OpenApiDocsMiddleware($app, [
    Router::class,
    $userRouter,
    $produtoRouter,
    $uploadRouter,
    $adminRouter,
    $blogRouter
]);

$app->run();
