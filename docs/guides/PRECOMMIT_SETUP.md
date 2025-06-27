# Validações de Qualidade de Código - Express PHP

## ✅ Configuração Concluída!

Foram configuradas as seguintes validações para pre-commit:

### 🔍 PHPStan - Análise Estática
- **Arquivo de config**: `phpstan.neon` (nível 5) e `phpstan-strict.neon` (nível 8)
- **Comando**: `composer phpstan` ou `composer phpstan:strict`
- **Verifica**: Tipos, métodos inexistentes, erros lógicos

### 🧪 PHPUnit - Testes Unitários
- **Arquivo de config**: `phpunit.xml`
- **Comando**: `composer test`
- **Verifica**: Todos os testes passam

### 📏 PSR-12 - Padrão de Código
- **Comando verificação**: `composer cs:check`
- **Comando correção**: `composer cs:fix`
- **Verifica**: Formatação, espaçamento, convenções

## 🚀 Como Usar

### Instalação Automática
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

### Corrigir e Verificar
```bash
composer run quality:fix
```

## 📁 Arquivos Criados

```
.pre-commit-config.yaml     # Configuração framework pre-commit
scripts/
├── pre-commit              # Script principal de validação
├── setup-precommit.sh     # Instalador automático
└── README.md              # Documentação detalhada
```

## ⚡ Execução Automática

Após a instalação, as validações são executadas automaticamente antes de cada commit:

1. **PHPStan** analisa o código estaticamente
2. **PHPUnit** executa todos os testes
3. **PSR-12** verifica e corrige formatação
4. **Sintaxe PHP** é validada

Se alguma validação falhar, o commit é rejeitado com detalhes dos erros.

## 🛠️ Próximos Passos

1. Execute a instalação:
   ```bash
   ./scripts/setup-precommit.sh
   ```

2. Teste as validações:
   ```bash
   ./scripts/pre-commit
   ```

3. Faça um commit para testar:
   ```bash
   git add .
   git commit -m "Configuração de pre-commit hooks"
   ```

## 🔧 Framework Pre-commit (Opcional)

Para melhor experiência, instale o framework pre-commit:
```bash
pip install pre-commit
pre-commit install
```

## 📝 Observações

- As validações podem ser puladas temporariamente com `git commit --no-verify`
- PSR-12 tenta correção automática quando possível
- Logs detalhados são exibidos em caso de falha
- Todos os scripts têm cores para melhor legibilidade
