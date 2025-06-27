# 🛡️ Validação Pre-commit Configurada - Express PHP

## ✅ Implementação Completa

Foi configurado um sistema robusto de validação de qualidade de código para pre-commit hooks no Express PHP, incluindo:

### 🔍 Validações Implementadas

1. **PHPStan - Análise Estática**
   - ✅ Nível 5 (padrão) e Nível 8 (strict)
   - ✅ Detecta erros de tipo e lógica
   - ✅ Configuração em `phpstan.neon` e `phpstan-strict.neon`

2. **PHPUnit - Testes Unitários**
   - ✅ Executa toda a suíte de testes
   - ✅ Verifica se todos os testes passam
   - ✅ Suporte a testes específicos (security, auth)

3. **PSR-12 - Padrão de Código**
   - ✅ Verificação automática de conformidade
   - ✅ Correção automática quando possível
   - ✅ Script adicional para linhas longas

4. **Verificações Adicionais**
   - ✅ Sintaxe PHP válida
   - ✅ Espaços em branco finais
   - ✅ Arquivos grandes
   - ✅ Conflitos de merge

### 📁 Arquivos Criados

```
.pre-commit-config.yaml         # Configuração framework pre-commit
scripts/
├── pre-commit                  # Script principal de validação
├── setup-precommit.sh         # Instalador automático
├── fix-psr12-lines.sh         # Correção de linhas longas
└── README.md                  # Documentação completa
PRECOMMIT_SETUP.md             # Guia de instalação
```

### 🚀 Comandos Adicionados ao Composer

```json
{
    "scripts": {
        "phpstan:strict": "phpstan analyse -c phpstan-strict.neon",
        "quality:check": ["@phpstan", "@test", "@cs:check"],
        "quality:fix": ["@cs:fix", "@phpstan", "@test"],
        "fix:psr12-lines": "./scripts/fix-psr12-lines.sh",
        "precommit:install": "./scripts/setup-precommit.sh",
        "precommit:test": "./scripts/pre-commit"
    }
}
```

## 🛠️ Como Usar

### Instalação Rápida
```bash
composer run precommit:install
```

### Teste Manual
```bash
composer run precommit:test
```

### Verificar Qualidade
```bash
composer run quality:check
```

### Corrigir Problemas
```bash
composer run quality:fix
composer run fix:psr12-lines  # Para linhas longas específicas
```

## ⚡ Funcionalidades

### 🤖 Execução Automática
- As validações executam automaticamente antes de cada commit
- Commit é rejeitado se alguma validação falhar
- Mensagens coloridas e detalhadas para fácil identificação

### 🔧 Correção Automática
- PSR-12 tenta corrigir formatação automaticamente
- Script específico para problemas de linhas longas
- Backups automáticos dos arquivos modificados

### 📊 Relatórios Detalhados
- Output colorido para melhor legibilidade
- Relatórios específicos para cada tipo de erro
- Sugestões de correção quando possível

### 🎯 Configuração Flexível
- Suporte a framework pre-commit e git hooks manuais
- Configurações separadas para análise normal e strict
- Possibilidade de pular validações temporariamente

## 🔄 Fluxo de Trabalho

1. **Desenvolver**: Escreva código normalmente
2. **Commit**: Execute `git commit`
3. **Validação**: Hooks executam automaticamente:
   - PHPStan analisa o código
   - PHPUnit executa testes
   - PSR-12 verifica formatação
   - Sintaxe PHP é validada
4. **Sucesso**: Commit é aceito
5. **Falha**: Commit rejeitado com detalhes dos erros

## 📝 Configurações Específicas

### PHPStan
- **Padrão**: Nível 5, ignora alguns erros comuns
- **Strict**: Nível 8, máxima qualidade
- **Exclui**: vendor, test, examples

### PSR-12
- **Alvo**: `src/` directory
- **Padrão**: PSR-12 completo
- **Extensões**: `.php`
- **Correção**: Automática quando possível

### Testes
- **Cobertura**: Todos os testes
- **Formato**: Sem cores no pre-commit
- **Falha**: Para se qualquer teste falhar

## 🎛️ Personalização

Para adicionar novas validações, edite `scripts/pre-commit`:

```bash
# Nova validação
print_status "Executando nova validação..."
if ! minha_validacao; then
    print_error "Nova validação falhou!"
    FAILURES+=("nova")
else
    print_success "Nova validação passou!"
fi
```

## 📋 Troubleshooting

### Problema: Hook não executa
**Solução**: `chmod +x .git/hooks/pre-commit`

### Problema: Dependências não encontradas
**Solução**: `composer install`

### Problema: Violações PSR-12
**Solução**: `composer run fix:psr12-lines && composer cs:fix`

### Problema: Testes falhando
**Solução**: `composer test` para ver detalhes

### Problema: PHPStan falhas
**Solução**: Verificar e corrigir erros reportados

## ✨ Benefícios Obtidos

- ✅ **Qualidade Consistente**: Todos os commits seguem os mesmos padrões
- ✅ **Detecção Precoce**: Problemas encontrados antes do commit
- ✅ **Automação Total**: Sem necessidade de lembrar de executar validações
- ✅ **Feedback Imediato**: Erros reportados com detalhes específicos
- ✅ **Correção Automática**: PSR-12 e scripts específicos corrigem quando possível
- ✅ **Flexibilidade**: Pode ser desabilitado temporariamente se necessário
- ✅ **Documentação Completa**: Guias e exemplos para toda a equipe

A implementação está completa e pronta para uso! 🎉
