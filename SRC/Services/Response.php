<?php

namespace Express\Services;

/**
 * Classe Response constrói e envia a resposta HTTP.
 * Permite definir status, cabeçalhos e corpo da resposta em diferentes formatos.
 *
 * @property int $statusCode Código de status HTTP.
 * @property array $headers Cabeçalhos da resposta.
 * @property string $body Corpo da resposta.
 */
class Response
{
  /**
   * Código de status HTTP.
   * @var int
   */
  private $statusCode = 200;
  /**
   * Cabeçalhos da resposta.
   * @var array
   */
  private $headers = [];
  /**
   * Corpo da resposta.
   * @var string
   */
  private $body = '';

  /**
   * Define o status HTTP da resposta.
   * @param int $code Código de status.
   * @return $this
   */
  public function status($code)
  {
    $this->statusCode = $code;
    http_response_code($this->statusCode);
    return $this;
  }
  /**
   * Define um cabeçalho na resposta.
   * @param string $name Nome do cabeçalho.
   * @param string $value Valor do cabeçalho.
   * @return $this
   */
  public function header($name, $value)
  {
    $this->headers[$name] = $value;
    header("{$name}: {$value}");
    return $this;
  }
  /**
   * Envia resposta em formato JSON.
   * @param mixed $data Dados a serem enviados.
   * @return $this
   */
  public function json($data)
  {
    $this->header('Content-Type', 'application/json; charset=utf-8');
    $this->body = json_encode($data);
    echo $this->body;
    return $this;
  }
  /**
   * Envia resposta em texto puro.
   * @param string $text Texto a ser enviado.
   * @return $this
   */
  public function text($text)
  {
    $this->header('Content-Type', 'text/plain; charset=utf-8');
    $this->body = $text;
    echo $this->body;
    return $this;
  }
  /**
   * Envia resposta em HTML.
   * @param string $html HTML a ser enviado.
   * @return $this
   */
  public function html($html)
  {
    $this->header('Content-Type', 'text/html; charset=utf-8');
    $this->body = $html;
    echo $this->body;
    return $this;
  }
}