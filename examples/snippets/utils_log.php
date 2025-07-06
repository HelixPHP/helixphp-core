<?php
// Exemplo de uso do helper de log
use Helix\Utils\Utils;

Utils::log('Usuário acessou a rota /api/user\n'.print_r([
    'user_id' => 123,
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'localhost',
], true), 'info');
