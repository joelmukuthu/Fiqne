<?php
/**
 * @author Joel Mukuthu
 * @copyright (c) 2010, Fiqne
 * @package Fiqne_MVC_Framework
 * @subpackage Registry
 */
class Registry
{
    /**
     * Stores values in the registry
     * @var array
     */
    protected static $vars = array();

    /**
     * Stores the current offset for use by {@see Registry::push}.
     * @var int
     */
    protected static $currentPushOffset = 0;

    /**
     * Add $value to the registry with key $key
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set($key, $value)
    {
        self::$vars[(string) $key] = $value;
    }

    /**
     * Retrieve $key from the registry
     * @param string $key
     * @return mixed
     * @throws LibraryException
     */
    public static function get($key)
    {
        if(!isset(self::$vars[(string) $key])) {
            throw new LibraryException("The registry key '{$key}' has not been set", E_USER_ERROR);
        }
        return self::$vars[(string) $key];
    }

    /**
     * Pushes a value to the registry. Behave similarly to {@see Registry::set} with the exception that the
     * user does not need to provide a key for the value.
     * @see Registry::pop
     * @param mixed $value
     * @param string $offsetPrefix The prefix to use in generating unique keys to use to store the provided value.
     * This may be used to push and get values that are similar. Must be a two letters! Defaults to 'aa' if none is provided.
     * @return void
     */
    public static function push($value, $offsetPrefix = 'aa')
    {
        $offset = $offsetPrefix . self::$currentPushOffset;
        self::$vars[(string) $offset] = $value;
        self::$currentPushOffset++;
    }

    /**
     * Get value(s) pushed with {@see Registry::push}.
     * @uses Registry::get
     * @param string $offsetPrefix The prefix to use in getting similar pushed values. Must be a two letters! Defaults to 'aa' if none is provided.
     * @return array
     */
    public static function pop($offsetPrefix = 'aa')
    {
        $keys = array_keys(self::$vars);
        $return = array();
        foreach ($keys as $key) {
            if (substr($key, 0, 2) === $offsetPrefix) {
                $return[] = self::get($key);
            }
        }
        return $return;
    }

    /**
     * Check whether a Registry key exists.
     *
     * @param string $key
     *
     * @return true|false
     */
    public static function exists($key)
    {
        return isset(self::$vars[$key]);
    }

    /**
     * Unset a Registry key.
     *
     * @param string $key
     *
     * @return void
     */
    public static function drop($key)
    {
        unset(self::$vars[$key]);
    }

}