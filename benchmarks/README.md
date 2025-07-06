# 📊 HelixPHP Framework - Benchmarks

*Última atualização: 6 de Julho de 2025*

Sistema de benchmarks para análise de performance do HelixPHP Framework.

---

## 🚀 Resultados Atuais

### 📈 **Performance Highlights (06/07/2025)**

| Componente | Ops/Segundo | Tempo Médio | Benchmark |
|------------|-------------|-------------|-----------|
| **Response Object Creation** | **2.58M** | **0.39 μs** | SimpleBenchmark |
| **CORS Headers Generation** | **2.57M** | **0.39 μs** | ExpressPhpBenchmark |
| **CORS Headers Processing** | **2.40M** | **0.42 μs** | ExpressPhpBenchmark |
| **JSON Encode (Small)** | **1.69M** | **0.59 μs** | ExpressPhpBenchmark |
| **XSS Protection Logic** | **1.13M** | **0.89 μs** | ExpressPhpBenchmark |
| **Route Pattern Matching** | **757K** | **1.32 μs** | ExpressPhpBenchmark |
| **Middleware Execution** | **293K** | **3.41 μs** | ExpressPhpBenchmark |
| **JWT Token Generation** | **114K** | **8.74 μs** | SimpleBenchmark |
| **App Initialization** | **95K** | **10.47 μs** | SimpleBenchmark |

### 🏆 **Eficiência de Memória**
- **Framework overhead:** 5.6 KB/instance
- **Total memory para 100 apps:** 379 KB
- **Peak memory usage:** < 8MB para 10,000 operações

---

## 🔧 Como Executar

### 🐳 Com Docker (Recomendado)
```bash
# Build e execução completa
docker-compose -f docker-compose.benchmark.yml up

# Benchmark específico
docker-compose -f docker-compose.benchmark.yml run app php benchmarks/DatabaseBenchmark.php

# Multi-database comparison
docker-compose -f docker-compose.benchmark.yml run app php benchmarks/MultiDatabaseBenchmark.php
```

Veja o [guia completo de Docker Benchmarks](DOCKER_BENCHMARKS.md).

### Execução Local
```bash
# Benchmark simples
php SimpleBenchmark.php

# Benchmark completo do framework
php ExpressPhpBenchmark.php

# Benchmark via script
./run_benchmark.sh
```

### Benchmark Comparativo
```bash
# Compara resultados atuais vs histórico
php compare_benchmarks.php
```

---

## 📋 Scripts Disponíveis

| Script | Função | Tempo |
|--------|--------|-------|
| `ExpressPhpBenchmark.php` | Benchmark principal | ~45s |
| `run_benchmark.sh` | Script automatizado | ~30s |
| `compare_benchmarks.php` | Comparação de resultados | ~5s |
| `generate_comprehensive_report.php` | Relatório detalhado | ~10s |

---

## 📊 Tipos de Benchmark

### 1. **Load Testing**
- **Low:** 100 iterações (desenvolvimento)
- **Normal:** 1,000 iterações (produção)
- **High:** 10,000 iterações (enterprise)

### 2. **Componentes Testados**
- **Core Framework:** Inicialização, routing, middleware
- **CORS Processing:** Headers, configuração, cache
- **Security:** XSS protection, validação
- **Performance:** JSON encoding, response creation
- **Database Operations:** MySQL, PostgreSQL, MariaDB, SQLite

### 3. **Métricas Coletadas**
- **Operações por segundo (ops/s)**
- **Tempo médio de execução (μs)**
- **Uso de memória (bytes)**
- **Cache hit ratio (%)**
- **Database latency (ms)**

---

## 📈 Interpretação dos Resultados

### ✅ **Performance Excelente (>10M ops/s)**
- CORS Headers Generation: 47.7M ops/s
- CORS Headers Processing: 43.3M ops/s
- Response Object Creation: 23.8M ops/s
- CORS Configuration: 19.3M ops/s

### 🥇 **Performance Boa (1M-10M ops/s)**
- JSON Encode (Small): 10.6M ops/s
- XSS Protection Logic: 4.2M ops/s
- Route Pattern Matching: 2.5M ops/s

### ⚠️ **Pontos de Atenção**
- JWT Token Generation: Queda significativa em high load
- Memory usage: Ligeiro aumento em low load

---

## 🗄️ Performance com Bancos de Dados

### Comparação de Performance (req/s)

| Operação | SQLite | MariaDB | MySQL | PostgreSQL |
|----------|---------|---------|--------|------------|
| **Simple SELECT** | 7,812 | 4,234 | 4,123 | 3,567 |
| **JOIN Query** | 3,123 | 1,789 | 1,654 | 1,945 |
| **INSERT** | 4,876 | 3,123 | 2,945 | 2,567 |
| **UPDATE** | 5,432 | 3,445 | 3,234 | 2,876 |

### Recomendações por Cenário
- **Desenvolvimento/Testes:** SQLite (melhor performance, zero config)
- **Produção Pequena/Média:** MariaDB (melhor que MySQL, compatível)
- **Produção Grande:** PostgreSQL (recursos avançados, escalabilidade)
- **Legacy:** MySQL (compatibilidade, suporte)

Veja análise completa em [Database Performance](../docs/performance/DATABASE_PERFORMANCE.md).

---

## 🎯 Configuração de Benchmark

### Ambiente Recomendado
```bash
# Configuração para benchmarks consistentes
ulimit -n 65536          # Aumentar file descriptors
php.ini:
  memory_limit = 512M
  max_execution_time = 300
  opcache.enable = 1
  opcache.enable_cli = 1
```

### Variáveis de Ambiente
```bash
# .env para benchmarks
BENCHMARK_ITERATIONS_LOW=100
BENCHMARK_ITERATIONS_NORMAL=1000
BENCHMARK_ITERATIONS_HIGH=10000
BENCHMARK_OUTPUT_DIR=./reports
BENCHMARK_COMPARE_BASELINE=true
```

---

## 📁 Estrutura de Relatórios

```
reports/
├── COMPREHENSIVE_PERFORMANCE_SUMMARY.md  # Relatório principal
├── baseline.json                         # Baseline para comparação
├── benchmark_low_YYYY-MM-DD_HH-MM-SS.json
├── benchmark_normal_YYYY-MM-DD_HH-MM-SS.json
└── benchmark_high_YYYY-MM-DD_HH-MM-SS.json
```

---

## 🔍 Análise Detalhada

### Escalabilidade
- **Linear:** CORS processing, Route matching
- **Sub-linear:** JWT generation, Memory allocation
- **Super-linear:** App initialization (cache benefits)

### Otimizações Implementadas
- **Pre-compiled headers** para CORS
- **String-based operations** para máxima velocidade
- **Memory-efficient caching**
- **Pipeline optimization** para middleware

---

## 🚀 Próximos Passos

### Benchmarks Planejados
1. **HTTP/2 Support** - Performance com protocolo moderno
2. **Async Processing** - Operações não-bloqueantes
3. **Database Integration** - ORM e query performance
4. **WebSocket Performance** - Real-time operations

### Melhorias Contínuas
1. **Automated CI/CD benchmarks** - Regressão automática
2. **Cross-platform testing** - Windows, macOS, Linux
3. **PHP version compatibility** - 8.1, 8.2, 8.3
4. **Memory profiling** - Detailed memory analysis

---

## 📚 Links Úteis

- **[📊 Relatório Completo](reports/COMPREHENSIVE_PERFORMANCE_SUMMARY.md)**
- **[🔧 Guia de Implementação](../docs/guides/QUICK_START_GUIDE.md)**
- **[📖 Documentação](../docs/DOCUMENTATION_INDEX.md)**

---

**HelixPHP Framework** - Benchmarks para garantir performance de classe mundial! 🚀
