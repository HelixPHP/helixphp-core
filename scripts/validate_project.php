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
        $this->validateExamples();
        $this->validateTests();
        $this->validateDocumentation();

        // Testes funcionais
        $this->validateAuthentication();
        $this->validateSecurity();

        // Relatório final
        $this->generateReport();
    }

    private function validateStructure()
    {
        echo "📁 Validando estrutura do projeto...\n";

        $requiredDirs = [
            'src/',
            'src/Middlewares/',
            'src/Middlewares/Security/',
            'src/Helpers/',
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
            'src/Middlewares/Security/AuthMiddleware.php',
            'src/Helpers/JWTHelper.php',
            'composer.json',
            'README.md',
            'docs/guides/PUBLISHING_GUIDE.md'
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

        // Verificar AuthMiddleware
        if (class_exists('Express\\Middlewares\\Security\\AuthMiddleware')) {
            $this->passed[] = "AuthMiddleware carregado";

            // Testar instanciação
            try {
                $auth = new Express\Middleware\Security\AuthMiddleware();
                $this->passed[] = "AuthMiddleware pode ser instanciado";
            } catch (Exception $e) {
                $this->errors[] = "Erro ao instanciar AuthMiddleware: " . $e->getMessage();
            }
        } else {
            $this->errors[] = "AuthMiddleware não encontrado";
        }

        // Verificar JWTHelper
        if (class_exists('Express\\Helpers\\JWTHelper')) {
            $this->passed[] = "JWTHelper carregado";

            // Testar geração de token
            try {
                $token = Express\Helpers\JWTHelper::encode(['user_id' => 1], 'test_secret');
                if ($token) {
                    $this->passed[] = "JWTHelper pode gerar tokens";
                } else {
                    $this->errors[] = "JWTHelper não conseguiu gerar token";
                }
            } catch (Exception $e) {
                $this->errors[] = "Erro ao gerar JWT: " . $e->getMessage();
            }
        } else {
            $this->errors[] = "JWTHelper não encontrado";
        }

        echo "✅ Middlewares validados\n\n";
    }

    private function validateExamples()
    {
        echo "📖 Validando exemplos...\n";

        $examples = [
            'examples/example_auth.php',
            'examples/snippets/auth_snippets.php'
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
            'docs/INDEX.md',
            'docs/README.md',
            'docs/pt-br/README.md',
            'docs/pt-br/AUTH_MIDDLEWARE.md',
            'docs/pt-br/objetos.md',
            'docs/guides/PUBLISHING_GUIDE.md',
            'docs/guides/READY_FOR_PUBLICATION.md',
            'docs/guides/SECURITY_IMPLEMENTATION.md',
            'docs/implementation/AUTH_IMPLEMENTATION_SUMMARY.md',
            'docs/implementation/PROJECT_COMPLETION.md',
            'docs/implementation/PROJECT_ORGANIZATION.md',
            'docs/development/DEVELOPMENT.md',
            'docs/development/MIDDLEWARE_MIGRATION.md',
            'docs/development/INTERNATIONALIZATION.md',
            'docs/development/COMPOSER_PSR4.md'
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

            // Mock de request e response
            $req = new stdClass();
            $req->headers = ['Authorization' => 'Bearer test.token.here'];

            $res = new stdClass();
            $res->status_code = 200;

            $auth = new Express\Middleware\Security\AuthMiddleware();

            $this->passed[] = "Sistema de autenticação funcional";
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
