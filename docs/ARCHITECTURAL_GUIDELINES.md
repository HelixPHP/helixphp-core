# PivotPHP Architectural Guidelines

## Princípios Fundamentais

### 1. **Simplicidade sobre Otimização Prematura**
- ❌ **Evitar**: Adicionar otimizações complexas antes de identificar gargalos reais
- ✅ **Fazer**: Implementar funcionalidades simples e medir performance quando necessário
- ✅ **Fazer**: Questionar cada "otimização" se realmente é necessária

### 2. **Separação de Responsabilidades nos Testes**
- ❌ **Evitar**: Misturar testes funcionais com testes de performance
- ✅ **Fazer**: Testes funcionais devem ser rápidos (<1s) e focados em correção
- ✅ **Fazer**: Testes de performance devem ser separados em grupos `@group performance`

### 3. **Timeouts Realistas**
- ❌ **Evitar**: Timeouts extremos (>30s) para mascarar problemas arquiteturais
- ✅ **Fazer**: Timeouts que refletem expectativas reais de produção
- ✅ **Fazer**: Investigar e corrigir a causa raiz quando timeouts precisam ser aumentados

## Diretrizes Específicas

### Performance Mode

#### ❌ **Problema Identificado: HighPerformanceMode**
```php
// 598 linhas de configuração para um microframework
// 40+ configurações, 3 perfis, circuit breakers, etc.
HighPerformanceMode::enable(HighPerformanceMode::PROFILE_EXTREME);
```

#### ✅ **Solução: SimplePerformanceMode**
```php
// 70 linhas, 3 perfis simples, foco no essencial
SimplePerformanceMode::enable(SimplePerformanceMode::PROFILE_PRODUCTION);
```

### Testing Architecture

#### ❌ **Anti-Pattern: Testes Mistos**
```php
// NO: Teste que mistura funcionalidade + performance
public function testHighPerformanceModeWithRealWorkload() {
    // 50 requests em loop
    // Assertions de timing
    // Verificações funcionais
}
```

#### ✅ **Pattern Correto: Separação**
```php
// Teste funcional (rápido)
public function testHighPerformanceModeIntegration() {
    // Verifica se funciona, não quão rápido
}

// Teste de performance (separado)
/**
 * @group performance
 */
public function testHighPerformanceModeRealPerformance() {
    // Foca apenas em métricas de performance
}
```

### Profile Usage

#### Para Desenvolvimento
```php
SimplePerformanceMode::enable(SimplePerformanceMode::PROFILE_DEVELOPMENT);
// - Sem pooling (overhead desnecessário)
// - Logs detalhados
// - Debugging ativo
```

#### Para Testes Automatizados
```php
SimplePerformanceMode::enable(SimplePerformanceMode::PROFILE_TEST);
// - Todas as otimizações desabilitadas
// - Foco na velocidade de execução dos testes
// - Sem overhead de monitoramento
```

#### Para Produção
```php
SimplePerformanceMode::enable(SimplePerformanceMode::PROFILE_PRODUCTION);
// - Apenas otimizações comprovadamente úteis
// - Pooling básico (não extremo)
// - Monitoring essencial
```

## Quando Usar Cada Abordagem

### Use HighPerformanceMode quando:
- ✅ Aplicação com >1000 req/s sustentados
- ✅ Necessidade comprovada de distributed pooling
- ✅ Equipe experiente em high-performance systems

### Use SimplePerformanceMode quando:
- ✅ Microframework para APIs simples
- ✅ Aplicações com <500 req/s
- ✅ Foco em simplicidade e manutenibilidade
- ✅ Equipe prefere código claro a otimizações complexas

## Red Flags Arquiteturais

### 🚨 **Timeout Extremos**
```php
// Se você precisa disso, há problema arquitetural
$this->assertLessThan(60.0, $duration); // 60 segundos!
```

### 🚨 **Over-Engineering**
```php
// 40+ configurações para um framework "micro"
'scale_threshold' => 0.5,
'scale_factor' => 2.5,
'shrink_threshold' => 0.2,
'circuit_threshold' => 200,
'activation_threshold' => 0.8,
```

### 🚨 **Premature Optimization**
- Circuit breakers para APIs simples
- Distributed pooling para <100 req/s
- Load shedding antes de medir carga real

## Métricas de Qualidade

### Testes
- ✅ Testes funcionais: <1s cada
- ✅ Testes de integração: <5s cada
- ✅ Testes de performance: separados em grupos

### Código
- ✅ Classes de configuração: <100 linhas
- ✅ Microframework core: <50 classes
- ✅ Zero dependencies desnecessárias

### Performance
- ✅ Startup time: <10ms
- ✅ Simple request: <1ms
- ✅ Memory footprint: <10MB

## Conclusão

**O objetivo de um microframework é simplicidade**. Otimizações complexas devem ser:
1. **Justificadas** por métricas reais
2. **Opcionais** e não por padrão
3. **Documentadas** com casos de uso específicos
4. **Testadas** separadamente dos testes funcionais

**Lembre-se**: É melhor ter código simples e correto que código "otimizado" e complexo.