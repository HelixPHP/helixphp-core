<?php

/**
 * Aliases e funções globais para Express PHP.
 */

// Função global para criação rápida de aplicações (estilo Express.js)
if (!function_exists('express')) {
    /**
     * Cria uma nova instância da aplicação Express PHP.
     *
     * @param string|null $basePath Caminho base da aplicação
     * @return Express\Core\Application
     */
    function express(?string $basePath = null): Express\Core\Application
    {
        return Express\Core\Application::express($basePath);
    }
}

// Helper para criação de aplicação
if (!function_exists('app')) {
    /**
     * Cria uma nova instância da aplicação.
     *
     * @param string|null $basePath Caminho base da aplicação
     * @return Express\Core\Application
     */
    function app(?string $basePath = null): Express\Core\Application
    {
        return Express\Core\Application::create($basePath);
    }
}
