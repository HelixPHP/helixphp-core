<?php

use Express\Routing\RouterInstance;

// Sub-router especializado para rotas de usuário
$userRouter = new RouterInstance('/user');
$userRouter->use(function ($request, $response, $next) {
    $response->header('X-Group', 'user');
    $next();
});
$userRouter->get(
    '/:id',
    function ($request, $response, $next) {
        if (!$request->headers->hasHeader('authorization')) {
            $response->status(401)->json([
                'error' => 'Unauthorized',
                'message' => 'Authorization header is missing or empty',
                'headers' => $request->headers->authorization,
                'validation' => true
            ]);
            return;
        }
        $next();
    },
    function ($request, $response, $next) {
        $request->params->rotina = 'default';
        $next();
    },
    function ($request, $response) {
        $response->status(200)->json([
            'message' => "{$request->method}: User-Agent: {$request->headers->userAgent}, User ID: {$request->params->id}, Rotina: {$request->params->rotina}",
        ]);
    },
    ['tags' => ['User']]
);
$userRouter->get('/:id/:rotina', function ($request, $response) {
    $response->status(200)->json(['message' => "{$request->method}: User-Agent: {$request->headers->userAgent}, User ID: {$request->params->id}, Rotina: {$request->params->rotina}"]);
}, ['tags' => ['User']]);
$userRouter->post('/:id', function ($request, $response) {
    $response->status(200)->json(['message' => "{$request->method}: User-Agent: {$request->headers->userAgent}, User ID: {$request->params->id}"]);
}, ['tags' => ['User']]);
$userRouter->post('/:id/:rotina', function ($request, $response) {
    $response->status(200)->json(['message' => "{$request->method}: User-Agent: {$request->headers->userAgent}, User ID: {$request->params->id}, Rotina: {$request->params->rotina}"]);
}, ['tags' => ['User']]);

return $userRouter;
