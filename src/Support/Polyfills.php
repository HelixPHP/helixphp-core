<?php

/**
 * Polyfills para funções PHP 8.0+ para compatibilidade com PHP 8.0+
 *
 * IMPORTANTE: Este arquivo foi mantido para compatibilidade com versões
 * anteriores, mas como agora requeremos PHP 8.0+, as funções str_starts_with,
 * str_contains e str_ends_with estão disponíveis nativamente.
 *
 * Este arquivo pode ser removido em futuras versões.
 */

// PHP 8.0+ já inclui todas essas funções nativamente
// Manter apenas como fallback para casos extremos

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return strlen($needle) === 0 || strpos($haystack, $needle) === 0;
    }
}

if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return strlen($needle) === 0 || strpos($haystack, $needle) !== false;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool
    {
        if (strlen($needle) === 0) {
            return true;
        }
        return substr($haystack, -strlen($needle)) === $needle;
    }
}

// Verificação de compatibilidade
if (PHP_VERSION_ID >= 80000) {
    // As funções nativas do PHP 8.0+ estão disponíveis
    // Os polyfills acima não serão executados
}
