#!/bin/bash

# Script para corrigir rapidamente tipagens de array no código

cd /home/cfernandes/projetos-php/express-php

# Corrige alguns padrões comuns de tipagem array no JWTHelper
sed -i 's/@param array \$options/@param array<string, mixed> $options/g' SRC/Helpers/JWTHelper.php
sed -i 's/@return array/@return array<string, mixed>/g' SRC/Helpers/JWTHelper.php
sed -i 's/@param array \$payload/@param array<string, mixed> $payload/g' SRC/Helpers/JWTHelper.php

# Corrige Utils.php
sed -i 's/@param array \$origins/@param array<string> $origins/g' SRC/Helpers/Utils.php
sed -i 's/@param array \$methods/@param array<string> $methods/g' SRC/Helpers/Utils.php
sed -i 's/@param array \$headers/@param array<string, mixed> $headers/g' SRC/Helpers/Utils.php
sed -i 's/@return array/@return array<string, mixed>/g' SRC/Helpers/Utils.php

# Corrige Services
sed -i 's/@var array/@var array<string, mixed>/g' SRC/Services/HeaderRequest.php
sed -i 's/@return array/@return array<string, mixed>/g' SRC/Services/HeaderRequest.php
sed -i 's/@var array/@var array<string, mixed>/g' SRC/Services/Response.php
sed -i 's/@return array/@return array<string, mixed>/g' SRC/Services/Response.php
sed -i 's/@var array/@var array<string, mixed>/g' SRC/Services/Request.php
sed -i 's/@param array \$routers/@param array<int, array<string, mixed>> $routers/g' SRC/Services/OpenApiExporter.php
sed -i 's/@return array/@return array<string, mixed>/g' SRC/Services/OpenApiExporter.php

# Corrige Middlewares Core
sed -i 's/@param array \$options/@param array<string, mixed> $options/g' SRC/Middlewares/Core/*.php

# Corrige Middlewares Security
sed -i 's/@param array \$options/@param array<string, mixed> $options/g' SRC/Middlewares/Security/*.php
sed -i 's/@return array/@return array<string, mixed>/g' SRC/Middlewares/Security/*.php

echo "Correções de tipagem aplicadas!"
