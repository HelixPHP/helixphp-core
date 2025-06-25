<?php
namespace Express\SRC\Helpers;

class Utils
{
    // Sanitização universal
    public static function sanitizeString($value) {
        return is_string($value) ? trim(strip_tags($value)) : $value;
    }
    public static function sanitizeEmail($value) {
        return is_string($value) ? trim($value) : $value;
    }
    public static function sanitizeArray($arr) {
        return is_array($arr) ? array_map([self::class, 'sanitizeString'], $arr) : $arr;
    }
    // Validação universal
    public static function isEmail($value) {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
    public static function isBool($value) {
        return is_bool($value) || $value === '0' || $value === '1' || $value === 0 || $value === 1 || $value === true || $value === false;
    }
    public static function isInt($value) {
        return is_numeric($value) && (int)$value == $value;
    }
    public static function isString($value) {
        return is_string($value) || is_numeric($value);
    }
    public static function isArray($value) {
        return is_array($value);
    }
    // CORS helper
    public static function corsHeaders($origins = ['*'], $methods = ['GET','POST','PUT','DELETE','OPTIONS'], $headers = ['Content-Type','Authorization']) {
        return [
            'Access-Control-Allow-Origin' => implode(',', $origins),
            'Access-Control-Allow-Methods' => implode(',', $methods),
            'Access-Control-Allow-Headers' => implode(',', $headers),
            'Access-Control-Allow-Credentials' => 'true',
        ];
    }
    // Logger simples
    public static function log($msg, $level = 'info') {
        $date = date('Y-m-d H:i:s');
        error_log("[$date][$level] $msg");
    }
    // Geração de token seguro
    public static function randomToken($length = 32) {
        return bin2hex(random_bytes($length/2));
    }
    // CSRF token
    public static function csrfToken() {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = self::randomToken(32);
        }
        return $_SESSION['csrf_token'];
    }
    public static function checkCsrf($token) {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
