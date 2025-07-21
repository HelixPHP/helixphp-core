<?php

namespace PivotPHP\Core\Events;

/**
 * Event Dispatcher
 *
 * Simple and effective event handling for the microframework.
 * Provides basic event functionality without unnecessary complexity.
 *
 * Following 'Simplicidade sobre Otimização Prematura' principle.
 */
class EventDispatcher
{
    /**
     * Event listeners
     */
    private array $listeners = [];

    /**
     * Register a listener for an event
     */
    public function listen(string $event, callable $listener): void
    {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }

        $this->listeners[$event][] = $listener;
    }

    /**
     * Dispatch an event
     */
    public function dispatch(string $event, array $data = []): bool
    {
        if (!isset($this->listeners[$event])) {
            return false;
        }

        foreach ($this->listeners[$event] as $listener) {
            $result = $listener($data);

            // If listener returns false, stop propagation
            if ($result === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Remove listeners for an event
     */
    public function removeListeners(string $event): void
    {
        unset($this->listeners[$event]);
    }

    /**
     * Get all registered events
     */
    public function getEvents(): array
    {
        return array_keys($this->listeners);
    }

    /**
     * Get listener count for an event
     */
    public function getListenerCount(string $event): int
    {
        return count($this->listeners[$event] ?? []);
    }

    /**
     * Clear all listeners
     */
    public function clearAll(): void
    {
        $this->listeners = [];
    }
}
