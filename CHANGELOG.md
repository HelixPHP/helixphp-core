# Changelog

Todas as mudanças notáveis no Express-PHP Framework serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.1.3] - 2025-07-06

### 🐛 **PHP 8.4 Compatibility & Validation Fixes**

> 📖 **Veja o overview completo da versão:** [docs/releases/FRAMEWORK_OVERVIEW_v2.1.3.md](docs/releases/FRAMEWORK_OVERVIEW_v2.1.3.md)

#### Fixed
- **PHP 8.4 Compatibility**: Resolvidos warnings de depreciação do `ReflectionProperty::setValue()` nos testes
- **PHPStan Level 9**: Corrigido erro de tipo no callback do `set_exception_handler` em `Application.php`
- **PSR-12 Compliance**: Corrigidas todas as violações de estilo de código em múltiplos arquivos
- **Type Safety**: Melhorada compatibilidade de tipos para callbacks de tratamento de exceções

#### Changed
- Atualizado método `setValue()` para incluir parâmetro `null` em propriedades estáticas
- Wrapper de callback implementado para garantir assinatura correta do exception handler
- Formatação de código ajustada para conformidade total com PSR-12

#### Quality
- ✅ PHPUnit: 237 testes, 661 asserções passando
- ✅ PHPStan: Nível 9 sem erros
- ✅ PSR-12: Score 9.5/10 (apenas avisos não-críticos)

---

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

### 📚 **Major Documentation & Scripts Restructure**

> 📖 **Veja o overview completo da versão:** [docs/releases/FRAMEWORK_OVERVIEW_v2.1.2.md](docs/releases/FRAMEWORK_OVERVIEW_v2.1.2.md)

#### 🏗️ **Reestruturação Completa da Documentação**
- **Nova estrutura organizada**: `docs/releases/`, `docs/techinical/`, `docs/implementions/`, `docs/performance/`, `docs/testing/`, `docs/contributing/`
- **Migração de releases**: Todos os `FRAMEWORK_OVERVIEW_*.md` movidos para `docs/releases/`
- **Índice centralizado**: Novo `docs/index.md` como porta de entrada para toda documentação
- **Documentação OpenAPI nativa**: Criada `docs/techinical/http/openapi_documentation.md` com guias completos
- **Guias expandidos**: Documentação técnica, de implementação, testes e contribuição totalmente atualizados

#### 🛠️ **Modernização e Integração de Scripts**
- **Script principal centralizado**: Novo `validate_all.sh` que orquestra todas as validações
- **Git Hooks integrados**:
  - `pre-commit`: Validações rápidas (PSR-12, sintaxe, estrutura)
  - `pre-push`: Validação completa (documentação, benchmarks, testes)
- **Scripts integrados**: `prepare_release.sh` e `release.sh` agora usam `validate_all.sh`
- **Instalador automático**: `setup-precommit.sh` configura ambos os hooks Git
- **Migração para legacy**: Scripts obsoletos movidos para `scripts/legacy/`

#### 📋 **Scripts Migrados para Legacy**
- `cleanup_docs.sh` - Script de limpeza para estrutura antiga
- `fix-psr12-lines.sh` - Correções PSR-12 específicas hardcoded
- `publish_v2.0.1.sh` - Script de publicação v2.0.1
- `validate-docs-legacy.sh` - Validação de docs estrutura antiga
- `validate-docs-v2.sh` - Validação de docs v2.0

#### 🚀 **Novos Scripts e Funcionalidades**
- **`validate-docs.sh`**: Novo validador para estrutura v2.1.2
- **`validate_all.sh`**: Script principal com modo `--pre-commit` para validações rápidas
- **Validação modular**: Cada script especializado em sua área (docs, benchmarks, projeto, PSR-12)
- **Relatórios detalhados**: Output colorido e estatísticas de sucesso para todos os scripts

#### 🔧 **Melhorias de Qualidade e Compatibilidade**
- **Compatibilidade PSR-15**: Depreciação oficial de middlewares legados não-PSR-15
- **Documentação reforçada**: Obrigatoriedade do padrão PSR-15 para middlewares
- **Validações rigorosas**: Verificação de estrutura, sintaxe, documentação e benchmarks
- **Benchmarks atualizados**: Documentação com resultados PHP 8.4.8 + JIT (2.69M ops/sec)

#### 📊 **Performance e Benchmarks**
- **Resultados atualizados**: PHP 8.4.8 com JIT habilitado
- **Documentação expandida**: `docs/performance/benchmarks/README.md` com 14KB de conteúdo
- **Validação automatizada**: Script `validate_benchmarks.sh` garante integridade dos relatórios

#### 🎯 **Experiência do Desenvolvedor**
- **Navegação simplificada**: Estrutura de documentação intuitiva e bem organizada
- **Hooks automáticos**: Validações executadas automaticamente em commits e pushes
- **Feedback claro**: Scripts com output colorido e instruções específicas
- **Documentação técnica expandida**: Guias detalhados para todas as funcionalidades

#### 📈 **Estatísticas da Release v2.1.2**
- **Arquivos de documentação**: 32 verificações, 100% de sucesso
- **Scripts reorganizados**: 6 scripts integrados, 5 movidos para legacy
- **Estrutura de diretórios**: 7 novas seções de documentação organizadas
- **Validações automatizadas**: 3 modos (pre-commit, pre-push, completo)
- **Taxa de sucesso**: 100% em todas as validações de qualidade

#### 🔄 **Fluxo de Trabalho Modernizado**
- **Pre-commit**: Validações essenciais em ~6 segundos
- **Pre-push**: Validação completa em ~12 segundos
- **Documentação**: Navegação centralizada via `docs/index.md`
- **Scripts**: Comando único `validate_all.sh` para todas as verificações

#### 🎯 **Próximos Passos Recomendados**
1. Execute `./scripts/setup-precommit.sh` para configurar hooks automáticos
2. Navegue pela nova documentação em `docs/index.md`
3. Use `scripts/validate_all.sh` para validação completa do projeto
4. Consulte `docs/releases/FRAMEWORK_OVERVIEW_v2.1.2.md` para overview detalhado

#### 🔧 **Mudanças Técnicas Detalhadas**

**Estrutura de Scripts:**
```
scripts/
├── validate_all.sh         # 🚀 Script principal (NOVO)
├── pre-commit              # 🔄 Hook integrado (ATUALIZADO)
├── pre-push                # ✨ Hook novo (CRIADO)
├── setup-precommit.sh      # 🔄 Instalador (ATUALIZADO)
├── validate-docs.sh        # 🆕 Validador docs v2.1.2 (RECRIADO)
├── validate_project.php    # ✅ Validador PHP (EXISTENTE)
├── validate_benchmarks.sh  # ✅ Validador benchmarks (EXISTENTE)
├── validate-psr12.php      # ✅ Validador PSR-12 (EXISTENTE)
├── prepare_release.sh      # 🔄 Preparador release (ATUALIZADO)
├── release.sh              # 🔄 Release script (ATUALIZADO)
└── legacy/                 # 📦 Scripts antigos migrados
```

**Estrutura de Documentação:**
```
docs/
├── index.md                # 🆕 Índice principal
├── releases/               # 🆕 Versões e overviews
├── techinical/             # 🆕 Documentação técnica
├── implementions/          # 🆕 Guias de implementação
├── performance/            # 🆕 Performance e benchmarks
├── testing/                # 🆕 Guias de teste
└── contributing/           # 🆕 Contribuição
```

**Compatibilidade:**
- ✅ **Mantida**: Todas as APIs públicas permanecem inalteradas
- ✅ **PSR-15**: Middlewares não-PSR-15 oficialmente depreciados
- ✅ **PHP 8.1+**: Suporte completo com features modernas
- ✅ **Backward Compatible**: Projetos existentes funcionam sem modificação

---

Todas as versões anteriores foram consolidadas e não são mais suportadas. Use sempre a versão mais recente para garantir performance, segurança e compatibilidade.
