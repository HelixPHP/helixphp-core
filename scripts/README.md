# PivotPHP Core - Scripts Directory

## 📋 Visão Geral

Este diretório contém todos os scripts de automação, validação e gerenciamento do PivotPHP Core. **Todos os scripts foram consolidados e otimizados** com detecção automática de versão e remoção de hardcoding.

## 🚀 Scripts Principais (Uso Diário)

### 🔍 Validação de Qualidade
```bash
# Validação completa de qualidade (RECOMENDADO)
scripts/quality-check.sh

# Validação abrangente do projeto
scripts/validate_all.sh

# Validação específica de documentação
scripts/validate-documentation.php
```

### 📦 Gerenciamento de Versões
```bash
# Incrementar versão patch (1.1.4 → 1.1.5)
scripts/version-bump.sh patch

# Incrementar versão minor (1.1.4 → 1.2.0)
scripts/version-bump.sh minor

# Incrementar versão major (1.1.4 → 2.0.0)
scripts/version-bump.sh major

# Ver próxima versão sem aplicar
scripts/version-bump.sh minor --dry-run
```

### 🚢 Release e Deploy
```bash
# Preparar para release
scripts/prepare_release.sh

# Executar release final
scripts/release.sh
```

## 🔧 Scripts de Validação Específica

### 📖 Documentação
```bash
# Validar documentação de código (DocBlocks)
scripts/validate-documentation.php

# Validar documentação de arquivos markdown
scripts/validate-docs.sh

# Validar recursos OpenAPI/Swagger
scripts/validate_openapi.sh
```

### 🧪 Testes e Qualidade
```bash
# Executar testes de stress
scripts/run_stress_tests.sh

# Testar em múltiplas versões PHP (8.1-8.4)
scripts/test-all-php-versions.sh

# Validar padrões PSR-12
scripts/validate-psr12.php

# Validar estrutura de benchmarks
scripts/validate_benchmarks.sh

# Validar projeto completo
scripts/validate_project.php
```

## ⚙️ Scripts Utilitários

### 🔄 Configuração e Desenvolvimento
```bash
# Hook de pre-commit
scripts/pre-commit

# Hook de pre-push  
scripts/pre-push

# Alternar versão PSR-7 para testes
scripts/switch-psr7-version.php
```

### 📚 Biblioteca Compartilhada
```bash
# Utilitários de detecção de versão e projeto
scripts/lib/version-utils.sh
```

## 🎯 Scripts por Categoria

### 📊 Qualidade e Validação (5 scripts)
- `quality-check.sh` - ⭐ **Principal**: Validação completa de qualidade
- `validate_all.sh` - Orchestrador de todas as validações
- `validate_project.php` - Validação estrutural do projeto
- `validate-documentation.php` - Validação de documentação de código
- `validate-psr12.php` - Validação de padrões PSR-12

### 📖 Documentação (2 scripts)  
- `validate-docs.sh` - Validação de documentação markdown
- `validate_openapi.sh` - Validação de recursos OpenAPI

### 🧪 Testes (2 scripts)
- `run_stress_tests.sh` - Testes de stress e performance
- `test-all-php-versions.sh` - Testes cross-version PHP

### 📦 Release e Versão (3 scripts)
- `version-bump.sh` - ⭐ **Principal**: Gerenciamento de versões
- `prepare_release.sh` - Preparação para release
- `release.sh` - Execução final do release

### 🔧 Utilitários (3 scripts)
- `validate_benchmarks.sh` - Validação de benchmarks
- `switch-psr7-version.php` - Utilitário PSR-7
- `lib/version-utils.sh` - ⭐ **Biblioteca**: Funções compartilhadas

### ⚙️ Git Hooks (2 scripts)
- `pre-commit` - Hook de pre-commit
- `pre-push` - Hook de pre-push

## ✨ Principais Melhorias (v1.1.4)

### 🔄 Consolidação Realizada
- **Removidos 10 scripts duplicados/obsoletos**
- **Consolidado** `quality-check.sh` como script principal
- **Eliminado** hardcoding de versões e caminhos
- **Criada** biblioteca compartilhada `lib/version-utils.sh`

### 🎯 Detecção Automática
- **Versão**: Lida automaticamente do arquivo `VERSION`
- **Projeto Root**: Detecta automaticamente o diretório do projeto
- **Validação**: Verifica contexto correto do PivotPHP Core

### 🚨 Validação Rigorosa
- **Arquivo VERSION obrigatório**: Scripts falham se não encontrar
- **Formato semântico**: Valida formato X.Y.Z
- **Mensagens claras**: Erros críticos em português

## 📋 Workflow Recomendado

### 🔄 Desenvolvimento Diário
```bash
# Antes de commit
scripts/quality-check.sh

# Validação completa (opcional)
scripts/validate_all.sh
```

### 📦 Preparação de Release
```bash
# 1. Bump da versão
scripts/version-bump.sh [patch|minor|major]

# 2. Preparação final
scripts/prepare_release.sh

# 3. Release (se tudo estiver ok)
scripts/release.sh
```

### 🧪 Validação Estendida
```bash
# Multi-version PHP testing
scripts/test-all-php-versions.sh

# Testes de stress
scripts/run_stress_tests.sh

# Validação de documentação
scripts/validate-documentation.php
```

## 🆘 Resolução de Problemas

### ❌ Erro: "VERSION file not found"
```bash
# Criar arquivo VERSION na raiz do projeto
echo "1.1.4" > VERSION
```

### ❌ Erro: "Invalid version format"
```bash
# Verificar formato do arquivo VERSION (deve ser X.Y.Z)
cat VERSION
# Corrigir se necessário
echo "1.1.4" > VERSION
```

### ❌ Erro: "Project root not found"
```bash
# Executar scripts a partir da raiz do projeto
cd /path/to/pivotphp-core
scripts/quality-check.sh
```

## 📚 Documentação Adicional

- **Guia de Versionamento**: `docs/VERSIONING_GUIDE.md`
- **Plano de Limpeza**: `scripts/CLEANUP_PLAN.md`
- **Status de Scripts**: `scripts/SCRIPTS_UPDATE_STATUS.md`
- **Consolidação**: `CONSOLIDATION_SUMMARY.md`

## 🔗 Scripts Removidos (Histórico)

Os seguintes scripts foram **removidos** por serem duplicados ou obsoletos:
- `quality-check-v114.sh` → Substituído por `quality-check.sh`
- `validate_all_v114.sh` → Substituído por `validate_all.sh`
- `quick-quality-check.sh` → Duplicação removida
- `simple_pre_release.sh` → Substituído por `prepare_release.sh`
- `quality-gate.sh` → Funcionalidade incorporada
- `quality-metrics.sh` → Funcionalidade incorporada
- `test-php-versions-quick.sh` → Substituído por `test-all-php-versions.sh`
- `ci-validation.sh` → Funcionalidade incorporada
- `setup-precommit.sh` → Script de configuração única
- `adapt-psr7-v1.php` → Script específico não essencial

---

**📊 Estatísticas Finais**:
- **Scripts ativos**: 15 (redução de 40% de 25 → 15)
- **Duplicações eliminadas**: 10 scripts
- **Hardcoding removido**: 100% dos scripts
- **Versão automática**: Todos os scripts

**🎯 Resultado**: Scripts mais limpos, organizados e maintíveis para o PivotPHP Core v1.1.4+