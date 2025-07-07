<?php

namespace Helix;

use PivotPHP\Core\Core\Application;

/**
 * Alias de compatibilidade para Application.
 *
 * @deprecated Versão 3.0.0 - Use PivotPHP\Core\Core\Application diretamente
 */
class ApiPivot extends Application
{
    /**
     * Construtor que delega para Application.
     *
     * @param string|null $basePath Caminho base da aplicação
     */
    public function __construct(?string $basePath = null)
    {
        trigger_error(
            'Helix\ApiPivot está depreciado. Use PivotPHP\Core\Core\Application diretamente.',
            E_USER_DEPRECATED
        );

        parent::__construct($basePath);
    }
}
