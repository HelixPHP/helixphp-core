<?php

use Helix\Routing\RouterInstance;

// Sub-router especializado para rotas de blog
$blogRouter = new RouterInstance('/blog');
$blogRouter->get('/posts', function ($request, $response) {
    $response->json(['area' => 'blog', 'posts' => ['Post 1', 'Post 2']]);
}, ['tags' => ['Blog']]);
$blogRouter->post('/posts', function ($request, $response) {
    $response->status(201)->json(['message' => 'Novo post criado', 'body' => $request->body]);
}, ['tags' => ['Blog']]);

return $blogRouter;
