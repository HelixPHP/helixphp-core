# Release Scripts

Este diretório contém scripts para gerenciamento de versões e releases do PivotPHP Core.

## Scripts Disponíveis

### prepare_release.sh
Prepara uma nova release com validações e atualizações automáticas.
```bash
./scripts/release/prepare_release.sh 1.2.0
```

### release.sh
Cria a release após preparação, incluindo tags e commits.
```bash
./scripts/release/release.sh
```

### version-bump.sh
Utilitário para incrementar versões automaticamente.
```bash
./scripts/release/version-bump.sh major|minor|patch
```

## Fluxo de Release

1. **Preparação**: Execute `prepare_release.sh` com a nova versão
   - Valida estado atual do repositório
   - Atualiza arquivo VERSION
   - Executa todas as validações de qualidade
   - Atualiza documentação relevante

2. **Criação**: Execute `release.sh` para finalizar
   - Cria commit da release
   - Cria tag da versão
   - Prepara notas de release

3. **Validação**: Verifica se todas as validações passam antes da release

## Validações Executadas

- Testes unitários completos
- Análise estática PHPStan Level 9
- Verificação PSR-12
- Testes de segurança
- Benchmarks de performance
- Validação de documentação
- Auditoria de dependências

## Estrutura de Versão

O projeto usa [Semantic Versioning](https://semver.org/):
- **MAJOR**: Mudanças incompatíveis na API
- **MINOR**: Funcionalidades adicionadas de forma compatível
- **PATCH**: Correções de bugs compatíveis

## Arquivos Atualizados

- `VERSION` - Versão principal do projeto
- `composer.json` - Versão do Composer
- `CHANGELOG.md` - Registro de mudanças
- Documentação relevante da versão