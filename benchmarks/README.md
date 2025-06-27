# 📊 Express PHP Framework - Benchmarks

*Última atualização: 27 de Junho de 2025*

Sistema de benchmarks para análise de performance do Express PHP Framework.

---

## 🚀 Resultados Atuais

### 📈 **Performance Highlights (27/06/2025)**

| Componente | Ops/Segundo | Tempo Médio | Carga Ideal |
|------------|-------------|-------------|-------------|
| **CORS Headers Generation** | **47.7M** | **0.02 μs** | Normal |
| **CORS Headers Processing** | **43.3M** | **0.02 μs** | High |
| **Response Object Creation** | **23.8M** | **0.04 μs** | Normal |
| **CORS Configuration** | **19.3M** | **0.05 μs** | High |
| **JSON Encode (Small)** | **10.6M** | **0.09 μs** | Normal |
| **XSS Protection Logic** | **4.2M** | **0.24 μs** | Low |
| **Route Pattern Matching** | **2.5M** | **0.40 μs** | High |
| **App Initialization** | **715K** | **1.40 μs** | High |

### 🏆 **Eficiência de Memória**
- **Framework overhead:** 1.36 KB/instance
- **Cache hit ratio:** 98%
- **Total memory (CORS):** 2KB

---

## 🔧 Como Executar

### Benchmark Completo (Recomendado)
```bash
# Executa todas as cargas (Low/Normal/High)
php ExpressPhpBenchmark.php
```

### Benchmark via Script
```bash
# Benchmark rápido
./run_benchmark.sh

# Benchmark com resultados detalhados
./run_benchmark.sh --verbose
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

### 3. **Métricas Coletadas**
- **Operações por segundo (ops/s)**
- **Tempo médio de execução (μs)**
- **Uso de memória (bytes)**
- **Cache hit ratio (%)**

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

**Express PHP Framework** - Benchmarks para garantir performance de classe mundial! 🚀
