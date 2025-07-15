# Scripts Directory

Este diretório contém todos os scripts auxiliares organizados por funcionalidade para o PivotPHP Core.

## Estrutura Organizada

```
scripts/
├── validation/     # Scripts de validação geral
├── quality/        # Scripts de verificação de qualidade
├── release/        # Scripts de gerenciamento de releases
├── testing/        # Scripts de execução de testes
└── utils/          # Scripts utilitários
```

## Diretórios

### 📋 [validation/](./validation/)
Scripts para validação geral do projeto:
- `validate_all.sh` - Validação completa do projeto
- `validate-docs.sh` - Validação da documentação
- `validate_project.php` - Validação programática
- `pre-commit` - Validações pré-commit
- `pre-push` - Validações pré-push

### 🔍 [quality/](./quality/)
Scripts para verificação de qualidade do código:
- `quality-check.sh` - Verificação completa de qualidade
- `validate-psr12.php` - Validação PSR-12 específica

### 🚀 [release/](./release/)
Scripts para gerenciamento de versões e releases:
- `prepare_release.sh` - Preparação de releases
- `release.sh` - Criação de releases
- `version-bump.sh` - Incremento de versões

### 🧪 [testing/](./testing/)
Scripts para execução de testes especializados:
- `run_stress_tests.sh` - Testes de stress
- `test-all-php-versions.sh` - Testes multi-versão PHP

### 🛠️ [utils/](./utils/)
Scripts utilitários para manutenção:
- `switch-psr7-version.php` - Alternância de versões PSR-7
- `version-utils.sh` - Utilitários de versão

## Fluxo de Desenvolvimento

### Desenvolvimento Diário
```bash
# Antes de commit
./scripts/validation/pre-commit

# Verificação de qualidade
./scripts/quality/quality-check.sh
```

### Antes de Release
```bash
# Validação completa
./scripts/validation/validate_all.sh

# Testes multi-versão PHP
./scripts/testing/test-all-php-versions.sh

# Preparar release
./scripts/release/prepare_release.sh 1.2.0
```

### CI/CD Integration
```bash
# Validação rápida para CI
./scripts/quality/quality-check.sh

# Testes de stress para validação completa
./scripts/testing/run_stress_tests.sh
```

## Convenções

- Todos os scripts são executáveis e bem documentados
- Scripts de validação retornam códigos de saída apropriados (0 = sucesso)
- Relatórios são gerados em `reports/` quando aplicável
- Scripts utilizam cores e formatação consistente para output
- Dependências são documentadas em cada README específico

## Migração de Scripts Legados

Scripts movidos da raiz para subdiretórios mantêm funcionalidade:
- Nomes dos scripts preservados
- Funcionalidades mantidas
- Compatibilidade com scripts existentes
- Documentação atualizada