<?php

namespace Helix\Http\Contracts;

/**
 * Interface para objetos que suportam atributos dinâmicos.
 */
interface AttributeInterface
{
    /**
     * Adiciona um atributo dinâmico.
     *
     * @param  string $name  Nome do atributo
     * @param  mixed  $value Valor do atributo
     * @return self
     */
    public function setAttribute(string $name, $value): self;

    /**
     * Obtém um atributo dinâmico.
     *
     * @param  string $name    Nome do atributo
     * @param  mixed  $default Valor padrão se não encontrado
     * @return mixed
     */
    public function getAttribute(string $name, $default = null);

    /**
     * Verifica se um atributo dinâmico existe.
     *
     * @param  string $name Nome do atributo
     * @return bool
     */
    public function hasAttribute(string $name): bool;

    /**
     * Remove um atributo dinâmico.
     *
     * @param  string $name Nome do atributo
     * @return self
     */
    public function removeAttribute(string $name): self;

    /**
     * Obtém todos os atributos dinâmicos.
     *
     * @return array<string, mixed>
     */
    public function getAttributes(): array;

    /**
     * Define múltiplos atributos dinâmicos de uma vez.
     *
     * @param  array<string, mixed> $attributes
     * @return self
     */
    public function setAttributes(array $attributes): self;
}
