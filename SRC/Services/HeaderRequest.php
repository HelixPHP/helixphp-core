<?php

namespace Express\Services;

/**
 * HeaderRequest class manages and facilitates access to request headers.
 * Converts headers to camelCase and allows access via properties or methods.
 *
 * @property array $headers Array of converted headers.
 */
class HeaderRequest
{
  /**
   * Array of converted headers.
   * @var array
   */
    private $headers;

  /**
   * Constructor. Initializes and converts request headers.
   */
    public function __construct()
    {
      // Fallback para getallheaders() se não estiver disponível (como em testes CLI)
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            $headers = [];
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headerName = str_replace(
                        ' ',
                        '-',
                        ucwords(strtolower(str_replace('_', ' ', substr($name, 5))))
                    );
                    $headers[$headerName] = $value;
                }
            }
        }

        foreach ($headers as $key => $value) {
            $key = trim($key, ':'); // Remove leading colon
            $camelCaseKey = explode('-', $key); // Remove any suffix after a hyphen
            $camelCaseKey = array_map('ucfirst', $camelCaseKey);
            $camelCaseKey = implode('', $camelCaseKey);
            $key = lcfirst($camelCaseKey); // Convert to camelCase
            $this->headers[$key] = $value;
        }
    }

  /**
   * Allows access to headers via property.
   * @param string $name Header name.
   * @return string|null Header value or null if not exists.
   */
    public function __get($name)
    {
        if (isset($this->headers[$name])) {
            return $this->headers[$name];
        }
        return null;
    }

  /**
   * Returns the value of a header.
   * @param string $name Header name.
   * @return string|null Valor do cabeçalho ou null se não existir.
   */
    public function getHeader($name)
    {
        return isset($this->headers[$name]) ? $this->headers[$name] : null;
    }

  /**
   * Retorna todos os cabeçalhos convertidos.
   * @return array
   */
    public function getAllHeaders()
    {
        return $this->headers;
    }

  /**
   * Verifica se um cabeçalho existe.
   * @param string $name Nome do cabeçalho.
   * @return bool
   */
    public function hasHeader($name)
    {
        return isset($this->headers[$name]);
    }
}
