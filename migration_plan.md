# Plano de Migração: ApiExpress → Application

## 🎯 Objetivo
Substituir `ApiExpress.php` pela classe `Application.php` como ponto de entrada principal do framework, mantendo compatibilidade e melhorando a arquitetura.

## 📊 Análise Atual

### ApiExpress.php (Facade)
- ✅ Compatibilidade com código existente
- ✅ Lazy loading e otimizações
- ❌ Duplicação de lógica
- ❌ Complexidade adicional

### Application.php (Core)
- ✅ Arquitetura moderna (DI, Container)
- ✅ Padrões PSR
- ✅ Melhor testabilidade
- ❌ Quebra compatibilidade

## 🚀 Estratégia de Migração

### Fase 1: Preparação (Sem Breaking Changes)
1. **Criar alias no composer.json**
2. **Adicionar método factory em Application**
3. **Atualizar documentação**

### Fase 2: Transição (v2.1.0)
1. **Deprecar ApiExpress**
2. **Migrar exemplos**
3. **Guia de migração**

### Fase 3: Conclusão (v3.0.0)
1. **Remover ApiExpress**
2. **Application como principal**

## 📝 Implementação

### 1. Alias no Autoloader
```json
// composer.json
"autoload": {
    "psr-4": {
        "Express\\": "src/"
    },
    "files": ["src/aliases.php"]
}
```

### 2. Arquivo de Aliases
```php
// src/aliases.php
if (!class_exists('Express\\ApiExpress')) {
    class_alias('Express\\Core\\Application', 'Express\\ApiExpress');
}
```

### 3. Factory Method em Application
```php
// Em Application.php
public static function create(?string $basePath = null): self
{
    return new self($basePath);
}

public static function express(?string $basePath = null): self
{
    return new self($basePath);
}
```

## 🔄 Compatibilidade

### Before (ApiExpress)
```php
use Express\ApiExpress;
$app = new ApiExpress();
```

### After (Application)
```php
use Express\Core\Application;
$app = new Application();
// ou
$app = Application::create();
// ou
$app = Application::express();
```

## 📋 Checklist de Migração

- [ ] Criar aliases de compatibilidade
- [ ] Adicionar factory methods
- [ ] Migrar exemplos principais
- [ ] Atualizar README.md
- [ ] Atualizar testes
- [ ] Documentar breaking changes
- [ ] Criar guia de migração
- [ ] Atualizar benchmarks

## ⚠️ Considerações

1. **Breaking Changes**: Mudança de namespace
2. **Compatibilidade**: Manter aliases por 1-2 versões
3. **Performance**: Application pode ser ligeiramente mais lenta no boot
4. **Documentação**: Atualizar toda documentação

## 🎯 Resultado Esperado

- ✅ Arquitetura mais limpa
- ✅ Melhor manutenibilidade
- ✅ Padrões modernos
- ✅ Compatibilidade mantida (temporariamente)
