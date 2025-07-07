<?php
// Exemplo de uso do helper de CORS
use PivotPHP\Core\Utils\Utils;

// Em um middleware customizado:
function corsMiddleware($response) {
    $headers = Utils::corsHeaders(['*'], ['GET','POST','PUT','DELETE'], ['Content-Type','Authorization']);
    foreach ($headers as $key => $value) {
        header("$key: $value");
    }
    // ... continue com o fluxo normal
}
