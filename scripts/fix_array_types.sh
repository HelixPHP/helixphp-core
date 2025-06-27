#!/bin/bash

# Script para corrigir rapidamente tipagens de array no código

cd /home/cfernandes/projetos-php/express-php

# Corrige alguns padrões comuns de tipagem array no JWTHelper
sed -i 's/@param array \$options/@param array<string, mixed> $options/g' src/Helpers/JWTHelper.php
sed -i 's/@return array/@return array<string, mixed>/g' src/Helpers/JWTHelper.php
sed -i 's/@param array \$payload/@param array<string, mixed> $payload/g' src/Helpers/JWTHelper.php

# Corrige Utils.php
sed -i 's/@param array \$origins/@param array<string> $origins/g' src/Helpers/Utils.php
sed -i 's/@param array \$methods/@param array<string> $methods/g' src/Helpers/Utils.php
sed -i 's/@param array \$headers/@param array<string, mixed> $headers/g' src/Helpers/Utils.php
sed -i 's/@return array/@return array<string, mixed>/g' src/Helpers/Utils.php

# Corrige Services
sed -i 's/@var array/@var array<string, mixed>/g' src/Services/HeaderRequest.php
sed -i 's/@return array/@return array<string, mixed>/g' src/Services/HeaderRequest.php
sed -i 's/@var array/@var array<string, mixed>/g' src/Services/Response.php
sed -i 's/@return array/@return array<string, mixed>/g' src/Services/Response.php
sed -i 's/@var array/@var array<string, mixed>/g' src/Services/Request.php
sed -i 's/@param array \$routers/@param array<int, array<string, mixed>> $routers/g' src/Services/OpenApiExporter.php
sed -i 's/@return array/@return array<string, mixed>/g' src/Services/OpenApiExporter.php

# Corrige Middlewares Core
sed -i 's/@param array \$options/@param array<string, mixed> $options/g' src/Middlewares/Core/*.php

# Corrige Middlewares Security
sed -i 's/@param array \$options/@param array<string, mixed> $options/g' src/Middlewares/Security/*.php
sed -i 's/@return array/@return array<string, mixed>/g' src/Middlewares/Security/*.php

echo "Correções de tipagem aplicadas!"
