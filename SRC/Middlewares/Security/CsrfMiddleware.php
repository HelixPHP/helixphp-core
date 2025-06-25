<?php
namespace Express\Middlewares\Security;

use Express\Helpers\Utils;

/**
 * Middleware de proteção CSRF (Cross-Site Request Forgery) para Express PHP.
 * Valida tokens CSRF em requisições POST, PUT, PATCH e DELETE.
 */
class CsrfMiddleware
{
    private $options;

    /**
     * @param array $options Opções de configuração:
     *   - headerName: string (nome do header para o token, default: 'X-CSRF-Token')
     *   - fieldName: string (nome do campo no body para o token, default: 'csrf_token')
     *   - excludePaths: array (caminhos que devem ser excluídos da verificação)
     *   - methods: array (métodos HTTP que requerem verificação CSRF)
     *   - generateTokenResponse: bool (se deve incluir o token na resposta)
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'headerName' => 'X-CSRF-Token',
            'fieldName' => 'csrf_token',
            'excludePaths' => [],
            'methods' => ['POST', 'PUT', 'PATCH', 'DELETE'],
            'generateTokenResponse' => false,
        ], $options);
    }

    public function __invoke($request, $response, $next)
    {
        // Inicializa sessão se não estiver ativa
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Gera token CSRF se não existir
        $csrfToken = Utils::csrfToken();

        // Verifica se o caminho atual deve ser excluído
        $currentPath = $request->path ?? $_SERVER['REQUEST_URI'];
        foreach ($this->options['excludePaths'] as $excludePath) {
            if (strpos($currentPath, $excludePath) === 0) {
                $next();
                return;
            }
        }

        // Adiciona token ao cabeçalho da resposta se configurado
        if ($this->options['generateTokenResponse']) {
            $response->header('X-CSRF-Token', $csrfToken);
        }

        // Verifica se o método requer validação CSRF
        $method = $request->method ?? $_SERVER['REQUEST_METHOD'];
        if (!in_array(strtoupper($method), $this->options['methods'])) {
            $next();
            return;
        }

        // Busca o token na requisição
        $submittedToken = null;

        // Primeiro tenta no header
        $headerName = 'HTTP_' . str_replace('-', '_', strtoupper($this->options['headerName']));
        if (isset($_SERVER[$headerName])) {
            $submittedToken = $_SERVER[$headerName];
        }

        // Se não encontrou no header, tenta no body
        if (!$submittedToken) {
            $body = $request->body ?? [];
            if (isset($body[$this->options['fieldName']])) {
                $submittedToken = $body[$this->options['fieldName']];
            }
        }

        // Se não encontrou no body, tenta no POST
        if (!$submittedToken && isset($_POST[$this->options['fieldName']])) {
            $submittedToken = $_POST[$this->options['fieldName']];
        }

        // Valida o token
        if (!$submittedToken || !Utils::checkCsrf($submittedToken)) {
            $response->status(403)->json([
                'error' => 'CSRF token validation failed',
                'message' => 'Invalid or missing CSRF token'
            ]);
            return;
        }

        // Token válido, continua
        $next();
    }

    /**
     * Método estático para obter o token CSRF atual
     * @return string
     */
    public static function getToken()
    {
        return Utils::csrfToken();
    }

    /**
     * Método estático para gerar um campo hidden HTML com o token CSRF
     * @return string
     */
    public static function hiddenField($fieldName = 'csrf_token')
    {
        $token = self::getToken();
        return "<input type=\"hidden\" name=\"{$fieldName}\" value=\"{$token}\">";
    }

    /**
     * Método estático para gerar um meta tag HTML com o token CSRF
     * @return string
     */
    public static function metaTag()
    {
        $token = self::getToken();
        return "<meta name=\"csrf-token\" content=\"{$token}\">";
    }
}
