<?php
namespace Express\SRC\Middlewares\Core;

/**
 * Middleware para validação automática de parâmetros e corpo da requisição com base nos metadados da rota.
 */
class RequestValidationMiddleware
{
    private static function validateType($value, $type) {
        if ($type === 'integer') return is_numeric($value);
        if ($type === 'boolean') return is_bool($value) || $value === '0' || $value === '1' || $value === 0 || $value === 1 || $value === true || $value === false;
        if ($type === 'email') return is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
        if ($type === 'array') return is_array($value);
        if ($type === 'string') return is_string($value) || is_numeric($value);
        return true;
    }
    private static function sanitize($value, $type) {
        if ($type === 'string' && is_string($value)) return trim(strip_tags($value));
        if ($type === 'email' && is_string($value)) return trim($value);
        if ($type === 'array' && is_array($value)) return array_map('trim', $value);
        return $value;
    }
    private function validateField($name, $value, $type, $required, &$errors) {
        if ($required && ($value === null || $value === '')) {
            $errors[] = "Campo obrigatório: $name";
            return;
        }
        if ($value !== null && !self::validateType($value, $type)) {
            $errors[] = "Campo $name deve ser do tipo $type";
        }
    }
    public function __invoke($request, $response, $next)
    {
        $route = $request->matchedRoute ?? null;
        $errors = [];
        if ($route && isset($route['metadata']['parameters'])) {
            foreach ($route['metadata']['parameters'] as $name => $meta) {
                $in = $meta['in'] ?? 'query';
                $type = $meta['type'] ?? 'string';
                $required = $meta['required'] ?? false;
                $value = null;
                if ($in === 'path') {
                    $value = $request->params->{$name} ?? null;
                } elseif ($in === 'query') {
                    $value = $request->query($name);
                } elseif ($in === 'body') {
                    $value = $request->body[$name] ?? null;
                }
                $value = self::sanitize($value, $type);
                $this->validateField($name, $value, $type, $required, $errors);
            }
        }
        // Validação de corpo (requestBody OpenAPI)
        if ($route && isset($route['metadata']['requestBody'])) {
            $schema = $route['metadata']['requestBody']['content']['application/json']['schema'] ?? null;
            if ($schema) {
                // Checa obrigatórios
                if (isset($schema['required'])) {
                    foreach ($schema['required'] as $field) {
                        if (!isset($request->body[$field])) {
                            $errors[] = "Campo obrigatório no body: $field";
                        }
                    }
                }
                // Checa tipos e rejeita extras
                if (isset($schema['properties'])) {
                    foreach ($schema['properties'] as $field => $prop) {
                        $type = $prop['type'] ?? 'string';
                        $value = $request->body[$field] ?? null;
                        $value = self::sanitize($value, $type);
                        $this->validateField($field, $value, $type, in_array($field, $schema['required'] ?? []), $errors);
                    }
                    // Rejeita campos extras
                    foreach ($request->body as $field => $v) {
                        if (!isset($schema['properties'][$field])) {
                            $errors[] = "Campo não permitido no body: $field";
                        }
                    }
                }
            }
        }
        if (!empty($errors)) {
            return $response->status(400)->json([
                'error' => true,
                'message' => 'Erro de validação',
                'fields' => $errors
            ]);
        }
        $next();
    }
}
