<?php

/**
 * Polyfills para funções PHP 8.0+ para compatibilidade com PHP 7.4+
 *
 * Este arquivo fornece implementações alternativas para funções introduzidas
 * no PHP 8.0, garantindo compatibilidade com versões anteriores.
 */

// Polyfill para str_starts_with (PHP 8.0+)
if (!function_exists('str_starts_with')) {
    /**
     * Verifica se uma string começa com uma substring específica.
     *
     * @param string $haystack A string onde buscar.
     * @param string $needle A substring a procurar no início de haystack.
     * @return bool
     */
    function str_starts_with(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }

        return strpos($haystack, $needle) === 0;
    }
}

// Polyfill para str_contains (PHP 8.0+)
if (!function_exists('str_contains')) {
    /**
     * Verifica se uma string contém uma substring específica.
     *
     * @param string $haystack A string onde buscar.
     * @param string $needle A substring a procurar em haystack.
     * @return bool
     */
    function str_contains(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }

        return strpos($haystack, $needle) !== false;
    }
}

// Polyfill para str_ends_with (PHP 8.0+)
if (!function_exists('str_ends_with')) {
    /**
     * Verifica se uma string termina com uma substring específica.
     *
     * @param string $haystack A string onde buscar.
     * @param string $needle A substring a procurar no final de haystack.
     * @return bool
     */
    function str_ends_with(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }

        $length = strlen($needle);
        return substr($haystack, -$length) === $needle;
    }
}

// Verificação de compatibilidade opcional
if (function_exists('str_starts_with') && defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 80000) {
    // As funções nativas do PHP 8.0+ estão disponíveis
    // Os polyfills não serão usados
}
