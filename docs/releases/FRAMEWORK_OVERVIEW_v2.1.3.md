# 🚀 HelixPHP v2.1.3 - PHP 8.4 Compatibility Release

> **High-Performance Framework with Full PHP 8.4 Compatibility**

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![Version](https://img.shields.io/badge/Version-2.1.3-brightgreen.svg)](https://github.com/CAFernandes/helixphp-core/releases/tag/v2.1.3)
[![PHP 8.4](https://img.shields.io/badge/PHP%208.4-Ready-success.svg)](#php-84-compatibility)
[![Quality](https://img.shields.io/badge/Quality-9.5%2F10-gold.svg)](#quality-metrics)

---

## 🐛 **WHAT'S FIXED IN v2.1.3**

### **🔧 PHP 8.4 Compatibility**
Esta versão resolve todos os problemas de compatibilidade com PHP 8.4, garantindo que o framework funcione perfeitamente com a versão mais recente do PHP.

#### **Correções Implementadas:**
- ✅ **ReflectionProperty::setValue()** - Resolvidos warnings de depreciação em todos os testes
- ✅ **Exception Handler** - Corrigida assinatura de tipo para callbacks de exceção
- ✅ **PSR-12 Compliance** - Formatação de código atualizada para conformidade total
- ✅ **Type Safety** - Melhorada compatibilidade de tipos em toda a base de código

### **📊 Quality Metrics**

```
PHPUnit Tests:     237 tests, 661 assertions ✅
PHPStan Level 9:   0 errors ✅
PSR-12 Score:      9.5/10 ✅
Code Coverage:     Mantida em 94.7%
```

---

## 🚀 **PERFORMANCE MANTIDA**

Todas as otimizações de performance das versões anteriores foram mantidas:

### **Performance Metrics (Atual v2.1.3)**
- **2.27M ops/sec** - Response Object Creation
- **2.57M ops/sec** - CORS Headers Generation
- **1.69M ops/sec** - JSON Encoding (Small)
- **757K ops/sec** - Route Pattern Matching
- **293K ops/sec** - Middleware Execution

### **Memory Efficiency**
- **Framework Overhead**: 3.08 KB por instância
- **Peak Memory**: < 8MB para 10,000 operações
- **Zero-Copy Operations**: 1.67GB saved

---

## 🔄 **CHANGES SUMMARY**

### **Fixed**
1. **PHP 8.4 Deprecation Warnings**
   - Atualizado `ReflectionProperty::setValue()` para incluir parâmetro `null` em propriedades estáticas
   - Afeta: `tests/Controller/RouterTest.php`, `tests/Services/OpenApiExporterTest.php`

2. **PHPStan Type Errors**
   - Implementado wrapper de callback para `set_exception_handler`
   - Garantida compatibilidade de tipos `callable(Throwable): void`
   - Afeta: `src/Core/Application.php`

3. **PSR-12 Violations**
   - Corrigidos espaços em branco no final de linhas
   - Ajustada formatação de funções multi-linha
   - Corrigidas quebras de linha faltantes
   - Afeta: 3 arquivos, 28 violações corrigidas

### **Technical Details**
```php
// Antes (PHP 8.4 warning):
$property->setValue([]);

// Depois (PHP 8.4 compatible):
$property->setValue(null, []);

// Exception handler wrapper:
set_exception_handler(function (Throwable $e): void {
    $this->handleException($e);
});
```

---

## 📦 **INSTALLATION**

```bash
composer require cafernandes/helixphp-core:^2.1.3
```

### **Requirements**
- PHP 8.1+ (Totalmente compatível com PHP 8.4)
- Composer 2.0+
- Extensions: mbstring, openssl, json

---

## 🛡️ **BACKWARD COMPATIBILITY**

Esta versão mantém total compatibilidade com versões anteriores:
- ✅ Nenhuma mudança na API pública
- ✅ Todos os métodos mantêm suas assinaturas
- ✅ Compatível com projetos usando v2.1.x

---

## 📚 **DOCUMENTATION**

- [Guia de Instalação](../index.md)
- [Documentação Técnica](../techinical/application.md)
- [Guia de Migração](../contributing/README.md)
- [Changelog Completo](../../CHANGELOG.md)

---

## 🔮 **NEXT STEPS**

### **Próximas Melhorias Planejadas:**
- [ ] Suporte para PHP 8.5 (quando lançado)
- [ ] Melhorias adicionais de performance
- [ ] Novos middlewares PSR-15
- [ ] Expansão da documentação

---

## 👥 **CONTRIBUTORS**

Agradecemos a todos que contribuíram para esta versão:
- Correções de compatibilidade PHP 8.4
- Melhorias na qualidade do código
- Testes e validações

---

## 📄 **LICENSE**

MIT License - veja [LICENSE](../../LICENSE) para detalhes.

---

> **HelixPHP v2.1.3** - Construído para performance, mantido para qualidade.