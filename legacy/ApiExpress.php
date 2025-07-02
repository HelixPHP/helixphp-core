<?php

namespace Express;

use Express\Core\Application;

/**
 * Alias de compatibilidade para Application.
 *
 * @deprecated Versão 3.0.0 - Use Express\Core\Application diretamente
 */
class ApiExpress extends Application
{
    /**
     * Construtor que delega para Application.
     *
     * @param string|null $basePath Caminho base da aplicação
     */
    public function __construct(?string $basePath = null)
    {
        trigger_error(
            'Express\ApiExpress está depreciado. Use Express\Core\Application diretamente.',
            E_USER_DEPRECATED
        );

        parent::__construct($basePath);
    }
}
