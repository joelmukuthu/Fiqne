<?php
/*
 * TODO Do we really need this?
 */
/**
 * Handles cookies.
 *
 * @author Joel Mukuthu
 * @copyright (c) 2010, Fiqne
 * @package Fiqne_MVC_Framework
 * @subpackage Cookie
 */
class Cookie
{
    /**
     * Set a cookie
     * @uses PHP_MANUAL#setcookie To set the cookie
     * @param string $value The value of the cookie
     * @return void
     */
    public static function set($name, $value = '', $expire = 0, $path = '/', $domain = '', $secure = false, $httponly = false)
    {
        if (headers_sent($file, $line)) {
            throw new LibraryException("Headers have already been sent in file '{$file}' at line '{$line}'", E_USER_WARNING);
        }
        setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

    /**
     * Get the value of a cookie
     * @uses PHP_MANUAL#isset To check if cookie named $name is set
     * @param string $name The name of the cookie
     * @return false|string
     */
    public static function get($name)
    {
        if (isset($_COOKIE[$name])) {
            return $_COOKIE[$name];
        } elseif (isset($_REQUEST[$name])) {
            return $_REQUEST[$name];
        }
        return false;
    }

    /**
     * Expire a cookie. Sets the lifetime of a cookie to one hour in the past.
     * This method can be likened to PHP_MANUAL#unset in the scope of cookies
     * @uses PHP_MANUAL#setcookie To set the cookie
     * @param string $name The name of the cookie
     * @return void
     */
    public static function expire($name)
    {
        setcookie($name, "", time() - 3600);
    }
}