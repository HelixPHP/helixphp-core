<?php

namespace Helix;

use Helix\Core\Application;

/**
 * Alias de compatibilidade para Application.
 *
 * @deprecated Versão 3.0.0 - Use Express\Core\Application diretamente
 */
class ApiHelix extends Application
{
    /**
     * Construtor que delega para Application.
     *
     * @param string|null $basePath Caminho base da aplicação
     */
    public function __construct(?string $basePath = null)
    {
        trigger_error(
            'Helix\ApiHelix está depreciado. Use Express\Core\Application diretamente.',
            E_USER_DEPRECATED
        );

        parent::__construct($basePath);
    }
}
