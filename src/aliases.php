<?php

/**
 * Aliases e funções globais para HelixPHP.
 */

// Função global para criação rápida de aplicações (estilo Express.js)
if (!function_exists('express')) {
    /**
     * Cria uma nova instância da aplicação HelixPHP.
     *
     * @param string|null $basePath Caminho base da aplicação
     * @return Helix\Core\Application
     */
    function express(?string $basePath = null): Helix\Core\Application
    {
        return Helix\Core\Application::express($basePath);
    }
}

// Helper para criação de aplicação
if (!function_exists('app')) {
    /**
     * Cria uma nova instância da aplicação.
     *
     * @param string|null $basePath Caminho base da aplicação
     * @return Helix\Core\Application
     */
    function app(?string $basePath = null): Helix\Core\Application
    {
        return Helix\Core\Application::create($basePath);
    }
}
