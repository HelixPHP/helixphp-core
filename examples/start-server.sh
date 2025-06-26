#!/bin/bash

# Script para iniciar o servidor PHP com suporte a URLs amigáveis
# Uso: ./start-server.sh [porta]

PORT=${1:-8000}
ROUTER_FILE="router.php"

echo "🚀 Iniciando servidor PHP na porta $PORT"
echo "📁 Diretório: $(pwd)"
echo "🔗 Acesse: http://localhost:$PORT/app/user/1234"
echo ""
echo "URLs disponíveis:"
echo "  - http://localhost:$PORT/app/user/1234"
echo "  - http://localhost:$PORT/app/admin/dashboard"
echo "  - http://localhost:$PORT/app/upload"
echo "  - http://localhost:$PORT/app/blog/posts"
echo ""
echo "Pressione Ctrl+C para parar o servidor"
echo ""

php -S localhost:$PORT $ROUTER_FILE
