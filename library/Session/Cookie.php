<?php
/**
 * Handles setting and getting of php.ini's session cookie settings
 *
 * @author Joel Mukuthu
 * @copyright (c) 2010, Fiqne
 * @package Fiqne_MVC_Framework
 * @subpackage Session
 */
class Session_Cookie
{
    /**
     * Option keys as returned by {@see PHP_MANUAL#session_get_cookie_params};
     * that is, matching php.ini session.cookie settings
     * @var array
     */
    private static $optionKeys = array(
        'lifetime',
        'path',
        'domain',
        'secure',
        'httponly'
        );

    /**
     * Get php.ini session cookie settings
     * @uses PHP_MANUAL#session_get_cookie_params
     * @return array
     */
    public static function getOptions()
    {
        return session_get_cookie_params();
    }

    /**
     * Get the specified session cookie setting in php.ini.
     * @uses PHP_MANUAL#ini_get To get the php.ini setting specified by $optionKey
     * @uses Session_Cookie::optionKeys To validate $optionKey
     * @param string $optionKey Should be specified as 'lifetime' and not 'session.cookie_lifetime' for instance
     * @return null|string
     * @throws LibraryException
     */
    public static function getOption($optionKey)
    {
        if (!in_array($optionKey, self::$optionKeys)) {
            throw new LibraryException("The key supplied '{$optionKey}' is invalid", E_USER_ERROR);
        }
        return ini_get('session.cookie_' . (string) $optionKey);
    }

    /**
     * Set session cookie options in php.ini
     * @uses Session::isStarted To check if a session has been started
     * @uses Session_Cookie::setOption To set the session cookie settings in php.ini
     * @param array $options The array keys should be specified as 'lifetime' and not 'session.cookie_lifetime' for instance
     * @return void
     * @throws LibraryException
     */
    public static function setOptions($options)
    {
        if (Session::isStarted()) {
            throw new LibraryException("A session has already been started. Session_Cookie::setOptions must be called before Session::start", E_USER_ERROR);
        }
        if (!is_array($options)) {
            throw new LibraryException("Options must be an array", E_USER_ERROR);
        }
        foreach ($options as $key => $value) {
            self::setOption($key, $value);
        }
    }

    /**
     * Set the specified session cookie setting in php.ini
     * @uses Session::isStarted To check if a session has been started
     * @uses Session_Cookie::optionKeys To validate $optionKey
     * @uses PHP_MANUAL#ini_set To set setting in php.ini
     * @param string $optionKey Should be specified as 'lifetime' and not 'session.cookie_lifetime' for instance
     * @param string|int $value
     * @return void
     * @throws LibraryException
     */
    public static function setOption($optionKey, $value)
    {
        if (Session::isStarted()) {
            throw new LibraryException("A session has already been started. Session_Cookie::setOption must be called before Session::start", E_USER_ERROR);
        }
        if (!in_array($optionKey, self::$optionKeys)) {
            throw new LibraryException("The key supplied '{$optionKey}' is invalid", E_USER_ERROR);
        }
        ini_set('session.cookie_' . (string) $optionKey, $value);
    }
}