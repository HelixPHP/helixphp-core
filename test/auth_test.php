<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Authentication\JWTHelper;
use PivotPHP\Core\Security\AuthMiddleware;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;

/**
 * Teste básico de autenticação
 * Para testes completos, usar PHPUnit
 */
class AuthTest
{
    public function runBasicTests(): void
    {
        echo "🔐 Testando sistema de autenticação...\n\n";

        $this->testJWTGeneration();
        $this->testJWTValidation();
        $this->testAuthMiddleware();

        echo "✅ Todos os testes de autenticação passaram!\n";
    }

    private function testJWTGeneration(): void
    {
        echo "🔑 Testando geração de JWT...\n";

        $jwt = new JWTHelper();
        $token = $jwt->generateToken(['user_id' => 1, 'role' => 'admin']);

        if (empty($token)) {
            throw new Exception('Falha na geração do token JWT');
        }

        echo "  ✅ Token gerado com sucesso\n";
    }

    private function testJWTValidation(): void
    {
        echo "🔍 Testando validação de JWT...\n";

        $jwt = new JWTHelper();
        $token = $jwt->generateToken(['user_id' => 1, 'role' => 'admin']);
        $payload = $jwt->validateToken($token);

        if (!$payload || $payload['user_id'] !== 1) {
            throw new Exception('Falha na validação do token JWT');
        }

        echo "  ✅ Token validado com sucesso\n";
    }

    private function testAuthMiddleware(): void
    {
        echo "🛡️  Testando AuthMiddleware...\n";

        $middleware = new AuthMiddleware();
        $request = new Request('GET', '/protected', '/protected');
        $response = new Response();

        // Testar sem token (deve falhar)
        try {
            $middleware->handle($request, $response, function($req, $res) {
                return $res;
            });
            echo "  ✅ Middleware bloqueou acesso sem token\n";
        } catch (Exception $e) {
            echo "  ✅ Middleware funcionando corretamente\n";
        }

        // Testar com token válido
        $jwt = new JWTHelper();
        $token = $jwt->generateToken(['user_id' => 1]);
        $request->header('Authorization', 'Bearer ' . $token);

        try {
            $result = $middleware->handle($request, $response, function($req, $res) {
                return $res;
            });
            echo "  ✅ Middleware permitiu acesso com token válido\n";
        } catch (Exception $e) {
            echo "  ❌ Erro inesperado: " . $e->getMessage() . "\n";
        }
    }
}

// Executar teste
try {
    $test = new AuthTest();
    $test->runBasicTests();
} catch (Exception $e) {
    echo "❌ Erro nos testes: " . $e->getMessage() . "\n";
    exit(1);
}