<?php

namespace Express\Middlewares\Core;

class OpenApiDocsMiddleware
{
  public function __construct(mixed $app, mixed $routers)
  {
    // Descobre a baseUrl do app
    $baseUrl = (is_object($app) && method_exists($app, 'getBaseUrl') && $app->getBaseUrl()) ? $app->getBaseUrl() : '';
    $jsonUrl = $baseUrl . '/docs/openapi.json';
    // Rota JSON OpenAPI
    $app->get('/docs/openapi.json', function ($request, $response) use ($routers, $baseUrl) {
      try {
        $openapi = \Express\Services\OpenApiExporter::export($routers, $baseUrl);
        header('Content-Type: application/json; charset=utf-8');
        $response->json($openapi);
        exit;
      } catch (\Throwable $e) {
        $html = '<h2>Erro ao gerar documenta??o OpenAPI</h2>';
        $html .= '<pre>' . htmlspecialchars($e->getMessage() . "\n" . $e->getTraceAsString()) . '</pre>';
        header('Content-Type: text/html', true, 500);
        $response->html($html);
        exit;
      }
    });
    // Rota HTML Swagger UI
    $app->get('/docs/index', function ($request, $response) use ($jsonUrl) {
      $documentation = <<<HTML
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Documentação da API - Express PHP</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist/swagger-ui.css">
    <style>
        html, body { height: 100%; margin: 0; }
        #swagger-ui { height: 100vh; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist/swagger-ui-bundle.js"></script>
    <script>
        window.onload = function() {
            SwaggerUIBundle({
                url: "{$jsonUrl}",
                dom_id: "#swagger-ui",
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIBundle.SwaggerUIStandalonePreset
                ],
                layout: "BaseLayout",
                docExpansion: "list",
                deepLinking: true,
                filter: true,
                showExtensions: true,
                showCommonExtensions: true
            });
        };
    </script>
</body>
</html>
HTML;
      $response->html($documentation);
      exit;
    });
  }
}
