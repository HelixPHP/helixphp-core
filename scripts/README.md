# Scripts de Qualidade de Código - Express PHP

Este diretório contém scripts para garantir a qualidade do código no Express PHP.

## Pre-commit Hooks

### Configuração Automática

Para configurar os hooks de pre-commit automaticamente:

```bash
composer run precommit:install
```

Ou execute diretamente:

```bash
./scripts/setup-precommit.sh
```

### Configuração Manual

#### Usando framework pre-commit (Recomendado)

1. Instale o framework pre-commit:
   ```bash
   pip install pre-commit
   ```

2. Instale os hooks:
   ```bash
   pre-commit install
   ```

3. Execute em todos os arquivos:
   ```bash
   pre-commit run --all-files
   ```

#### Usando Git Hooks Manual

1. Copie o script para o diretório de hooks do Git:
   ```bash
   cp scripts/pre-commit .git/hooks/pre-commit
   chmod +x .git/hooks/pre-commit
   ```

## Validações Incluídas

### 1. PHPStan - Análise Estática
- **Nível**: 5 (padrão) ou 8 (strict)
- **Arquivos**: `src/`
- **Comando**: `composer phpstan`
- **Comando strict**: `composer phpstan:strict`

### 2. PHPUnit - Testes Unitários
- **Cobertura**: Todos os testes
- **Comando**: `composer test`
- **Específico**: `composer test:security` ou `composer test:auth`

### 3. PSR-12 - Padrão de Código
- **Verificação**: `composer cs:check`
- **Correção automática**: `composer cs:fix`
- **Arquivos**: `src/`

### 4. Verificações Adicionais
- Sintaxe PHP válida
- Espaços em branco finais
- Fim de arquivo
- Arquivos grandes
- Conflitos de merge

## Comandos Úteis

### Executar todas as verificações de qualidade
```bash
composer quality:check
```

### Corrigir e verificar qualidade
```bash
composer quality:fix
```

### Testar hooks manualmente
```bash
composer precommit:test
# ou
./scripts/pre-commit
```

### Pular validações temporariamente
```bash
git commit --no-verify
```

## Estrutura dos Arquivos

```
scripts/
├── pre-commit              # Script principal de validação
├── setup-precommit.sh     # Instalador automático
└── README.md              # Esta documentação

.pre-commit-config.yaml     # Configuração do framework pre-commit
```

## Fluxo de Trabalho

1. **Antes do commit**: As validações são executadas automaticamente
2. **Falha na validação**: O commit é rejeitado com detalhes dos erros
3. **Correção automática**: PSR-12 tenta corrigir automaticamente
4. **Sucesso**: Commit é permitido

## Configuração do PHPStan

### Padrão (`phpstan.neon`)
- Nível 5 de análise
- Ignora alguns erros comuns
- Focado em produtividade

### Strict (`phpstan-strict.neon`)
- Nível 8 de análise
- Sem ignorar erros
- Focado em qualidade máxima

## Personalização

### Adicionando novas validações

Edite o arquivo `scripts/pre-commit` para adicionar novas verificações:

```bash
# Nova validação personalizada
print_status "Executando validação customizada..."
if ! my_custom_validation; then
    print_error "Validação customizada falhou!"
    FAILURES+=("custom")
else
    print_success "Validação customizada passou!"
fi
```

### Modificando padrões PSR-12

Edite os comandos no `composer.json`:

```json
{
    "scripts": {
        "cs:check": "phpcs --standard=PSR12 --extensions=php src/",
        "cs:fix": "phpcbf --standard=PSR12 --extensions=php src/"
    }
}
```

## Troubleshooting

### Erro: "Dependências não encontradas"
```bash
composer install
```

### Erro: "PHPStan falhou"
- Verifique os erros mostrados
- Execute `composer phpstan` para ver detalhes
- Corrija os problemas de código

### Erro: "Testes falharam"
- Execute `composer test` para ver detalhes
- Corrija os testes que falharam

### Erro: "PSR-12 não conforme"
- Execute `composer cs:fix` para correção automática
- Adicione as mudanças ao commit: `git add .`

### Hook não está executando
- Verifique se o arquivo tem permissão de execução: `chmod +x .git/hooks/pre-commit`
- Verifique se o framework pre-commit está instalado: `pre-commit --version`

## Benefícios

- ✅ **Qualidade consistente**: Garante padrões em todo o projeto
- ✅ **Detecção precoce**: Encontra problemas antes do commit
- ✅ **Automação**: Reduz revisões manuais
- ✅ **Educação**: Ensina boas práticas aos desenvolvedores
- ✅ **CI/CD friendly**: Preparado para integração contínua
