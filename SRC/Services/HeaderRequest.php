<?php

namespace Express\SRC\Services;

/**
 * Classe HeaderRequest gerencia e facilita o acesso aos cabeçalhos da requisição.
 * Converte os cabeçalhos para camelCase e permite acesso via propriedades ou métodos.
 *
 * @property array $headers Array de cabeçalhos convertidos.
 */
class HeaderRequest
{
  /**
   * Array de cabeçalhos convertidos.
   * @var array
   */
  private $headers;

  /**
   * Construtor. Inicializa e converte os cabeçalhos da requisição.
   */
  public function __construct()
  {
    $headers = getallheaders();
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
   * Permite acesso aos cabeçalhos via propriedade.
   * @param string $name Nome do cabeçalho.
   * @return string|null Valor do cabeçalho ou null se não existir.
   */
  public function __get($name)
  {
    if (isset($this->headers[$name])) {
      return $this->headers[$name];
    }
    return null;
  }

  /**
   * Retorna o valor de um cabeçalho.
   * @param string $name Nome do cabeçalho.
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
