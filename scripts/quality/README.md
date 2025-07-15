# Quality Scripts

Este diretório contém scripts para verificação e validação da qualidade do código.

## Scripts Disponíveis

### quality-check.sh
Script principal de verificação de qualidade que executa todos os testes de qualidade.
```bash
./scripts/quality/quality-check.sh
```

### validate-psr12.php
Validação específica do padrão PSR-12 com relatório detalhado.
```bash
php ./scripts/quality/validate-psr12.php
```

## Funcionalidades

### quality-check.sh
- Execução de testes unitários com cobertura
- Análise estática com PHPStan Level 9
- Verificação de padrão PSR-12
- Testes de segurança
- Benchmarks de performance
- Auditoria de dependências
- Geração de relatórios detalhados

### validate-psr12.php
- Validação completa do padrão PSR-12
- Relatório com score de qualidade
- Identificação de violações específicas
- Suporte para exclusão de diretórios

## Relatórios Gerados

Os scripts geram relatórios em `reports/quality/`:
- `phpstan-results.txt` - Resultados da análise estática
- `test-results.txt` - Resultados dos testes
- `coverage-results.txt` - Relatório de cobertura
- `codestyle-results.txt` - Verificação PSR-12
- `security-results.txt` - Testes de segurança
- `benchmark-results.txt` - Resultados de performance
- `audit-results.txt` - Auditoria de dependências
- `quality-report-{timestamp}.txt` - Relatório consolidado

## Requisitos de Qualidade

- **PHPStan**: Level 9 (zero erros)
- **PSR-12**: 100% de conformidade
- **Cobertura**: ≥30% (alvo: 35%+)
- **Performance**: ≥30K ops/sec
- **Segurança**: Zero vulnerabilidades críticas