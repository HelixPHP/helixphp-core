<?php

use Express\Routing\RouterInstance;

// Sub-router especializado para rotas de admin
$adminRouter = new RouterInstance('/admin');
$adminRouter->use(function ($request, $response, $next) {
    $response->header('X-Admin', 'true');
    $next();
});
$adminRouter->get('/dashboard', function ($request, $response) {
    $response->json(['message' => 'Bem-vindo ao painel admin!']);
}, ['tags' => ['Admin']]);
$adminRouter->get(
    '/logs',
    function ($request, $response, $next) {
        if (!$request->headers->hasHeader('authorization')) {
            $response->status(401)->json(['error' => 'Acesso negado ao log']);
            return;
        }
        $next();
    },
    function ($request, $response) {
        $response->json(['logs' => ['log1', 'log2']]);
    },
    ['tags' => ['Admin']]
);

return $adminRouter;
