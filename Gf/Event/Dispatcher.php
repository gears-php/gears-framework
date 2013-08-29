<?php
/**
 * @package   Gf
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @copyright Copyright (c) 2011-2013 Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @license   http://url/license
 */
namespace Gf\Event;

/**
 * The base class for all classes that dispatch events
 *
 * @package    Gf
 * @subpackage Events
 */
abstract class Dispatcher
{
    private $_listeners = [];

    /**
     * Adds a particular event listener
     * @param string $event_type Type of event to listen
     * @param callback $listener Callable closure function or class method
     * @return $this
     */
    public function on($event_type, $listener)
    {
        if (is_callable($listener)) {
            if (!isset($this->_listeners[$event_type])) {
                $this->_listeners[$event_type] = [];
            }

            $this->_listeners[$event_type][] = $listener;
        } else {
            trigger_error(sprintf('%s() - the passed event listener is not callable', __METHOD__), E_USER_ERROR);
        }

        return $this;
    }

    /**
     * Removes a particular event listener. If there is no matching listener
     * registered, a call to this method has no effect
     *
     * @param string $event_type
     * @param callback $listener_to_remove
     */
    public function off($event_type, $listener_to_remove)
    {
        if (is_callable($listener_to_remove)) {
            if (isset($this->_listeners[$event_type])) {
                foreach ($this->_listeners[$event_type] as $key => $listener) {
                    if ($listener_to_remove == $listener) {
                        unset($this->_listeners[$event_type][$key]);
                        return;
                    }
                }

                trigger_error(sprintf('%s() - the given listener was not found four "%s" event', __METHOD__, $event_type), E_USER_NOTICE);
            } else {
                trigger_error(sprintf('%s() - no "%s" event listeners registered', __METHOD__, $event_type), E_USER_NOTICE);
            }
        } else {
            trigger_error(sprintf('%s() - the passed event listener is not callable', __METHOD__), E_USER_ERROR);
        }
    }

    /**
     * Dispatch all listeners of a particular event.
     * @param string $event_type
     * @param array $event_params (optional) Array of parameters specific to each event type
     */
    public function dispatch($event_type, array $event_params = [])
    {
        if (isset($this->_listeners[$event_type])) {
            foreach ($this->_listeners[$event_type] as $listener) {
                call_user_func_array($listener, $event_params);
            }
        }
    }
}