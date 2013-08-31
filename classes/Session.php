<?php
/**
 * @package   Gears\Framework
 * @author    Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @copyright Copyright (c) 2011-2013 Denis Krasilnikov <deniskrasilnikov86@gmail.com>
 * @license   http://url/license
 */
namespace Gears\Framework;

/**
 * Wrapper to built-in php $_SESSION with some additional functionality
 *
 * @package    Gears\Framework
 * @subpackage App
 */
class Session
{
    /**
     * Indicates whether session was already started or not
     */
    private static $_started = false;

    /**
     * In soft mode the session is self-started on first access
     * with set(), get() or delete() methods
     */
    private static $_softMode = true;

    /**
     * Namespace is used to encapsulate all application session data.
     * This will prevent conflicts in case some other external code
     * is using session also
     */
    private static $_namespace = 'Gears\Framework';

    /**
     * Prevent class instantinating
     */
    private function __construct()
    {
    }

    /**
     * Override default session namespace
     */
    public static function setNamespace($namespace)
    {
        self::$_namespace = $namespace;
    }

    /**
     * Whether session identifier was given in request
     */
    public static function isIdentified()
    {
        if (ini_get('session.use_cookies') == '1' && !empty($_COOKIE[session_name()])) return true;
        if (!empty($_REQUEST[session_name()])) return true;
        return false;
    }

    /**
     * Whether session was already started or not
     */
    public static function isStarted()
    {
        return self::$_started;
    }

    /**
     * Start the session if not started yet
     */
    public static function start()
    {
        if (!self::$_started) {
            session_start();
            self::$_started = true;
        }

        return true;
    }

    /**
     * Put the variable inside the session namespace. Example:
     *
     * Session::set('a.b.c', $value); // == $_SESSION['namespace']['a']['b']['c'] = $value;
     *
     * @param string $nodeString Path to the value inside session array
     * @param mixed $value Value to be set
     */
    public static function set($nodeString, $value)
    {
        if ('' == trim($nodeString)) {
            return false;
        } else {
            if (self::$_softMode) self::start();

            if (self::$_started) {
                $p = & $_SESSION[self::$_namespace];
                $p = (array)$p;

                $path = explode('.', $nodeString);
                foreach ($path as $node) {
                    $p = & $p[$node];
                }

                $p = $value;
            } else {
                trigger_error(__METHOD__ . ' session is not stared', E_USER_WARNING);
            }
        }
    }

    /**
     * Get the session variable from session array within application namespace. In 'soft mode' also
     * start session if one can be identified. Returns whole namespace data if no parameters given.
     * Returns $_SESSION if null given.
     *
     * Triggers E_USER_WARNING in case no session started and not in soft mode
     *
     * @param string $nodeString Path to the value inside session array
     * @return mixed
     */
    public static function get($nodeString = '')
    {
        if (self::$_softMode && self::isIdentified()) self::start();

        if (self::$_started) {
            if (null === $nodeString) return $_SESSION;

            $p = & $_SESSION[self::$_namespace];
            $p = (array)$p;

            if ('' != $nodeString) {
                $path = explode('.', $nodeString);
                foreach ($path as $node) {
                    $p = & $p[$node];
                }
            }

            return $p;
        } else if (!self::$_softMode) {
            trigger_error(__METHOD__ . ' session is not stared', E_USER_WARNING);
        } else return null;
    }

    /**
     * Unset session variable within the namespace. If no parameters given removes the whole namespace
     *
     * @param string $nodeString Path to the value inside session array
     */
    public static function delete($nodeString = null)
    {
        if (self::$_softMode && self::isIdentified()) self::start();

        if (self::$_started) {
            if (is_null($nodeString)) {
                unset($_SESSION[self::$_namespace]);
                return;
            }

            $p = & $_SESSION[self::$_namespace];
            $p = (array)$p;

            $path = explode('.', $nodeString);
            $stop = count($path) - 1;

            for ($i = 0; $i < $stop; $i++) {
                $p = & $p[$path[$i]];
            }

            unset($p[$path[$stop]]);
        } else if (!self::$_softMode) {
            trigger_error(__METHOD__ . ' session is not stared', E_USER_WARNING);
        } else return null;
    }

    /**
     * Enable or disable soft mode
     *
     * @param bool $bool
     */
    public static function softMode($enabled = true)
    {
        self::$_softMode = $enabled;
    }
}