<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <denis.krasilnikov@gears.com>
 * @copyright Copyright (c) 2022 Denis Krasilnikov <denis.krasilnikov@gears.com>
 */
namespace Gears\Framework\Events;

use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * The base class for all classes that dispatch events
 *
 * @package    Gears\Framework
 * @subpackage Events
 */
class Dispatcher implements EventDispatcherInterface
{
    private array $listeners = [];

    /**
     * Adds a particular event listener
     */
    public function on(string $eventClass, callable $listener): static
    {
        if (is_callable($listener)) {
            if (!isset($this->listeners[$eventClass])) {
                $this->listeners[$eventClass] = [];
            }

            $this->listeners[$eventClass][] = $listener;
        } else {
            trigger_error(sprintf('%s() - the passed event listener is not callable', __METHOD__), E_USER_ERROR);
        }

        return $this;
    }

    /**
     * Removes a particular event listener. If there is no matching listener
     * registered, a call to this method has no effect
     */
    public function off($eventClass, callable $listenerToRemove): void
    {
        if (is_callable($listenerToRemove)) {
            if (isset($this->listeners[$eventClass])) {
                foreach ($this->listeners[$eventClass] as $key => $listener) {
                    if ($listenerToRemove == $listener) {
                        unset($this->listeners[$eventClass][$key]);
                        return;
                    }
                }

                trigger_error(sprintf('%s() - the given listener was not found four "%s" event', __METHOD__, $eventClass));
            } else {
                trigger_error(sprintf('%s() - no "%s" event listeners registered', __METHOD__, $eventClass));
            }
        } else {
            trigger_error(sprintf('%s() - the passed event listener is not callable', __METHOD__), E_USER_ERROR);
        }
    }

    /**
     * Dispatch all listeners of a particular event.
     */
    public function dispatch(object $event): void
    {
        if (isset($this->listeners[$event::class])) {
            foreach ($this->listeners[$event::class] as $listener) {
                call_user_func_array($listener, [$event]);
            }
        }
    }
}