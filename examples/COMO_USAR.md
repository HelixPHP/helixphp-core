# Como usar os exemplos do Express-PHP 2.0

## 🚀 Exemplos Disponíveis

### 1. **app.php** - Aplicação Completa
Demonstra todas as funcionalidades do framework com sub-routers e middlewares.

```bash
# Iniciar servidor
cd examples
php -S localhost:8000 app.php

# Acessar no browser:
# http://localhost:8000/          # Página inicial
# http://localhost:8000/docs      # Documentação
# http://localhost:8000/user/123  # Usuário específico
# http://localhost:8000/admin/logs # Logs administrativos
# http://localhost:8000/blog/posts # Posts do blog
```

### 2. **example_modular.php** - Aplicação Básica Modular
Exemplo simples mostrando a estrutura modular básica.

```bash
php -S localhost:8001 example_modular.php

# Endpoints disponíveis:
# GET /                   # Informações básicas
# GET /users/:id         # Usuário por ID
# POST /users            # Criar usuário
# GET /api/status        # Status da API
# GET /api/info          # Informações do framework
```

### 3. **example_security_new.php** - Demonstração de Segurança
Mostra todos os middlewares de segurança em ação.

```bash
php -S localhost:8002 example_security_new.php

# Funcionalidades:
# - CORS configurado
# - Headers de segurança
# - Rate limiting
# - Proteção XSS
# - Proteção CSRF
# - Autenticação JWT
```

### 4. **example_streaming_new.php** - Streaming HTTP
Demonstra streaming, Server-Sent Events e downloads.

```bash
php -S localhost:8003 example_streaming_new.php

# Recursos:
# GET /events            # Server-Sent Events
# GET /stream/data       # JSON streaming
# GET /live/feed         # Feed de dados ao vivo
# GET /chat/stream       # Simulação de chat
# GET /test              # Página de teste HTML
```

## 🔧 Configuração do Ambiente

### Pré-requisitos
- PHP 8.1 ou superior
- Composer
- Extensões: json, mbstring

### Instalação
```bash
# Instalar dependências
composer install

# Executar testes
./vendor/bin/phpunit

# Verificar sintaxe de um exemplo
php -l examples/app.php
```

## 📱 Testando os Endpoints

### Usando curl

```bash
# Testar rota básica
curl http://localhost:8000/

# Testar com parâmetros
curl http://localhost:8000/user/123

# Testar POST
curl -X POST http://localhost:8000/blog/posts \
  -H "Content-Type: application/json" \
  -d '{"title":"Novo Post","content":"Conteúdo do post"}'

# Testar upload (exemplo)
curl -X POST http://localhost:8000/upload \
  -F "file=@arquivo.txt"
```

### Usando JavaScript (Frontend)

```javascript
// Conectar a Server-Sent Events
const eventSource = new EventSource('http://localhost:8003/events');
eventSource.onmessage = function(event) {
    console.log('Event received:', event.data);
};

// Fazer requisições AJAX
fetch('http://localhost:8000/user/123')
    .then(response => response.json())
    .then(data => console.log(data));
```

## 🛡️ Recursos de Segurança

### Autenticação JWT
```bash
# 1. Fazer login
curl -X POST http://localhost:8002/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password"}'

# 2. Usar o token retornado
curl http://localhost:8002/api/profile \
  -H "Authorization: Bearer SEU_TOKEN_AQUI"
```

### Proteção CSRF
```bash
# 1. Obter token CSRF
curl http://localhost:8002/csrf-token

# 2. Usar o token em operações sensíveis
curl -X POST http://localhost:8002/api/sensitive/data \
  -H "Content-Type: application/json" \
  -H "X-CSRF-Token: SEU_TOKEN_CSRF" \
  -d '{"data":"sensitive info"}'
```

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
