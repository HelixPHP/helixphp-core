<?php
// Exemplo de validação usando Utils
use Express\Helpers\Utils;

$dados = [
    'email' => 'usuario@dominio.com',
    'idade' => '25',
    'ativo' => '1',
];

// Validação de tipos
if (!Utils::isEmail($dados['email'])) {
    echo "Email inválido\n";
}
if (!Utils::isInt($dados['idade'])) {
    echo "Idade inválida\n";
}
if (!Utils::isBool($dados['ativo'])) {
    echo "Status inválido\n";
}

// Validação customizada
if ($dados['idade'] <= 18) {
    echo "Idade deve ser maior que 18\n";
}
