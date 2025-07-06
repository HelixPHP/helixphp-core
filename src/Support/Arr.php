<?php

namespace Helix\Support;

/**
 * Helper para trabalhar com arrays
 */
class Arr
{
    /**
     * Obtém um valor do array usando notação de ponto
     *
     * @deprecated Use Express\Utils\Arr::get() instead
     * @param  mixed $default
     * @return mixed
     */
    public static function get(array $array, string $key, $default = null)
    {
        return \Helix\Utils\Arr::get($array, $key, $default);
    }

    /**
     * Define um valor no array usando notação de ponto
     *
     * @deprecated Use Express\Utils\Arr::set() instead
     * @param mixed $value
     */
    public static function set(array &$array, string $key, $value): void
    {
        \Helix\Utils\Arr::set($array, $key, $value);
    }

    /**
     * Verifica se uma chave existe usando notação de ponto
     *
     * @deprecated Use Express\Utils\Arr::has() instead
     */
    public static function has(array $array, string $key): bool
    {
        return \Helix\Utils\Arr::has($array, $key);
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
     *
     * @deprecated Use Express\Utils\Arr::flatten() instead
     */
    public static function flatten(array $array, string $delimiter = '.'): array
    {
        // A implementação em Utils\Arr::flatten usa depth ao invés de delimiter
        // Para manter compatibilidade, usamos dot() que é equivalente
        return \Helix\Utils\Arr::dot($array, '');
    }

    /**
     * Filtra um array mantendo apenas as chaves especificadas
     *
     * @deprecated Use Express\Utils\Arr::only() instead
     */
    public static function only(array $array, array $keys): array
    {
        return \Helix\Utils\Arr::only($array, $keys);
    }

    /**
     * Filtra um array removendo as chaves especificadas
     *
     * @deprecated Use Express\Utils\Arr::except() instead
     */
    public static function except(array $array, array $keys): array
    {
        return \Helix\Utils\Arr::except($array, $keys);
    }

    /**
     * Divide um array em chunks
     *
     * @deprecated Use Express\Utils\Arr::chunk() instead
     */
    public static function chunk(array $array, int $size): array
    {
        return \Helix\Utils\Arr::chunk($array, $size, true);
    }

    /**
     * Verifica se um array é associativo
     *
     * @deprecated Use Express\Utils\Arr::isAssoc() instead
     */
    public static function isAssoc(array $array): bool
    {
        return \Helix\Utils\Arr::isAssoc($array);
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
