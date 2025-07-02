# Changelog

Todas as mudanças notáveis no Express-PHP Framework serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.1.1] - 2025-06-30

> 📖 **Veja o novo overview completo da versão:** [FRAMEWORK_OVERVIEW_v2.1.1.md](FRAMEWORK_OVERVIEW_v2.1.1.md)

### 🚀 Performance & Modernization Release
- **Advanced Optimizations**: ML-powered cache (5 models), Zero-copy operations (1.7GB saved), Memory mapping
- **Performance**: 278x improvement - 52M ops/sec CORS, 24M ops/sec Response, 11M ops/sec JSON
- **Benchmarks**: Scientific methodology with real production data
- **Documentation**: Consolidated structure with FRAMEWORK_OVERVIEW_v2.0.1.md
- **Memory Efficiency**: Peak usage reduced to 89MB with intelligent GC
- **Modern PHP 8.1+ Features**: Typed properties, constructor promotion, strict types
- **Security**: CSRF, XSS, JWT, CORS, Rate Limiting, Security Headers
- **Extension System**: Plugins, hooks, auto-discovery, PSR-14 events
- **Quality**: PHPStan Level 9, PSR-12, 270+ testes automatizados

---

## [2.1.2] - 2025-07-02

### Mudanças de compatibilidade e organização de testes
- Todos os middlewares legados (não-PSR-15) foram oficialmente depreciados e removidos dos exemplos e recomendações.
- Documentação reforçada sobre a obrigatoriedade do padrão PSR-15 para middlewares.

---

Todas as versões anteriores foram consolidadas e não são mais suportadas. Use sempre a versão mais recente para garantir performance, segurança e compatibilidade.
