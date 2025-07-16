<?php

namespace PivotPHP\Core\Legacy\Events;

/**
 * Sistema de eventos para PivotPHP
 *
 * @deprecated Use SimpleEventDispatcher instead. This class will be removed in v1.2.0.
 * Following 'Simplicidade sobre Otimização Prematura' principle.
 */
class EventDispatcher
{
    private array $listeners = [];

    /**
     * Registra um listener para um evento
     */
    public function listen(
        string $event,
        callable $listener,
        int $priority = 0
    ): void {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }

        $this->listeners[$event][] = [
            'listener' => $listener,
            'priority' => $priority
        ];

        // Ordena por prioridade (maior prioridade primeiro)
        usort(
            $this->listeners[$event],
            function ($a, $b) {
                return $b['priority'] <=> $a['priority'];
            }
        );
    }

    /**
     * Dispara um evento
     */
    public function dispatch(string $event, array $data = []): void
    {
        if (!isset($this->listeners[$event])) {
            return;
        }

        $eventObject = new Event($event, $data);

        foreach ($this->listeners[$event] as $listenerData) {
            if ($eventObject->isPropagationStopped()) {
                break;
            }

            call_user_func($listenerData['listener'], $eventObject);
        }
    }

    /**
     * Remove todos os listeners de um evento
     */
    public function forget(string $event): void
    {
        unset($this->listeners[$event]);
    }

    /**
     * Remove todos os listeners
     */
    public function forgetAll(): void
    {
        $this->listeners = [];
    }

    /**
     * Verifica se um evento tem listeners
     */
    public function hasListeners(string $event): bool
    {
        return isset($this->listeners[$event]) && !empty($this->listeners[$event]);
    }

    /**
     * Retorna todos os listeners de um evento
     */
    public function getListeners(string $event): array
    {
        return $this->listeners[$event] ?? [];
    }
}
