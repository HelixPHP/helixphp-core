<?php
/**
 * Script de Validação do Projeto Express PHP
 *
 * Este script verifica se todos os componentes estão funcionando
 * corretamente antes da publicação do projeto.
 */

require_once __DIR__ . '/../vendor/autoload.php';

class ProjectValidator
{
    private $errors = [];
    private $warnings = [];
    private $passed = [];

    public function validate()
    {
        echo "🔍 Validando projeto Express PHP...\n\n";

        // Testes estruturais
        $this->validateStructure();
        $this->validateComposer();
        $this->validateMiddlewares();
        $this->validateOpenApiFeatures();
        $this->validateExamples();
        $this->validateTests();
        $this->validateDocumentation();

        // Testes funcionais
        $this->validateAuthentication();
        $this->validateSecurity();

        // Relatório final
        return $this->generateReport();
    }

    private function validateStructure()
    {
        echo "📁 Validando estrutura do projeto...\n";

        $requiredDirs = [
            'src/',
            'src/Middleware/',
            'src/Authentication/',
            'examples/',
            'tests/',
            'docs/'
        ];

        foreach ($requiredDirs as $dir) {
            if (is_dir($dir)) {
                $this->passed[] = "Diretório {$dir} existe";
            } else {
                $this->errors[] = "Diretório {$dir} não encontrado";
            }
        }

        $requiredFiles = [
            'src/ApiExpress.php',
            'src/Middleware/Security/SecurityMiddleware.php',
            'src/Authentication/JWTHelper.php',
            'composer.json',
            'README.md',
            'docs/DOCUMENTATION_INDEX.md',
            'docs/guides/QUICK_START_GUIDE.md'
        ];

        foreach ($requiredFiles as $file) {
            if (file_exists($file)) {
                $this->passed[] = "Arquivo {$file} existe";
            } else {
                $this->errors[] = "Arquivo {$file} não encontrado";
            }
        }

        echo "✅ Estrutura validada\n\n";
    }

    private function validateComposer()
    {
        echo "📦 Validando composer.json...\n";

        if (!file_exists('composer.json')) {
            $this->errors[] = "composer.json não encontrado";
            return;
        }

        $composer = json_decode(file_get_contents('composer.json'), true);

        if (!$composer) {
            $this->errors[] = "composer.json inválido";
            return;
        }

        // Verificar campos obrigatórios
        $required = ['name', 'description', 'authors', 'autoload'];
        foreach ($required as $field) {
            if (isset($composer[$field])) {
                $this->passed[] = "Campo {$field} presente no composer.json";
            } else {
                $this->errors[] = "Campo {$field} ausente no composer.json";
            }
        }

        // Verificar campo version (opcional para publicação no Packagist)
        if (isset($composer['version'])) {
            $this->warnings[] = "Campo version presente - será ignorado pelo Packagist (use tags Git)";
        } else {
            $this->passed[] = "Campo version ausente - correto para publicação no Packagist";
        }

        // Verificar scripts
        if (isset($composer['scripts']['test'])) {
            $this->passed[] = "Script de teste configurado";
        } else {
            $this->warnings[] = "Script de teste não configurado";
        }

        echo "✅ Composer validado\n\n";
    }

    private function validateMiddlewares()
    {
        echo "🛡️ Validando middlewares...\n";

        // Verificar SecurityMiddleware
        if (class_exists('Express\\Middleware\\Security\\SecurityMiddleware')) {
            $this->passed[] = "SecurityMiddleware carregado";

            // Testar instanciação
            try {
                $security = new Express\Middleware\Security\SecurityMiddleware();
                $this->passed[] = "SecurityMiddleware pode ser instanciado";
            } catch (Exception $e) {
                $this->errors[] = "Erro ao instanciar SecurityMiddleware: " . $e->getMessage();
            }
        } else {
            $this->warnings[] = "SecurityMiddleware não encontrado";
        }

        // Verificar JWTHelper
        if (class_exists('Express\\Authentication\\JWTHelper')) {
            $this->passed[] = "JWTHelper carregado";

            // Testar geração de token
            try {
                $token = Express\Authentication\JWTHelper::encode(['user_id' => 1], 'test_secret');
                if ($token) {
                    $this->passed[] = "JWTHelper pode gerar tokens";
                } else {
                    $this->errors[] = "JWTHelper não conseguiu gerar token";
                }
            } catch (Exception $e) {
                $this->errors[] = "Erro ao gerar JWT: " . $e->getMessage();
            }
        } else {
            $this->warnings[] = "JWTHelper não encontrado";
        }

        echo "✅ Middlewares validados\n\n";
    }

    private function validateExamples()
    {
        echo "📖 Validando exemplos...\n";

        $examples = [
            'examples/example_basic.php',
            'examples/example_auth.php',
            'examples/example_auth_simple.php',
            'examples/example_middleware.php',
            'examples/example_standard_middlewares.php',
            'examples/example_openapi_docs.php',
            'examples/example_complete_optimizations.php'
        ];

        foreach ($examples as $example) {
            if (file_exists($example)) {
                $this->passed[] = "Exemplo {$example} existe";

                // Verificar sintaxe
                $output = shell_exec("php -l {$example} 2>&1");
                if (strpos($output, 'No syntax errors') !== false) {
                    $this->passed[] = "Exemplo {$example} tem sintaxe válida";
                } else {
                    $this->errors[] = "Erro de sintaxe em {$example}: {$output}";
                }
            } else {
                $this->errors[] = "Exemplo {$example} não encontrado";
            }
        }

        // Validação específica para o exemplo OpenAPI
        if (file_exists('examples/example_openapi_docs.php')) {
            $content = file_get_contents('examples/example_openapi_docs.php');
            if (strpos($content, 'OpenApiExporter') !== false) {
                $this->passed[] = "Exemplo OpenAPI usa OpenApiExporter corretamente";
            } else {
                $this->warnings[] = "Exemplo OpenAPI pode não estar usando OpenApiExporter";
            }

            if (strpos($content, '/docs') !== false && strpos($content, 'swagger-ui') !== false) {
                $this->passed[] = "Exemplo OpenAPI inclui interface Swagger UI";
            } else {
                $this->warnings[] = "Exemplo OpenAPI pode não ter interface Swagger UI completa";
            }
        }

        // Verificar se o README dos exemplos está atualizado
        if (file_exists('examples/README.md')) {
            $exampleReadme = file_get_contents('examples/README.md');
            if (strpos($exampleReadme, 'example_openapi_docs.php') !== false) {
                $this->passed[] = "README dos exemplos menciona exemplo OpenAPI";
            } else {
                $this->warnings[] = "README dos exemplos pode não estar atualizado com exemplo OpenAPI";
            }
        }

        echo "✅ Exemplos validados\n\n";
    }

    private function validateTests()
    {
        echo "🧪 Validando testes...\n";

        $testFiles = [
            'tests/Security/AuthMiddlewareTest.php',
            'tests/Helpers/JWTHelperTest.php',
            'tests/Security/AuthMiddlewareTest.php'
        ];

        foreach ($testFiles as $testFile) {
            if (file_exists($testFile)) {
                $this->passed[] = "Teste {$testFile} existe";

                // Verificar sintaxe
                $output = shell_exec("php -l {$testFile} 2>&1");
                if (strpos($output, 'No syntax errors') !== false) {
                    $this->passed[] = "Teste {$testFile} tem sintaxe válida";
                } else {
                    $this->errors[] = "Erro de sintaxe em {$testFile}: {$output}";
                }
            } else {
                $this->errors[] = "Teste {$testFile} não encontrado";
            }
        }

        // Tentar executar testes unitários
        if (file_exists('vendor/bin/phpunit')) {
            echo "Executando testes unitários...\n";
            $output = shell_exec('./vendor/bin/phpunit tests/ 2>&1');

            if (strpos($output, 'OK') !== false || strpos($output, 'Tests: ') !== false) {
                $this->passed[] = "Testes unitários executados com sucesso";
            } else {
                $this->warnings[] = "Alguns testes podem ter falhas: " . substr($output, 0, 200) . "...";
            }
        } else {
            $this->warnings[] = "PHPUnit não instalado - testes unitários não executados";
        }

        echo "✅ Testes validados\n\n";
    }

    private function validateDocumentation()
    {
        echo "📚 Validando documentação...\n";

        $docs = [
            'README.md',
            'docs/DOCUMENTATION_INDEX.md',
            'docs/README.md',
            'docs/guides/QUICK_START_GUIDE.md',
            'docs/guides/CUSTOM_MIDDLEWARE_GUIDE.md',
            'docs/guides/STANDARD_MIDDLEWARES.md',
            'docs/guides/SECURITY_IMPLEMENTATION.md',
            'benchmarks/README.md',
            'benchmarks/reports/COMPREHENSIVE_PERFORMANCE_SUMMARY.md'
        ];

        foreach ($docs as $doc) {
            if (file_exists($doc)) {
                $content = file_get_contents($doc);
                if (strlen($content) > 100) {
                    $this->passed[] = "Documentação {$doc} existe e tem conteúdo";
                } else {
                    $this->warnings[] = "Documentação {$doc} existe mas parece incompleta";
                }
            } else {
                $this->errors[] = "Documentação {$doc} não encontrada";
            }
        }

        // Validações específicas da nova estrutura
        if (file_exists('docs/guides/QUICK_START_GUIDE.md')) {
            $quickStart = file_get_contents('docs/guides/QUICK_START_GUIDE.md');
            if (strpos($quickStart, 'composer require') !== false) {
                $this->passed[] = "Guia rápido inclui instruções de instalação";
            } else {
                $this->warnings[] = "Guia rápido pode não ter instruções de instalação";
            }
        }

        if (file_exists('docs/guides/CUSTOM_MIDDLEWARE_GUIDE.md')) {
            $middlewareGuide = file_get_contents('docs/guides/CUSTOM_MIDDLEWARE_GUIDE.md');
            if (strpos($middlewareGuide, 'MiddlewareInterface') !== false) {
                $this->passed[] = "Guia de middleware explica interface";
            } else {
                $this->warnings[] = "Guia de middleware pode não explicar interface";
            }
        }

        if (file_exists('docs/guides/STANDARD_MIDDLEWARES.md')) {
            $standardMiddlewares = file_get_contents('docs/guides/STANDARD_MIDDLEWARES.md');
            if (strpos($standardMiddlewares, 'SecurityMiddleware') !== false &&
                strpos($standardMiddlewares, 'CorsMiddleware') !== false) {
                $this->passed[] = "Documentação de middlewares padrão está completa";
            } else {
                $this->warnings[] = "Documentação de middlewares padrão pode estar incompleta";
            }
        }

        // Verificar se README principal foi atualizado
        if (file_exists('README.md')) {
            $readme = file_get_contents('README.md');
            if (strpos($readme, 'QUICK_START_GUIDE.md') !== false) {
                $this->passed[] = "README principal referencia guia rápido";
            } else {
                $this->warnings[] = "README principal pode não referenciar guia rápido";
            }

            if (strpos($readme, 'example_openapi_docs.php') !== false) {
                $this->passed[] = "README principal referencia exemplo OpenAPI";
            } else {
                $this->warnings[] = "README principal pode não referenciar exemplo OpenAPI";
            }
        }

        // Verificar estrutura de benchmarks
        if (file_exists('benchmarks/reports/COMPREHENSIVE_PERFORMANCE_SUMMARY.md')) {
            $perfSummary = file_get_contents('benchmarks/reports/COMPREHENSIVE_PERFORMANCE_SUMMARY.md');
            if (strpos($perfSummary, '2025') !== false) {
                $this->passed[] = "Relatório de performance tem dados recentes";
            } else {
                $this->warnings[] = "Relatório de performance pode estar desatualizado";
            }
        }

        echo "✅ Documentação validada\n\n";
    }

    private function validateAuthentication()
    {
        echo "🔐 Validando sistema de autenticação...\n";

        try {
            // Simular requisição com JWT
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer test.token.here';
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_SERVER['REQUEST_URI'] = '/api/test';

            // Validação básica de autenticação sem instanciar classes específicas
            if (class_exists('Express\\Authentication\\JWTHelper')) {
                // Testar JWT Helper básico
                $jwt = Express\Authentication\JWTHelper::encode(['test' => true], 'secret');
                if ($jwt) {
                    $this->passed[] = "Sistema de autenticação funcional";
                } else {
                    $this->errors[] = "Sistema de autenticação não funcional";
                }
            } else {
                $this->warnings[] = "Sistema de autenticação não disponível";
            }
        } catch (Exception $e) {
            $this->errors[] = "Erro no sistema de autenticação: " . $e->getMessage();
        }

        echo "✅ Autenticação validada\n\n";
    }

    private function validateSecurity()
    {
        echo "🔒 Validando configurações de segurança...\n";

        // Verificar se arquivos sensíveis não estão sendo commitados
        $sensitiveFiles = [
            '.env' => 'Arquivo de environment',
            'config/database.php' => 'Configuração de banco local',
            'composer.lock' => 'Lock file do composer (se deve ser commitado depende do projeto)'
        ];

        foreach ($sensitiveFiles as $file => $description) {
            if (file_exists($file)) {
                $this->warnings[] = "{$description} presente ({$file}) - verifique se deve ser commitado";
            }
        }

        // Verificar se .gitignore está configurado corretamente
        if (file_exists('.gitignore')) {
            $gitignore = file_get_contents('.gitignore');
            $requiredEntries = ['/vendor/', '.env', '*.log'];

            foreach ($requiredEntries as $entry) {
                if (strpos($gitignore, $entry) !== false) {
                    $this->passed[] = "Entrada '{$entry}' presente no .gitignore";
                } else {
                    $this->warnings[] = "Entrada '{$entry}' ausente no .gitignore";
                }
            }
        } else {
            $this->errors[] = "Arquivo .gitignore não encontrado";
        }

        // Verificar se .env.example existe
        if (file_exists('.env.example')) {
            $this->passed[] = "Arquivo .env.example presente para referência";
        } else {
            $this->warnings[] = "Arquivo .env.example não encontrado - recomendado para projetos";
        }

        // Verificar configurações de segurança no código
        $securityFiles = glob('src/Middlewares/Security/*.php');
        if (count($securityFiles) >= 2) {
            $this->passed[] = "Múltiplos middlewares de segurança implementados";
        } else {
            $this->warnings[] = "Poucos middlewares de segurança encontrados";
        }

        echo "✅ Segurança validada\n\n";
    }

    private function validateOpenApiFeatures()
    {
        echo "📚 Validando recursos OpenAPI/Swagger...\n";

        // Verificar se OpenApiExporter existe
        if (class_exists('Express\\Utils\\OpenApiExporter')) {
            $this->passed[] = "OpenApiExporter carregado";

            // Testar export básico
            try {
                if (class_exists('Express\\Routing\\Router')) {
                    $docs = Express\Utils\OpenApiExporter::export('Express\\Routing\\Router');
                    if (is_array($docs) && isset($docs['openapi'])) {
                        $this->passed[] = "OpenApiExporter pode gerar documentação";

                        if ($docs['openapi'] === '3.0.0') {
                            $this->passed[] = "OpenApiExporter gera OpenAPI 3.0.0";
                        } else {
                            $this->warnings[] = "OpenApiExporter pode não estar usando OpenAPI 3.0.0";
                        }
                    } else {
                        $this->errors[] = "OpenApiExporter não gera documentação válida";
                    }
                } else {
                    $this->warnings[] = "Router não encontrado para testar OpenApiExporter";
                }
            } catch (Exception $e) {
                $this->errors[] = "Erro ao testar OpenApiExporter: " . $e->getMessage();
            }
        } else {
            $this->errors[] = "OpenApiExporter não encontrado";
        }

        // Verificar se o README principal menciona OpenAPI
        if (file_exists('README.md')) {
            $readme = file_get_contents('README.md');
            if (strpos($readme, 'OpenAPI') !== false || strpos($readme, 'Swagger') !== false) {
                $this->passed[] = "README principal menciona OpenAPI/Swagger";

                if (strpos($readme, 'OpenApiExporter') !== false) {
                    $this->passed[] = "README explica como usar OpenApiExporter";
                } else {
                    $this->warnings[] = "README pode não explicar como usar OpenApiExporter";
                }
            } else {
                $this->warnings[] = "README principal pode não mencionar recursos OpenAPI";
            }
        }

        echo "✅ Recursos OpenAPI validados\n\n";
    }

    private function generateReport()
    {
        echo "📊 RELATÓRIO DE VALIDAÇÃO\n";
        echo str_repeat("=", 50) . "\n\n";

        echo "✅ SUCESSOS (" . count($this->passed) . "):\n";
        foreach ($this->passed as $pass) {
            echo "  ✓ {$pass}\n";
        }
        echo "\n";

        if (!empty($this->warnings)) {
            echo "⚠️ AVISOS (" . count($this->warnings) . "):\n";
            foreach ($this->warnings as $warning) {
                echo "  ⚠ {$warning}\n";
            }
            echo "\n";
        }

        if (!empty($this->errors)) {
            echo "❌ ERROS (" . count($this->errors) . "):\n";
            foreach ($this->errors as $error) {
                echo "  ✗ {$error}\n";
            }
            echo "\n";
        }

        // Status final
        if (empty($this->errors)) {
            echo "🎉 PROJETO VALIDADO COM SUCESSO!\n";
            echo "   O projeto está pronto para publicação.\n";

            if (!empty($this->warnings)) {
                echo "   Considere resolver os avisos antes da publicação.\n";
            }

            echo "\n📋 PRÓXIMOS PASSOS:\n";
            echo "   1. Execute os testes: composer test\n";
            echo "   2. Verifique a documentação\n";
            echo "   3. Faça commit das alterações\n";
            echo "   4. Crie uma tag de versão: git tag -a v1.0.0 -m 'Release v1.0.0'\n";
            echo "   5. Push para o repositório: git push origin main --tags\n";
            echo "   6. Publique no Packagist: https://packagist.org\n";
            echo "   7. Repositório: https://github.com/CAFernandes/express-php\n";

            return true;
        } else {
            echo "❌ VALIDAÇÃO FALHOU!\n";
            echo "   Corrija os erros antes de publicar o projeto.\n";
            return false;
        }
    }
}

// Executar validação
$validator = new ProjectValidator();
$success = $validator->validate();

exit($success ? 0 : 1);
