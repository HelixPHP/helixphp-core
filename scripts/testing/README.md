# Testing Scripts

Este diretório contém scripts para execução de testes e validação em diferentes ambientes.

## Scripts Disponíveis

### run_stress_tests.sh
Executa testes de stress e performance intensiva.
```bash
./scripts/testing/run_stress_tests.sh
```

### test-all-php-versions.sh
Testa o framework em múltiplas versões do PHP via Docker.
```bash
./scripts/testing/test-all-php-versions.sh
```

## Tipos de Teste

### Testes de Stress
- Testes de carga intensiva
- Validação de vazamentos de memória
- Testes de concorrência
- Benchmarks de performance prolongados

### Testes Multi-Versão PHP
- Compatibilidade com PHP 8.1, 8.2, 8.3, 8.4
- Validação em ambientes Docker limpos
- Testes de funcionalidades específicas por versão
- Verificação de depreciações

## Uso Recomendado

### Para Desenvolvimento
```bash
# Testes rápidos durante desenvolvimento
composer test

# Testes de stress antes de releases
./scripts/testing/run_stress_tests.sh
```

### Para CI/CD
```bash
# Validação multi-versão PHP (completa)
composer docker:test-all

# Apenas qualidade multi-versão
composer docker:test-quality
```

## Configuração de Ambiente

### Docker
Os scripts utilizam Docker para isolamento e consistência:
- Imagens oficiais do PHP
- Ambiente limpo para cada teste
- Resultados reproduzíveis

### Suites de Teste
- **Unit**: Testes unitários rápidos
- **Fast**: Testes excluindo stress e integração
- **CI**: Testes otimizados para CI/CD
- **Stress**: Testes de performance intensiva
- **Integration**: Testes de integração completos

## Relatórios

Os testes geram relatórios em `reports/testing/`:
- Resultados por versão PHP
- Métricas de performance
- Logs de falhas detalhados
- Comparativos de compatibilidade