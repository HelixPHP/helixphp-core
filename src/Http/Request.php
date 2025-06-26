<?php

namespace Express\Http;

use Express\Http\HeaderRequest;
use InvalidArgumentException;
use stdClass;
use RuntimeException;

/**
 * Classe Request representa a requisição HTTP recebida.
 * Facilita o acesso a parâmetros de rota, query string, corpo e cabeçalhos.
 *
 * @property string $method Método HTTP.
 * @property string $path Padrão da rota.
 * @property string $pathCallable Caminho real da requisição.
 * @property stdClass $params Parâmetros extraídos da URL.
 * @property stdClass $query Parâmetros da query string.
 * @property stdClass $body Corpo da requisição.
 * @property HeaderRequest $headers Cabeçalhos da requisição.
 */
class Request
{
    /**
     * Método HTTP.
     * @var string
     */
    private $method = '';

    /**
     * Padrão da rota.
     * @var string
     */
    private $path = '';

    /**
     * Caminho real da requisição.
     * @var string
     */
    private $pathCallable = '';

    /**
     * Parâmetros extraídos da URL.
     * @var stdClass
     */
    private $params = null;

    /**
     * Parâmetros da query string.
     * @var stdClass
     */
    private $query = null;

    /**
     * Corpo da requisição.
     * @var stdClass
     */
    private $body = null;

    /**
     * Cabeçalhos da requisição.
     * @var HeaderRequest
     */
    private $headers = null;

    /**
     * Arquivos enviados via upload (anexos).
     * @var array<string, mixed>
     */
    private $files = [];

    /**
     * Construtor da classe Request.
     * @param string $method Método HTTP.
     * @param string $path Padrão da rota.
     * @param string $pathCallable Caminho real da requisição.
     */
    public function __construct($method, $path, $pathCallable)
    {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->pathCallable = $pathCallable;
        if (!preg_match('/\/$/', $pathCallable)) {
            $this->pathCallable .= '/'; // Ensure path ends with a slash
        }
        $this->params = new stdClass();
        $this->query = new stdClass();
        $this->body = new stdClass();
        $this->headers = new HeaderRequest();
        $this->files = $_FILES;
        $this->parseRoute();
    }

    /**
     * Magic method to get properties dynamically
     *
     * @param string $name The property name
     * @return mixed The property value
     * @throws InvalidArgumentException if the property does not exist
     */
    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        throw new InvalidArgumentException("Property {$name} does not exist in Request class");
    }

    /**
     * Este método inicializa a rota, parseando o caminho e os parâmetros.
     *
     * @return void
     */
    private function parseRoute()
    {
        $this->parsePath();
        $this->parseQuery();
        $this->parseBody();
    }

    /**
     * Este método parseia o caminho da rota, extraindo os parâmetros e valores.
     *
     * @return void
     */
    private function parsePath()
    {
        // Permitir barra final opcional
        $pattern = preg_replace('/\/:([^\/]+)/', '/([^/]+)', $this->path);
        $pattern = rtrim($pattern ?: '', '/');
        $pattern = '#^' . $pattern . '/?$#';
        preg_match($pattern, rtrim($this->pathCallable ?: '', '/'), $values);
        array_shift($values); // Remove the full match
        preg_match_all('/\/:([^\/]+)/', $this->path, $params);
        $params = $params[1];
        // Permitir que valores extras sejam ignorados se a rota for mais curta
        if (count($params) > count($values)) {
            throw new InvalidArgumentException('Number of parameters does not match the number of values');
        }
        // Combine parameters with values
        if (!empty($params)) {
            $paramsArray = array_combine($params, array_slice($values, 0, count($params)));
            if ($paramsArray !== false) {
                foreach ($paramsArray as $key => $value) {
                    if (is_numeric($value)) {
                        $value = (int)$value; // Convert numeric values to integers
                    }
                    $this->params->{$key} = $value;
                }
            }
        }
    }

    /**
     * Este método parseia a query string da requisição, extraindo os parâmetros.
     *
     * @return void
     */
    private function parseQuery()
    {
        $query = $_SERVER['QUERY_STRING'] ?? '';
        $queryArray = [];
        parse_str($query, $queryArray);
        foreach ($queryArray as $key => $value) {
            $this->query->{$key} = $value;
        }
    }

    /**
     * Este método inicializa o corpo da requisição, parseando os dados do JSON ou formulário.
     *
     * @return void
     * @throws InvalidArgumentException if the body cannot be parsed as JSON or form data
     * @throws RuntimeException if the request method is not supported
     */
    private function parseBody()
    {
        if ($this->method === 'GET') {
            $this->body = new \stdClass();
            return;
        }
        $input = file_get_contents('php://input');
        if ($input !== false) {
            $decoded = json_decode($input);
            $this->body = $decoded !== null ? $decoded : new \stdClass();
        } else {
            $this->body = new \stdClass();
        }
        if (json_last_error() == JSON_ERROR_NONE) {
            return;
        }
        // If JSON parsing fails, try to parse as form data
        if (!empty($_POST)) {
            $this->body = new stdClass();
            foreach ($_POST as $key => $value) {
                $this->body->{$key} = $value;
            }
        }
    }

    /**
     * Obtém um parâmetro específico da rota.
     * @param string $key Nome do parâmetro.
     * @param mixed $default Valor padrão se não encontrado.
     * @return mixed
     */
    public function param(string $key, $default = null)
    {
        return $this->params->{$key} ?? $default;
    }

    /**
     * Obtém um parâmetro específico da query string.
     * @param string $key Nome do parâmetro.
     * @param mixed $default Valor padrão se não encontrado.
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->query->{$key} ?? $default;
    }

    /**
     * Obtém um valor específico do corpo da requisição.
     * @param string $key Nome do campo.
     * @param mixed $default Valor padrão se não encontrado.
     * @return mixed
     */
    public function input(string $key, $default = null)
    {
        return $this->body->{$key} ?? $default;
    }

    /**
     * Obtém informações sobre um arquivo enviado.
     * @param string $key Nome do campo do arquivo.
     * @return array|null
     */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Verifica se a requisição tem um arquivo específico.
     * @param string $key Nome do campo do arquivo.
     * @return bool
     */
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Obtém o IP do cliente.
     * @return string
     */
    public function ip(): string
    {
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Obtém o User-Agent.
     * @return string
     */
    public function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Verifica se a requisição é AJAX.
     * @return bool
     */
    public function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Verifica se a requisição é HTTPS.
     * @return bool
     */
    public function isSecure(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on');
    }

    /**
     * Obtém a URL completa da requisição.
     * @return string
     */
    public function fullUrl(): string
    {
        $protocol = $this->isSecure() ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return "{$protocol}://{$host}{$uri}";
    }

    /**
     * Cria uma instância Request a partir das variáveis globais PHP.
     *
     * @return Request Nova instância de Request
     */
    public static function createFromGlobals(): Request
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $pathCallable = $path;

        return new static($method, $path, $pathCallable);
    }
}
