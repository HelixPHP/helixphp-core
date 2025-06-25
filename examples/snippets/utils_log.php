<?php
// Exemplo de uso do helper de log
use Express\SRC\Helpers\Utils;

Utils::log('info', 'Usuário acessou a rota /api/user', [
    'user_id' => 123,
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'localhost',
]);
