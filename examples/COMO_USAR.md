# 🚀 Como Usar os Exemplos do Express PHP

Este guia mostra como executar e testar os exemplos práticos do Express PHP.

## 📋 Pré-requisitos

- PHP 8.1 ou superior
- Composer instalado
- Dependências instaladas: `composer install`

## 🎯 Exemplos Disponíveis

### 1. **example_basic.php** - Exemplo Básico ⭐
**O melhor para começar!**

```bash
# Executar
php -S localhost:8000 examples/example_basic.php

# Testar
curl http://localhost:8000/
curl http://localhost:8000/api/users
```

### 2. **example_auth_simple.php** - Autenticação JWT 🔐

```bash
# Executar
php -S localhost:8000 examples/example_auth_simple.php

# Fazer login
curl -X POST http://localhost:8000/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"123456"}'

# Usar o token retornado
curl -H "Authorization: Bearer SEU_TOKEN" http://localhost:8000/auth/me
```

### 3. **example_middleware.php** - Middlewares Avançados 🛡️

```bash
# Executar
php -S localhost:8000 examples/example_middleware.php

# Testar API de produtos
curl http://localhost:8000/api/products
curl http://localhost:8000/api/products?category=electronics

# Criar produto
curl -X POST http://localhost:8000/api/products \
  -H "Content-Type: application/json" \
  -d '{"name":"Novo Produto","price":99.99,"category":"test"}'
```

### 4. **app.php** - Aplicação Completa 🚀

```bash
# Executar
php -S localhost:8000 examples/app.php

# Explorar todas as funcionalidades
curl http://localhost:8000/
```

## 🔧 Métodos de Execução

### Método 1: Servidor Built-in (Recomendado)
```bash
php -S localhost:8000 examples/example_basic.php
```

### Método 2: Script de Inicialização
```bash
chmod +x examples/start-server.sh
./examples/start-server.sh example_basic.php
```

## 🧪 Testes Práticos

### API REST Básica
```bash
# Listar usuários
curl http://localhost:8000/api/users

# Criar usuário
curl -X POST http://localhost:8000/api/users \
  -H "Content-Type: application/json" \
  -d '{"name":"João Silva","email":"joao@example.com"}'

# Buscar usuário específico
curl http://localhost:8000/api/users/1

# Atualizar usuário
curl -X PUT http://localhost:8000/api/users/1 \
  -H "Content-Type: application/json" \
  -d '{"name":"João Santos"}'
```

### Autenticação JWT
```bash
# 1. Fazer login
curl -X POST http://localhost:8000/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@example.com","password":"123456"}'

# 2. Usar token para acessar rota protegida
curl -H "Authorization: Bearer SEU_TOKEN" http://localhost:8000/auth/me
```

## 📚 Próximos Passos

1. **Começe pelo `example_basic.php`**
2. **Estude o código** - Cada exemplo tem comentários detalhados
3. **Modifique e experimente** - Faça suas próprias alterações
4. **Use os snippets** - Copie código da pasta `snippets/`
5. **Leia a documentação** - Consulte `docs/` para funcionalidades avançadas

---

**🎯 Divirta-se explorando o Express PHP!**

## 🔍 Monitoramento e Debug

### Logs
Todos os exemplos incluem headers customizados para debug:
- `X-Powered-By: Express-PHP-2.0`
- `X-Module: [Module-Name]`

### Performance
Use ferramentas como:
- Browser DevTools (Network tab)
- curl com `-w` flag para timing
- Apache Bench para load testing

## 📚 Estrutura dos Exemplos

```
examples/
├── app.php                    # Aplicação completa
├── app_clean.php             # Versão limpa (backup)
├── example_modular.php       # Básico modular
├── example_security_new.php  # Segurança
├── example_streaming_new.php # Streaming
├── example_auth.php          # Autenticação (em atualização)
└── router.php               # Router para servidor built-in
```

## 🎯 Próximos Passos

1. **Personalizar** os exemplos para suas necessidades
2. **Estudar** o código-fonte para entender a arquitetura
3. **Implementar** seus próprios middlewares
4. **Criar** suas aplicações usando a base modular

## ⚠️ Notas Importantes

- **Desenvolvimento**: Use `php -S` apenas para desenvolvimento
- **Produção**: Configure um servidor web real (Apache/Nginx)
- **Segurança**: Sempre use HTTPS em produção
- **Performance**: Configure opcache e otimizações de PHP

---

**Express-PHP 2.0** - Framework moderno, seguro e performático! 🚀
