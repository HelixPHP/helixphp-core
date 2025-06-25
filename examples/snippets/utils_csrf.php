<?php
// Exemplo de geração e validação de token CSRF
use Express\SRC\Helpers\Utils;

$token = Utils::csrfToken();
echo "Token CSRF: $token\n";

// Em uma requisição subsequente:
if (!Utils::checkCsrf($token)) {
    echo "Token CSRF inválido!\n";
} else {
    echo "Token CSRF válido.\n";
}
