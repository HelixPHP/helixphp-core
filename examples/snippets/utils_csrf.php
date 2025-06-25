<?php
// Exemplo de geração e validação de token CSRF
use Express\SRC\Helpers\Utils;
use Express\SRC\Services\CsrfMiddleware;

// Método 1: Usando Utils (básico)
$token = Utils::csrfToken();
echo "Token CSRF: $token\n";

// Em uma requisição subsequente:
if (!Utils::checkCsrf($token)) {
    echo "Token CSRF inválido!\n";
} else {
    echo "Token CSRF válido.\n";
}

// Método 2: Usando CsrfMiddleware (recomendado)
$csrfToken = CsrfMiddleware::getToken();
echo "Token CSRF (middleware): $csrfToken\n";

// Gerando campos HTML
echo "Campo hidden: " . CsrfMiddleware::hiddenField() . "\n";
echo "Meta tag: " . CsrfMiddleware::metaTag() . "\n";

// Exemplo de formulário completo
$form = '
<form method="POST" action="/submit">
    ' . CsrfMiddleware::hiddenField() . '
    <input type="text" name="name" placeholder="Nome">
    <button type="submit">Enviar</button>
</form>';

echo "Formulário com CSRF:\n$form\n";
