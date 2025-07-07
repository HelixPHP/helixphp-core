<?php

/**
 * Aliases e funções globais para PivotPHP.
 */

// Função global para criação rápida de aplicações (estilo Express.js)
if (!function_exists('express')) {
    /**
     * Cria uma nova instância da aplicação PivotPHP.
     *
     * @param string|null $basePath Caminho base da aplicação
     * @return PivotPHP\Core\Core\Application
     */
    function express(?string $basePath = null): PivotPHP\Core\Core\Application
    {
        return PivotPHP\Core\Core\Application::express($basePath);
    }
}

// Helper para criação de aplicação
if (!function_exists('app')) {
    /**
     * Cria uma nova instância da aplicação.
     *
     * @param string|null $basePath Caminho base da aplicação
     * @return PivotPHP\Core\Core\Application
     */
    function app(?string $basePath = null): PivotPHP\Core\Core\Application
    {
        return PivotPHP\Core\Core\Application::create($basePath);
    }
}
