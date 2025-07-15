# Validation Scripts

Este diretório contém scripts de validação para o framework PivotPHP Core.

## Scripts Disponíveis

### validate_all.sh
Script principal que executa todas as validações do projeto em sequência.
```bash
./scripts/validation/validate_all.sh
```

### validate-docs.sh
Valida a estrutura e completude da documentação do projeto.
```bash
./scripts/validation/validate-docs.sh
```

### validate_project.php
Validação programática do projeto em PHP com verificações detalhadas.
```bash
php ./scripts/validation/validate_project.php
```

### pre-commit
Script de validação executado antes de commits para garantir qualidade do código.
```bash
./scripts/validation/pre-commit
```

### pre-push
Script de validação executado antes de push, incluindo testes de integração.
```bash
./scripts/validation/pre-push
```

## Uso Recomendado

1. **Antes de commit**: Execute `pre-commit` ou `validate_all.sh`
2. **Antes de push**: Execute `pre-push` para validação completa
3. **Validação de documentação**: Execute `validate-docs.sh` após mudanças na documentação
4. **Validação completa**: Execute `validate_all.sh` para verificação abrangente

## Dependências

- PHP 8.1+
- Composer
- PHPUnit
- PHPStan
- PHP_CodeSniffer