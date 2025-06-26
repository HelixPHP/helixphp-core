#!/bin/bash

# Script para corrigir os erros restantes do PHPStan

cd /home/cfernandes/projetos-php/express-php

echo "Corrigindo erros restantes..."

# Corrige JWTHelper - métodos específicos
sed -i 's/public static function encodeHS256(array \$payload/public static function encodeHS256(array \/** @var array<string, mixed> *\/ \$payload/g' src/Helpers/JWTHelper.php
sed -i 's/): array/): array \/** @return array<string, mixed> *\//g' src/Helpers/JWTHelper.php

# Corrige Utils - parâmetros específicos
sed -i 's/corsHeaders(array \$origins = \[\]/corsHeaders(array \/** @var array<string> *\/ \$origins = []/g' src/Helpers/Utils.php
sed -i 's/, array \$methods = \[\]/, array \/** @var array<string> *\/ \$methods = []/g' src/Helpers/Utils.php
sed -i 's/, array \$headers = \[\]/, array \/** @var array<string, mixed> *\/ \$headers = []/g' src/Helpers/Utils.php

# Corrige AuthMiddleware
sed -i 's/private array \$options/private array \/** @var array<string, mixed> *\/ \$options/g' src/Middlewares/Security/AuthMiddleware.php

# Corrige RequestValidationMiddleware
sed -i 's/validateField.*array &\$errors/validateField(mixed \$value, array \$rules, string \$fieldName, array \/** @var array<string, string> *\/ \&\$errors/g' src/Middlewares/Core/RequestValidationMiddleware.php

echo "Script executado com sucesso!"
