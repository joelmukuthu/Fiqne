<?php
/**
 * Handles session manipulation.
 *
 * @author Joel Mukuthu
 * @copyright (c) 2010, Fiqne
 * @package Fiqne_MVC_Framework
 * @subpackage Session
 */
class Session
{
    /**
     * Flag to indicate whether a session has been started or not.
     * @var bool
     */
    protected static $sessionStarted = false;

    /**
     * The base session key for the session. i.e. $_SESSION['baseKey'].
     * @var string
     */
    protected static $baseSessionKey = '__FIQNE__';

    /**
     * Flag to indicate if the session is read only (not writable) or not.
     * @var bool
     */
    protected static $readOnly = false;

    /**
     * The default session cookie options.
     * @var array
     */
    protected static $defaultSessionCookieOptions = array(
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => 'www.fiqne.com',
        'secure'   => 'off',
        'httponly' => 'on'
        );

    /**
     * The name of the session. Eliminates the need for subsequent calls to {@see PHP_MANUAL#session_name}
     * after the first call of {@see Session::getName}.
     * @var string
     */
    public static $sessionName = false;

    /**
     * The session id. Eliminates the need for subsequent calls to {@see PHP_MANUAL#session_id}
     * after the first call of {@see Session::getId}.
     * @var string
     */
    public static $sessionId = false;

    /**
     * @todo Will we ever need to construct an instance of {@see Session}? All properties and methods are static!
     */
    public function __construct()
    {
    }

    /**
     * Set a session key $key with value $value.
     * @uses Session::isStarted To check whether a session has been started.
     * @uses Session::isWritable To check whether writing to the current session is allowed.
     * @uses Session::writeClose To commit the new session data.
     * @uses PHP_MANUAL#is_string
     * @uses PHP_MANUAL#is_int
     * @uses Session::baseSessionKey To set the base sesssion key for the current session.
     * @param string $key Using integers for keys is discouraged as the user will have to keep track
     * of assigned keys to avoid overwriting already defined keys.
     * @param mixed $value
     * @return void
     * @throws LibraryException
     */
    public static function set($key, $value)
    {
        if(!is_string($key) && !is_int($key)) {
            throw new LibraryException("The key supplied is neither a string nor an interger", E_USER_WARNING);
        }
        if(!self::isStarted()) {
            throw new LibraryException("A session has not been started", E_USER_ERROR);
        }
        if(!self::isWritable()) {
            throw new LibraryException("Writing to the current session has been disabled", E_USER_ERROR);
        }
        $_SESSION[self::$baseSessionKey][$key] = $value;
    }

    /**
     * Get the value of a session key $key.
     * @uses Session::keyExists To check if supplied key exists within the session.
     * @uses Session::baseSessionKey To get the base sesssion key for the current session.
     * @param string $key
     * @return mixed
     * @throws LibraryException
     */
    public static function get($key)
    {
        if(!self::keyExists($key)) {
            throw new LibraryException("The key '{$key}' does not exist in the session", E_USER_WARNING);
        }
        return $_SESSION[self::$baseSessionKey][$key];
    }

    /**
     * Check whether a key $key exists within the current session.
     * @uses Session::isStarted To check whether a session has been started.
     * @uses PHP_MANUAL#isset
     * @uses Session::baseSessionKey To get the base sesssion key for the current session.
     * @param srting $key
     * @return true|false
     * @throws LibraryException
     */
    public static function keyExists($key)
    {
        if(!self::isStarted()) {
            throw new LibraryException("A session has not been started", E_USER_ERROR);
        }
        return isset($_SESSION[self::$baseSessionKey][$key]);
    }

    /**
     * Unset session key $key.
     * @uses Session::isStarted To check whether a session has been started.
     * @uses PHP_MANUAL#unset
     * @uses Session::baseSessionKey To get the base sesssion key for the current session.
     * @param string $key
     * @return void
     * @throws LibraryException
     */
    public static function unsetKey($key)
    {
        if(!self::isStarted()) {
            throw new LibraryException("A session has not been started", E_USER_ERROR);
        }
        unset($_SESSION[self::$baseSessionKey][$key]);
    }

    /**
     * Check if a session has been started for the current request.
     * @uses Session::sessionStarted
     * @return true|false
     */
    public static function isStarted()
    {
        return self::$sessionStarted;
    }

    /**
     * Set the default base key to use for the session, once it's started.
     * As such, must be called before {@see Session::start}.
     * @uses Session::isStarted To check whether a session has been started.
     * @param string $key
     * @throws LibraryException
     */
    public static function setBaseKey($key = 'FIQNE')
    {
        if(self::isStarted()) {
            throw new LibraryException("A session has already been started. Session::setBaseKey must be called before Session::start", E_USER_ERROR);
        }
        self::$baseSessionKey = $key;
    }

    /**
     * Get the session base key.
     * @return string
     */
    public static function getBaseKey()
    {
        return self::$baseSessionKey;
    }

    /**
     * Set session name. Must be called before {@see Session::start}.
     * @uses Session::isStarted To check whether a session has been started.
     * @uses PHP_MANUAL#session_name
     * @param string $newName
     * @return void
     * @throws LibraryException
     */
    public static function setName($newName)
    {
        if(self::isStarted()) {
            throw new LibraryException("A session has already been started. Session::setName must be called before Session::start", E_USER_ERROR);
        }
        session_name($newName);
        self::$sessionName = $newName;
    }

    /**
     * Get session name.
     * @uses Session::isStarted To check whether a session has been started.
     * @uses PHP_MANUAL#session_name
     * @return string
     * @throws LibraryException
     */
    public static function getName()
    {
        if(!self::isStarted()) {
            throw new LibraryException("A session has not been started", E_USER_ERROR);
        }
        if (!self::$sessionName) {
            self::$sessionName = session_name();
        }
        return self::$sessionName;
    }

    /**
     * Set the session id. Must be called before {@see Session::start}.
     * @uses Session::isStarted To check whether a session has been started.
     * @uses PHP_MANUAL#session_id
     * @param string $newId
     * @return void
     * @throws LibraryException
     */
    public static function setId($newId)
    {
        if (self::isStarted()) {
            throw new LibraryException("A session has already been started. Session::setId must be called before Session::start", E_USER_ERROR);
        }
        if (is_int($newId)) {
            throw new LibraryException("Session id cannot be all integers. Supplied with '{$newId}'", E_USER_ERROR);
        }
        session_id($newId);
        self::$sessionName = $newId;
    }

    /**
     * Get the session id.
     * @uses Session::isStarted To check whether a session has been started
     * @uses PHP_MANUAL#session_id
     * @return string
     * @throws LibraryException
     */
    public static function getId()
    {
        if(!self::isStarted()) {
            throw new LibraryException("A session has not been started", E_USER_ERROR);
        }
        if (!self::$sessionId) {
            self::$sessionId = session_id();
        }
        return self::$sessionId;
    }

    /**
     * Start a session. If a session has already been started, this method is not executed further
     * to avoid a notice from {@see PHP_MANUAL#session_start}.
     * This method explicitly sets the 'session.use_only_cookies' php.ini directive to ensure sessions
     * are only passed using cookies only.
     * @uses PHP_MANUAL#session_start
     * @uses PHP_MANUAL#ini_set To set the 'session.use_only_cookies' php.ini directive.
     * @uses Session::isStarted To check whether a session has been started.
     * @uses Session_Cookie::setOptions
     * @uses Session::sessionStarted Sets it to true once a session has been started.
     * @return void
     * @throws LibraryException
     */
    public static function start()
    {
        if(self::isStarted()) {
            return;
        }
        //Ensure session id is only passed via cookies
        ini_set('session.use_only_cookies', 1);
        //set session cookie options
        Session_Cookie::setOptions(self::$defaultSessionCookieOptions);
        /**
         * This true|false scenario with {@see PHP_MANUAL#session_start} will only work in php >= 5.3.0.
         * Earlier versions will always return true.
         */
        if (strnatcmp(phpversion(), '5.3') >= 0) {
            if (session_start()) {
                self::$sessionStarted = true;
            } else {
                throw new LibraryException("Could not start a session, unhandled error", E_COMPILE_ERROR);
            }
        } else {
            session_start();
        }
    }

    /**
     * Write session variables and close the session.
     * @uses PHP_MANUAL#session_write_close
     * @uses Session::isStarted To check whether a session has been started.
     * @return void
     * @throws LibraryException
     */
    public static function writeClose()
    {
        if(!self::isStarted()) {
            throw new LibraryException("A session has not been started", E_USER_ERROR);
        }
        session_write_close();
    }

    /**
     * Set a session as read only. This session cannot be writed to after it has been called.
     * @uses Session::readOnly
     * @return void
     */
    public static function setReadOnly()
    {
        self::$readOnly = true;
    }

    /**
     * Set a session as writable. Does the opposite of {@see Session::setReadOnly}.
     * @uses Session::readOnly
     * @return void
     */
    public static function unsetReadOnly()
    {
        self::$readOnly = false;
    }

    /**
     * Check whether a session is writable or not.
     * @see Session::setReadOnly
     * @see Session::unsetReadOnly
     * @uses Session::readOnly
     * @return void
     */
    public static function isWritable()
    {
        return !self::$readOnly;
    }

    /**
     * Check if a session exists. Since {@see Session::start} defines sessions to use only cookies,
     * it is then possible to check if the session's name has been set in {@see PHP_MANUAL#$_COOKIE}
     * or in {@see PHP_MANUAL#$_REQUEST}
     * @uses Session::getName
     * @return true|false
     */
    public static function sessionExists()
    {
        $name = self::getName();
        if (isset($_COOKIE[$name])) {
            return true;
        }
        if (isset($_REQUEST[$name])) {
            return true;
        }
        return false;
    }

    /**
     * Regenerate the session id and delete the old session.
     * @uses PHP_MANUAL#session_regenerate_id
     * @uses PHP_MANUAL#headers_sent To check whether any output has been sent to the browser.
     * @uses Session::isStarted To check whether a session has been started.
     * @return void
     * @throws LibraryException
     */
    public static function regenerateId()
    {
        if (!self::isStarted()) {
            throw new LibraryException("A session has not been started", E_USER_ERROR);
        }
        if (headers_sent($file, $line)) {
            throw new LibraryException("Headers have already been sent in file '{$file}' at line '{$line}'", E_USER_WARNING);
        }
        return session_regenerate_id(true);
    }

    /**
     * Encode a session into a string.
     * @uses PHP_MANUAL#session_encode
     * @uses Session::isStarted To check whether a session has been started.
     * @return string
     * @throws LibraryException
     */
    public static function encode()
    {
        if(!self::isStarted()) {
            throw new LibraryException("A session has not been started", E_USER_ERROR);
        }
        return session_encode();
    }

    /**
     * Decode a session from a string.
     * @uses Session::isStarted To check whether a session has been started.
     * @param string $sessionData
     * @return true|false
     * @throws LibraryException
     */
    public static function decode($sessionData)
    {
        if(!self::isStarted()) {
            throw new LibraryException("A session has not been started", E_USER_ERROR);
        }
        return session_decode($sessionData);
    }

    /**
     * Set the session's expiration time in seconds.
     * @uses Session::isStarted To check whether a session has been started.
     * @param int $time
     * @return void
     * @throws LibraryException
     */
    public static function expireIn($time = 0)
    {
        if(self::isStarted()) {
            throw new LibraryException("A session has already been started. Session::expireIn must be called before Session::start", E_USER_ERROR);
        }
        self::$defaultSessionCookieOptions['lifetime'] = (int) $time;
    }

    /**
     * Unset all the currently stored session variables. Behaves similarly to {@see PHP_MANUAL#session_unset}
     * but differs in that {@see PHP_MANUAL#session_unset} would only unset variables with respect to this
     * {@see Session} but this method unsets ALL session variables, including variables set in instances of
     * {@see Session_Namespace}.
     * @uses Session::isStarted To check whether a session has been started.
     * @return void
     * @throws LibraryException
     */
    public static function unsetAll()
    {
        if(!self::isStarted()) {
            throw new LibraryException("A session has not been started", E_USER_ERROR);
        }
        $_SESSION = array();
    }

    /**
     * Destroy a session.
     * @uses PHP_MANUAL#session_destroy
     * @uses Session::unsetAll
     * @uses Session::isStarted To check whether a session has been started.
     * @uses Session::getName
     * @uses Session_Cookie::getOptions To {@see PHP_MANUAL#get_session_cookie_params}.
     * @return true|false
     * @throws LibraryException If no session has been started or headers have already been sent.
     */
    public static function destroy()
    {
        if(!self::isStarted()) {
            throw new LibraryException("A session has not been started", E_USER_ERROR);
        }
        if (headers_sent($file, $line)) {
            throw new LibraryException("Headers have already been sent in file '{$file}' at line '{$line}'", E_COMPILE_ERROR);
        }
        Session::unsetAll();
        $params = Session_Cookie::getOptions();
        setcookie(Session::getName(), "", time() - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        return session_destroy();
    }
}