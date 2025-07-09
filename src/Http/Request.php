<?php

namespace PivotPHP\Core\Http;

use PivotPHP\Core\Http\HeaderRequest;
use PivotPHP\Core\Http\Contracts\AttributeInterface;
use PivotPHP\Core\Http\Psr7\ServerRequest;
use PivotPHP\Core\Http\Psr7\Stream;
use PivotPHP\Core\Http\Psr7\Uri;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\UploadedFileInterface;
use InvalidArgumentException;
use stdClass;
use RuntimeException;

/**
 * Classe Request híbrida que implementa PSR-7 mantendo compatibilidade Express.js
 *
 * Esta classe oferece suporte completo a PSR-7 (ServerRequestInterface) 
 * enquanto mantém todos os métodos de conveniência do estilo Express.js
 * para total compatibilidade com código existente.
 *
 * @property mixed $user Usuário autenticado ou qualquer outro atributo dinâmico.
 */
class Request implements ServerRequestInterface, AttributeInterface
{
    /**
     * Instância PSR-7 interna
     */
    private ServerRequestInterface $psr7Request;

    /**
     * Método HTTP.
     */
    private string $method;

    /**
     * Padrão da rota.
     */
    private string $path;

    /**
     * Caminho real da requisição.
     */
    private string $pathCallable;

    /**
     * Parâmetros extraídos da URL.
     */
    private stdClass $params;

    /**
     * Parâmetros da query string.
     */
    private stdClass $query;

    /**
     * Corpo da requisição.
     */
    private stdClass $body;

    /**
     * Cabeçalhos da requisição.
     */
    private HeaderRequest $headers;

    /**
     * Arquivos enviados via upload (anexos).
     *
     * @var array<string, mixed>
     */
    private array $files = [];

    /**
     * Atributos dinâmicos adicionados ao request.
     *
     * @var array<string, mixed>
     */
    private array $attributes = [];

    /**
     * Construtor da classe Request.
     *
     * @param string $method       Método HTTP.
     * @param string $path         Padrão da rota.
     * @param string $pathCallable Caminho real da requisição.
     */
    public function __construct(string $method, string $path, string $pathCallable)
    {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->pathCallable = $pathCallable;
        if (!str_ends_with($pathCallable, '/')) {
            $this->pathCallable .= '/';
        }
        $this->params = new stdClass();
        $this->query = new stdClass();
        $this->body = new stdClass();
        $this->headers = new HeaderRequest();
        $this->files = $_FILES;
        
        // Inicializar PSR-7 request interno
        $this->initializePsr7Request();
        
        $this->parseRoute();
    }

    /**
     * Inicializa o request PSR-7 interno
     */
    private function initializePsr7Request(): void
    {
        $uri = new Uri($this->pathCallable);
        $body = Stream::createFromString(file_get_contents('php://input') ?: '');
        $headers = $this->convertHeadersToPsr7Format($_SERVER);
        
        $this->psr7Request = new ServerRequest(
            $this->method,
            $uri,
            $body,
            $headers,
            '1.1',
            $_SERVER
        );
        
        // Configurar query params
        $this->psr7Request = $this->psr7Request->withQueryParams($_GET);
        
        // Configurar parsed body
        if (in_array($this->method, ['POST', 'PUT', 'PATCH'])) {
            $input = file_get_contents('php://input');
            if ($input !== false) {
                $decoded = json_decode($input, true);
                $this->psr7Request = $this->psr7Request->withParsedBody($decoded ?: $_POST);
            }
        }
        
        // Configurar cookies
        $this->psr7Request = $this->psr7Request->withCookieParams($_COOKIE);
        
        // Configurar uploaded files
        $this->psr7Request = $this->psr7Request->withUploadedFiles($this->normalizeFiles($_FILES));
    }

    /**
     * Converte headers do formato $_SERVER para PSR-7
     */
    private function convertHeadersToPsr7Format(array $server): array
    {
        $headers = [];
        
        foreach ($server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name = substr($key, 5);
                $name = str_replace('_', '-', $name);
                $name = ucwords(strtolower($name), '-');
                $headers[$name] = [$value];
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $name = str_replace('_', '-', $key);
                $name = ucwords(strtolower($name), '-');
                $headers[$name] = [$value];
            }
        }
        
        return $headers;
    }

    /**
     * Normaliza uploaded files para PSR-7
     */
    private function normalizeFiles(array $files): array
    {
        $normalized = [];

        foreach ($files as $key => $file) {
            if (is_array($file['name'])) {
                $normalized[$key] = $this->normalizeNestedFiles($file);
            } else {
                $normalized[$key] = $this->createUploadedFile($file);
            }
        }

        return $normalized;
    }

    /**
     * Normaliza nested uploaded files
     */
    private function normalizeNestedFiles(array $file): array
    {
        $normalized = [];

        foreach (array_keys($file['name']) as $key) {
            $normalized[$key] = $this->createUploadedFile([
                'name' => $file['name'][$key],
                'type' => $file['type'][$key],
                'tmp_name' => $file['tmp_name'][$key],
                'error' => $file['error'][$key],
                'size' => $file['size'][$key],
            ]);
        }

        return $normalized;
    }

    /**
     * Cria UploadedFile do array de arquivo
     */
    private function createUploadedFile(array $file): \PivotPHP\Core\Http\Psr7\UploadedFile
    {
        if (!isset($file['tmp_name']) || !is_string($file['tmp_name'])) {
            throw new \InvalidArgumentException('Invalid file specification');
        }

        // Para testes, criar um stream vazio se o arquivo não existir
        if (!file_exists($file['tmp_name'])) {
            $stream = Stream::createFromString('');
        } else {
            $stream = Stream::createFromFile($file['tmp_name']);
        }

        return new \PivotPHP\Core\Http\Psr7\UploadedFile(
            $stream,
            $file['size'] ?? null,
            $file['error'] ?? \UPLOAD_ERR_OK,
            $file['name'] ?? null,
            $file['type'] ?? null
        );
    }

    // =============================================================================
    // MÉTODOS EXPRESS.JS (COMPATIBILIDADE TOTAL)
    // =============================================================================

    /**
     * Magic method to get properties dynamically
     */
    public function __get(string $name): mixed
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }

        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }

        throw new InvalidArgumentException("Property {$name} does not exist in Request class");
    }

    /**
     * Magic method to set properties dynamically
     */
    public function __set(string $name, mixed $value): void
    {
        if (property_exists($this, $name)) {
            throw new RuntimeException("Cannot override native property: {$name}");
        }

        $this->attributes[$name] = $value;
        $this->psr7Request = $this->psr7Request->withAttribute($name, $value);
    }

    /**
     * Magic method to check if property exists
     */
    public function __isset(string $name): bool
    {
        return property_exists($this, $name) || array_key_exists($name, $this->attributes);
    }

    /**
     * Magic method to unset properties
     */
    public function __unset(string $name): void
    {
        if (property_exists($this, $name)) {
            throw new RuntimeException("Cannot unset native property: {$name}");
        }

        unset($this->attributes[$name]);
        $this->psr7Request = $this->psr7Request->withoutAttribute($name);
    }

    /**
     * Obtém um parâmetro específico da rota.
     */
    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params->{$key} ?? $default;
    }

    /**
     * Obtém um parâmetro específico da query string.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->query->{$key} ?? $default;
    }

    /**
     * Obtém um valor específico do corpo da requisição.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body->{$key} ?? $default;
    }

    /**
     * Obtém informações sobre um arquivo enviado.
     */
    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;
        return is_array($file) ? $file : null;
    }

    /**
     * Verifica se a requisição tem um arquivo específico.
     */
    public function hasFile(string $key): bool
    {
        $file = $this->files[$key] ?? null;
        return is_array($file) && isset($file['error']) && $file['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Obtém o IP do cliente.
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
     */
    public function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Verifica se a requisição é AJAX.
     */
    public function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Verifica se a requisição é HTTPS.
     */
    public function isSecure(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on');
    }

    /**
     * Obtém a URL completa da requisição.
     */
    public function fullUrl(): string
    {
        $protocol = $this->isSecure() ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return "{$protocol}://{$host}{$uri}";
    }

    /**
     * Obtém header da requisição.
     */
    public function header(string $name): ?string
    {
        if (!is_string($name)) {
            throw new InvalidArgumentException('Header name must be a string');
        }
        if (!$this->headers->hasHeader($name)) {
            return null;
        }

        return $this->headers->getHeader($name);
    }

    // =============================================================================
    // MÉTODOS PSR-7 (ServerRequestInterface)
    // =============================================================================

    public function getServerParams(): array
    {
        return $this->psr7Request->getServerParams();
    }

    public function getCookieParams(): array
    {
        return $this->psr7Request->getCookieParams();
    }

    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->psr7Request = $this->psr7Request->withCookieParams($cookies);
        return $clone;
    }

    public function getQueryParams(): array
    {
        return $this->psr7Request->getQueryParams();
    }

    public function withQueryParams(array $query): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->psr7Request = $this->psr7Request->withQueryParams($query);
        return $clone;
    }

    public function getUploadedFiles(): array
    {
        return $this->psr7Request->getUploadedFiles();
    }

    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->psr7Request = $this->psr7Request->withUploadedFiles($uploadedFiles);
        return $clone;
    }

    public function getParsedBody()
    {
        return $this->psr7Request->getParsedBody();
    }

    public function withParsedBody($data): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->psr7Request = $this->psr7Request->withParsedBody($data);
        return $clone;
    }

    public function getAttributes(): array
    {
        return $this->psr7Request->getAttributes();
    }

    public function getAttribute($name, $default = null)
    {
        return $this->psr7Request->getAttribute($name, $default);
    }

    public function withAttribute($name, $value): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->psr7Request = $this->psr7Request->withAttribute($name, $value);
        $clone->attributes[$name] = $value;
        return $clone;
    }

    public function withoutAttribute($name): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->psr7Request = $this->psr7Request->withoutAttribute($name);
        unset($clone->attributes[$name]);
        return $clone;
    }

    // =============================================================================
    // MÉTODOS PSR-7 (RequestInterface)
    // =============================================================================

    public function getRequestTarget(): string
    {
        return $this->psr7Request->getRequestTarget();
    }

    public function withRequestTarget($requestTarget): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->psr7Request = $this->psr7Request->withRequestTarget($requestTarget);
        return $clone;
    }

    public function getMethod(): string
    {
        return $this->psr7Request->getMethod();
    }

    public function withMethod($method): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->psr7Request = $this->psr7Request->withMethod($method);
        $clone->method = strtoupper($method);
        return $clone;
    }

    public function getUri(): UriInterface
    {
        return $this->psr7Request->getUri();
    }

    public function withUri(UriInterface $uri, $preserveHost = false): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->psr7Request = $this->psr7Request->withUri($uri, $preserveHost);
        return $clone;
    }

    // =============================================================================
    // MÉTODOS PSR-7 (MessageInterface)
    // =============================================================================

    public function getProtocolVersion(): string
    {
        return $this->psr7Request->getProtocolVersion();
    }

    public function withProtocolVersion($version): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->psr7Request = $this->psr7Request->withProtocolVersion($version);
        return $clone;
    }

    public function getHeaders(): array
    {
        return $this->psr7Request->getHeaders();
    }

    public function hasHeader($name): bool
    {
        return $this->psr7Request->hasHeader($name);
    }

    public function getHeader($name): array
    {
        return $this->psr7Request->getHeader($name);
    }

    public function getHeaderLine($name): string
    {
        return $this->psr7Request->getHeaderLine($name);
    }

    public function withHeader($name, $value): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->psr7Request = $this->psr7Request->withHeader($name, $value);
        return $clone;
    }

    public function withAddedHeader($name, $value): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->psr7Request = $this->psr7Request->withAddedHeader($name, $value);
        return $clone;
    }

    public function withoutHeader($name): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->psr7Request = $this->psr7Request->withoutHeader($name);
        return $clone;
    }

    public function getBody(): StreamInterface
    {
        return $this->psr7Request->getBody();
    }

    public function withBody(StreamInterface $body): ServerRequestInterface
    {
        $clone = clone $this;
        $clone->psr7Request = $this->psr7Request->withBody($body);
        return $clone;
    }

    // =============================================================================
    // MÉTODOS LEGADOS (COMPATIBILIDADE)
    // =============================================================================

    /**
     * Este método inicializa a rota, parseando o caminho e os parâmetros.
     */
    private function parseRoute(): void
    {
        $this->parsePath();
        $this->parseQuery();
        $this->parseBody();
    }

    /**
     * Este método parseia o caminho da rota, extraindo os parâmetros e valores.
     */
    private function parsePath(): void
    {
        $pattern = preg_replace('/\/:([^\/]+)/', '/([^/]+)', $this->path);
        $pattern = rtrim($pattern ?: '', '/');
        $pattern = '#^' . $pattern . '/?$#';
        $matchResult = preg_match($pattern, rtrim($this->pathCallable ?: '', '/'), $values);
        if ($matchResult && !empty($values)) {
            array_shift($values);
        } else {
            $values = [];
        }
        preg_match_all('/\/:([^\/]+)/', $this->path, $params);
        $params = $params[1];
        
        if (count($params) > count($values)) {
            throw new InvalidArgumentException('Number of parameters does not match the number of values');
        }
        
        if (!empty($params)) {
            $paramsArray = array_combine($params, array_slice($values, 0, count($params)));
            if ($paramsArray !== false) {
                foreach ($paramsArray as $key => $value) {
                    if (is_numeric($value)) {
                        $value = (int)$value;
                    }
                    $this->params->{$key} = $value;
                    // Sincronizar com PSR-7
                    $this->psr7Request = $this->psr7Request->withAttribute($key, $value);
                }
            }
        }
    }

    /**
     * Este método parseia a query string da requisição.
     */
    private function parseQuery(): void
    {
        $query = $_SERVER['QUERY_STRING'] ?? '';
        $queryArray = [];
        parse_str($query, $queryArray);
        foreach ($queryArray as $key => $value) {
            $this->query->{$key} = $value;
        }
    }

    /**
     * Este método inicializa o corpo da requisição.
     */
    private function parseBody(): void
    {
        if ($this->method === 'GET') {
            $this->body = new stdClass();
            return;
        }

        $input = file_get_contents('php://input');
        if ($input !== false) {
            $decoded = json_decode($input);
            if ($decoded instanceof stdClass) {
                $this->body = $decoded;
            } else {
                $this->body = new stdClass();
            }
        } else {
            $this->body = new stdClass();
        }

        if (json_last_error() == JSON_ERROR_NONE) {
            return;
        }

        if (!empty($_POST)) {
            $this->body = new stdClass();
            foreach ($_POST as $key => $value) {
                $this->body->{$key} = $value;
            }
        }
    }

    /**
     * Cria uma instância Request a partir das variáveis globais PHP.
     */
    public static function createFromGlobals(): Request
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $path = $path !== false && $path !== null ? $path : '/';
        $pathCallable = $path;

        return new self($method, $path, $pathCallable);
    }

    // Métodos legados mantidos para compatibilidade
    public function getPath(): string
    {
        if (empty($this->path)) {
            throw new RuntimeException('Path is not defined in Request');
        }
        return $this->path;
    }

    public function setPath(string $path): self
    {
        if (empty($path)) {
            throw new InvalidArgumentException('Path cannot be empty');
        }
        $this->path = $path;
        if (!str_ends_with($this->path, '/')) {
            $this->path .= '/';
        }
        $this->parsePath();
        return $this;
    }

    public function getPathCallable(): string
    {
        if (empty($this->pathCallable)) {
            throw new RuntimeException('Path callable is not defined in Request');
        }
        return $this->pathCallable;
    }

    public function getParams(): stdClass
    {
        return $this->params;
    }

    public function getParam(string $key, mixed $default = null): mixed
    {
        return $this->params->{$key} ?? $default;
    }

    public function getQuerys(): stdClass
    {
        return $this->query;
    }

    public function getQuery(string $key, mixed $default = null): mixed
    {
        return $this->query->{$key} ?? $default;
    }

    public function getBodyAsStdClass(): stdClass
    {
        if (in_array($this->method, ['GET', 'HEAD', 'OPTIONS', 'DELETE'])) {
            return new stdClass();
        }
        return $this->body;
    }

    public function setAttribute(string $name, $value): self
    {
        if (property_exists($this, $name)) {
            throw new RuntimeException("Cannot override native property: {$name}");
        }

        $this->attributes[$name] = $value;
        $this->psr7Request = $this->psr7Request->withAttribute($name, $value);
        return $this;
    }

    public function hasAttribute(string $name): bool
    {
        return array_key_exists($name, $this->attributes);
    }

    public function removeAttribute(string $name): self
    {
        unset($this->attributes[$name]);
        $this->psr7Request = $this->psr7Request->withoutAttribute($name);
        return $this;
    }

    public function setAttributes(array $attributes): self
    {
        foreach ($attributes as $name => $value) {
            $this->setAttribute($name, $value);
        }
        return $this;
    }
}