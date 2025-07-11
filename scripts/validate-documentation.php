<?php
/**
 * Script de validação de documentação
 * Verifica se todas as classes e métodos públicos estão documentados
 */

declare(strict_types=1);

$basePath = dirname(__DIR__);
$srcPath = $basePath . '/src';

// Cores para output
const RED = "\033[0;31m";
const GREEN = "\033[0;32m";
const YELLOW = "\033[1;33m";
const BLUE = "\033[0;34m";
const NC = "\033[0m"; // No Color

function log(string $message): void {
    echo BLUE . "[" . date('Y-m-d H:i:s') . "]" . NC . " $message\n";
}

function success(string $message): void {
    echo GREEN . "✅ $message" . NC . "\n";
}

function warning(string $message): void {
    echo YELLOW . "⚠️  $message" . NC . "\n";
}

function error(string $message): void {
    echo RED . "❌ $message" . NC . "\n";
}

function info(string $message): void {
    echo BLUE . "ℹ️  $message" . NC . "\n";
}

/**
 * Encontra todos os arquivos PHP no diretório src
 */
function findPhpFiles(string $directory): array {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory)
    );
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
    
    return $files;
}

/**
 * Extrai informações sobre classes, interfaces e traits de um arquivo
 */
function extractClassInfo(string $filePath): array {
    $content = file_get_contents($filePath);
    if ($content === false) {
        return [];
    }
    
    $classes = [];
    $lines = explode("\n", $content);
    
    for ($i = 0; $i < count($lines); $i++) {
        $line = trim($lines[$i]);
        
        // Procurar por definições de classe, interface ou trait
        if (preg_match('/^(abstract\s+)?(final\s+)?(class|interface|trait)\s+(\w+)/', $line, $matches)) {
            $type = $matches[3];
            $name = $matches[4];
            
            // Verificar se há DocBlock antes da definição
            $hasDocBlock = false;
            $docBlockLines = [];
            
            // Procurar DocBlock nas linhas anteriores
            for ($j = $i - 1; $j >= 0; $j--) {
                $prevLine = trim($lines[$j]);
                
                if ($prevLine === '*/') {
                    $hasDocBlock = true;
                    break;
                }
                
                if ($prevLine === '' || strpos($prevLine, '*') === 0) {
                    continue;
                }
                
                break;
            }
            
            // Se encontrou DocBlock, extrair conteúdo
            if ($hasDocBlock) {
                for ($j = $i - 1; $j >= 0; $j--) {
                    $prevLine = trim($lines[$j]);
                    
                    if ($prevLine === '/**') {
                        break;
                    }
                    
                    if (strpos($prevLine, '*') === 0) {
                        $docBlockLines[] = substr($prevLine, 1);
                    }
                }
                
                $docBlockLines = array_reverse($docBlockLines);
            }
            
            $classes[] = [
                'type' => $type,
                'name' => $name,
                'line' => $i + 1,
                'hasDocBlock' => $hasDocBlock,
                'docBlock' => $docBlockLines
            ];
        }
    }
    
    return $classes;
}

/**
 * Extrai informações sobre métodos públicos de um arquivo
 */
function extractMethodInfo(string $filePath): array {
    $content = file_get_contents($filePath);
    if ($content === false) {
        return [];
    }
    
    $methods = [];
    $lines = explode("\n", $content);
    
    for ($i = 0; $i < count($lines); $i++) {
        $line = trim($lines[$i]);
        
        // Procurar por métodos públicos
        if (preg_match('/^public\s+(static\s+)?function\s+(\w+)\s*\(/', $line, $matches)) {
            $isStatic = !empty($matches[1]);
            $name = $matches[2];
            
            // Pular construtores e métodos mágicos básicos
            if (in_array($name, ['__construct', '__destruct', '__toString', '__invoke'])) {
                continue;
            }
            
            // Verificar se há DocBlock antes da definição
            $hasDocBlock = false;
            $docBlockLines = [];
            
            // Procurar DocBlock nas linhas anteriores
            for ($j = $i - 1; $j >= 0; $j--) {
                $prevLine = trim($lines[$j]);
                
                if ($prevLine === '*/') {
                    $hasDocBlock = true;
                    break;
                }
                
                if ($prevLine === '' || strpos($prevLine, '*') === 0) {
                    continue;
                }
                
                break;
            }
            
            // Se encontrou DocBlock, extrair conteúdo
            if ($hasDocBlock) {
                for ($j = $i - 1; $j >= 0; $j--) {
                    $prevLine = trim($lines[$j]);
                    
                    if ($prevLine === '/**') {
                        break;
                    }
                    
                    if (strpos($prevLine, '*') === 0) {
                        $docBlockLines[] = substr($prevLine, 1);
                    }
                }
                
                $docBlockLines = array_reverse($docBlockLines);
            }
            
            $methods[] = [
                'name' => $name,
                'line' => $i + 1,
                'isStatic' => $isStatic,
                'hasDocBlock' => $hasDocBlock,
                'docBlock' => $docBlockLines
            ];
        }
    }
    
    return $methods;
}

/**
 * Valida a qualidade de um DocBlock
 */
function validateDocBlock(array $docBlock): array {
    $issues = [];
    
    if (empty($docBlock)) {
        $issues[] = 'DocBlock vazio';
        return $issues;
    }
    
    $content = implode(' ', $docBlock);
    
    // Verificar se tem descrição
    if (strlen(trim($content)) < 10) {
        $issues[] = 'Descrição muito curta';
    }
    
    // Verificar se tem @param para métodos que provavelmente têm parâmetros
    if (strpos($content, 'function') !== false && strpos($content, '@param') === false) {
        $issues[] = 'Possível falta de @param';
    }
    
    // Verificar se tem @return para métodos que provavelmente retornam valor
    if (strpos($content, 'function') !== false && strpos($content, '@return') === false) {
        $issues[] = 'Possível falta de @return';
    }
    
    return $issues;
}

// Início do script
log("🔍 Iniciando validação de documentação...");

if (!is_dir($srcPath)) {
    error("Diretório src não encontrado: $srcPath");
    exit(1);
}

$phpFiles = findPhpFiles($srcPath);
log("📁 Encontrados " . count($phpFiles) . " arquivos PHP");

$totalClasses = 0;
$classesWithDocBlock = 0;
$classesWithoutDocBlock = [];

$totalMethods = 0;
$methodsWithDocBlock = 0;
$methodsWithoutDocBlock = [];

$docBlockQualityIssues = [];

foreach ($phpFiles as $filePath) {
    $relativePath = str_replace($basePath . '/', '', $filePath);
    
    // Extrair informações das classes
    $classes = extractClassInfo($filePath);
    foreach ($classes as $class) {
        $totalClasses++;
        
        if ($class['hasDocBlock']) {
            $classesWithDocBlock++;
            
            // Validar qualidade do DocBlock
            $issues = validateDocBlock($class['docBlock']);
            if (!empty($issues)) {
                $docBlockQualityIssues[] = [
                    'file' => $relativePath,
                    'type' => 'class',
                    'name' => $class['name'],
                    'line' => $class['line'],
                    'issues' => $issues
                ];
            }
        } else {
            $classesWithoutDocBlock[] = [
                'file' => $relativePath,
                'name' => $class['name'],
                'line' => $class['line'],
                'type' => $class['type']
            ];
        }
    }
    
    // Extrair informações dos métodos
    $methods = extractMethodInfo($filePath);
    foreach ($methods as $method) {
        $totalMethods++;
        
        if ($method['hasDocBlock']) {
            $methodsWithDocBlock++;
            
            // Validar qualidade do DocBlock
            $issues = validateDocBlock($method['docBlock']);
            if (!empty($issues)) {
                $docBlockQualityIssues[] = [
                    'file' => $relativePath,
                    'type' => 'method',
                    'name' => $method['name'],
                    'line' => $method['line'],
                    'issues' => $issues
                ];
            }
        } else {
            $methodsWithoutDocBlock[] = [
                'file' => $relativePath,
                'name' => $method['name'],
                'line' => $method['line'],
                'isStatic' => $method['isStatic']
            ];
        }
    }
}

// Relatório
echo "\n";
echo "=========================================\n";
echo "    RELATÓRIO DE DOCUMENTAÇÃO\n";
echo "=========================================\n";
echo "\n";

echo "📊 Estatísticas Gerais:\n";
echo "  • Classes/Interfaces/Traits: $totalClasses\n";
echo "  • Classes documentadas: $classesWithDocBlock\n";
echo "  • Métodos públicos: $totalMethods\n";
echo "  • Métodos documentados: $methodsWithDocBlock\n";
echo "\n";

// Calcular percentuais
$classDocPercentage = $totalClasses > 0 ? ($classesWithDocBlock / $totalClasses) * 100 : 0;
$methodDocPercentage = $totalMethods > 0 ? ($methodsWithDocBlock / $totalMethods) * 100 : 0;

echo "📈 Cobertura de Documentação:\n";
echo "  • Classes: " . number_format($classDocPercentage, 1) . "%\n";
echo "  • Métodos: " . number_format($methodDocPercentage, 1) . "%\n";
echo "\n";

// Listar classes sem documentação
if (!empty($classesWithoutDocBlock)) {
    echo "❌ Classes sem documentação:\n";
    foreach ($classesWithoutDocBlock as $class) {
        echo "  • {$class['file']}:{$class['line']} - {$class['type']} {$class['name']}\n";
    }
    echo "\n";
}

// Listar métodos sem documentação
if (!empty($methodsWithoutDocBlock)) {
    echo "❌ Métodos sem documentação:\n";
    $count = 0;
    foreach ($methodsWithoutDocBlock as $method) {
        if ($count >= 10) {
            echo "  • ... e " . (count($methodsWithoutDocBlock) - 10) . " outros\n";
            break;
        }
        $static = $method['isStatic'] ? 'static ' : '';
        echo "  • {$method['file']}:{$method['line']} - {$static}{$method['name']}()\n";
        $count++;
    }
    echo "\n";
}

// Listar problemas de qualidade
if (!empty($docBlockQualityIssues)) {
    echo "⚠️  Problemas de qualidade em DocBlocks:\n";
    $count = 0;
    foreach ($docBlockQualityIssues as $issue) {
        if ($count >= 5) {
            echo "  • ... e " . (count($docBlockQualityIssues) - 5) . " outros\n";
            break;
        }
        echo "  • {$issue['file']}:{$issue['line']} - {$issue['type']} {$issue['name']}\n";
        echo "    Issues: " . implode(', ', $issue['issues']) . "\n";
        $count++;
    }
    echo "\n";
}

// Decisão final
$criticalIssues = count($classesWithoutDocBlock) + count($methodsWithoutDocBlock);
$qualityIssues = count($docBlockQualityIssues);

echo "🎯 Avaliação Final:\n";

if ($classDocPercentage >= 100 && $methodDocPercentage >= 95) {
    success("DOCUMENTAÇÃO APROVADA");
    echo "  • Cobertura de classes: " . number_format($classDocPercentage, 1) . "% (≥100%)\n";
    echo "  • Cobertura de métodos: " . number_format($methodDocPercentage, 1) . "% (≥95%)\n";
    $exitCode = 0;
} else {
    error("DOCUMENTAÇÃO REPROVADA");
    echo "  • Cobertura de classes: " . number_format($classDocPercentage, 1) . "% (requer 100%)\n";
    echo "  • Cobertura de métodos: " . number_format($methodDocPercentage, 1) . "% (requer 95%)\n";
    $exitCode = 1;
}

echo "\n";
echo "📋 Resumo de Problemas:\n";
echo "  • Classes sem documentação: " . count($classesWithoutDocBlock) . "\n";
echo "  • Métodos sem documentação: " . count($methodsWithoutDocBlock) . "\n";
echo "  • Problemas de qualidade: " . count($docBlockQualityIssues) . "\n";
echo "\n";

if ($exitCode === 0) {
    success("Documentação atende aos critérios de qualidade!");
} else {
    error("Documentação não atende aos critérios de qualidade!");
    echo "\n";
    echo "🔧 Ações necessárias:\n";
    echo "  1. Adicionar DocBlocks às classes sem documentação\n";
    echo "  2. Adicionar DocBlocks aos métodos públicos\n";
    echo "  3. Melhorar qualidade dos DocBlocks existentes\n";
    echo "  4. Executar validação novamente\n";
}

echo "\n";

exit($exitCode);