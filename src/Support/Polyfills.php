<?php

/**
 * Polyfills para funções PHP 8.0+ para compatibilidade com PHP 7.4+
 */

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
        return $needle !== '' && strpos($haystack, $needle) === 0;
    }
}

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
        return $needle !== '' && strpos($haystack, $needle) !== false;
    }
}

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
        return $needle !== '' && substr($haystack, -strlen($needle)) === $needle;
    }
}
