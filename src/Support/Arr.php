<?php

namespace Express\Support;

/**
 * Helper para trabalhar com arrays
 */
class Arr
{
    /**
     * Obtém um valor do array usando notação de ponto
     *
     * @param  mixed $default
     * @return mixed
     */
    public static function get(array $array, string $key, $default = null)
    {
        if (isset($array[$key])) {
            return $array[$key];
        }

        if (strpos($key, '.') === false) {
            return $default;
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * Define um valor no array usando notação de ponto
     *
     * @param mixed $value
     */
    public static function set(array &$array, string $key, $value): void
    {
        $keys = explode('.', $key);
        $current = &$array;

        foreach ($keys as $key) {
            if (!isset($current[$key]) || !is_array($current[$key])) {
                $current[$key] = [];
            }
            $current = &$current[$key];
        }

        $current = $value;
    }

    /**
     * Verifica se uma chave existe usando notação de ponto
     */
    public static function has(array $array, string $key): bool
    {
        if (isset($array[$key])) {
            return true;
        }

        if (strpos($key, '.') === false) {
            return false;
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return false;
            }
            $array = $array[$segment];
        }

        return true;
    }

    /**
     * Remove uma chave do array usando notação de ponto
     */
    public static function forget(array &$array, string $key): void
    {
        $keys = explode('.', $key);
        $current = &$array;

        foreach (array_slice($keys, 0, -1) as $key) {
            if (!isset($current[$key]) || !is_array($current[$key])) {
                return;
            }
            $current = &$current[$key];
        }

        unset($current[end($keys)]);
    }

    /**
     * Achata um array multidimensional
     */
    public static function flatten(array $array, string $delimiter = '.'): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $flattened = static::flatten($value, $delimiter);
                foreach ($flattened as $flatKey => $flatValue) {
                    $result[$key . $delimiter . $flatKey] = $flatValue;
                }
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Filtra um array mantendo apenas as chaves especificadas
     */
    public static function only(array $array, array $keys): array
    {
        return array_intersect_key($array, array_flip($keys));
    }

    /**
     * Filtra um array removendo as chaves especificadas
     */
    public static function except(array $array, array $keys): array
    {
        return array_diff_key($array, array_flip($keys));
    }

    /**
     * Divide um array em chunks
     */
    public static function chunk(array $array, int $size): array
    {
        if ($size < 1) {
            $size = 1;
        }
        return array_chunk($array, $size, true);
    }

    /**
     * Verifica se um array é associativo
     *
     * @deprecated Use Express\Utils\Arr::isAssoc() instead
     */
    public static function isAssoc(array $array): bool
    {
        return \Express\Utils\Arr::isAssoc($array);
    }

    /**
     * Embaralha um array mantendo as chaves
     */
    public static function shuffle(array $array): array
    {
        $keys = array_keys($array);
        shuffle($keys);

        $shuffled = [];
        foreach ($keys as $key) {
            $shuffled[$key] = $array[$key];
        }

        return $shuffled;
    }
}
