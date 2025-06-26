<?php
namespace Express\Helpers;

class Utils
{
    // Sanitização universal
    public static function sanitizeString(mixed $value): mixed
    {
        return is_string($value) ? trim(strip_tags($value)) : $value;
    }
    public static function sanitizeEmail(mixed $value): mixed
    {
        return is_string($value) ? trim($value) : $value;
    }
    public static function sanitizeArray(mixed $arr): mixed
    {
        return is_array($arr) ? array_map([self::class, 'sanitizeString'], $arr) : $arr;
    }
    // Validação universal
    public static function isEmail(mixed $value): bool
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
    public static function isBool(mixed $value): bool
    {
        return is_bool($value) || $value === '0' || $value === '1' || $value === 0 || $value === 1;
    }
    public static function isInt(mixed $value): bool
    {
        return is_numeric($value) && (int)$value == $value;
    }
    public static function isString(mixed $value): bool
    {
        return is_string($value) || is_numeric($value);
    }
    public static function isArray(mixed $value): bool
    {
        return is_array($value);
    }
    // CORS helper
    public static function corsHeaders(
        array $origins = ['*'],
        array $methods = ['GET','POST','PUT','DELETE','OPTIONS'],
        array $headers = ['Content-Type','Authorization']
    ): array {
        return [
            'Access-Control-Allow-Origin' => implode(',', $origins),
            'Access-Control-Allow-Methods' => implode(',', $methods),
            'Access-Control-Allow-Headers' => implode(',', $headers),
            'Access-Control-Allow-Credentials' => 'true',
        ];
    }
    // Logger simples
    public static function log(string $msg, string $level = 'info'): void
    {
        $date = date('Y-m-d H:i:s');
        error_log("[$date][$level] $msg");
    }
    // Geração de token seguro
    public static function randomToken(int $length = 32): string
    {
        $bytesLength = max(1, (int)($length/2));
        return bin2hex(random_bytes($bytesLength));
    }
    // CSRF token
    public static function csrfToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = self::randomToken(32);
        }
        return $_SESSION['csrf_token'];
    }
    public static function checkCsrf(string $token): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
