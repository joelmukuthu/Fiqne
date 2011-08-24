<?php
/**
 * Handles session namespace manipulation. A session namespace named 'namespace'
 * corresponds to $_SESSION['namespace'], but in actual sense is $_SESSION['__FIQNE__']['namespace']
 *
 * @author Joel Mukuthu
 * @copyright (c) 2010, Fiqne
 * @package Fiqne_MVC_Framework
 * @subpackage Session
 */
class Session_Namespace
{
    /**
     * The name of an instance of {@see Session_Namespace}.
     * @var string
     */
    protected $name;

    /**
     * Whether an instance of {@see Session_Namespace} is writable or not.
     * @var bool
     */
    protected $readOnly = false;

    /**
     * Construct an instance of {@see Session_Namespace} with the provided $name. If a name is not
     * provided, it defaults to 'app'.
     * @uses Session::start To start a session.
     * @uses Session::keyExists To check if the user is resuming a session namespace.
     * @uses Session::set To save a storage space for this instance of {@see Session_Namespace}.
     * @param string $name
     */
    public function __construct ($name = 'app')
    {
        $this->name = $name;
        Session::start();
        if (!Session::keyExists($name)) {
            Session::set($name, array());
        }
    }

    /**
     * Save a value for an instance of {@see Session_Namespace}. This magic method allows setting values
     * like so $namespace->key = 'value';
     * @uses Session_Namespace::isWritable
     * @param string $key Using integers for keys is discouraged as the user will have to keep track
     * of assigned keys to avoid overwriting already defined keys.
     * @param mixed $value
     * @return void
     * @throws LibraryException
     */
    public function __set($key, $value)
    {
        if (!$this->isWritable()) {
            throw new LibraryException("Writing to the current session or session namespace '{$this->name}' has been disabled", E_USER_ERROR);
        }
        $_SESSION[Session::getBaseKey()][$this->name][(string) $key] = $value;
    }

    /**
     * Get a stored session namespcae value. Allows getting values like so $value = $namespace->key;
     * @see Session_Namespace::__set
     * @param string $key
     * @return mixed
     * @throws LibraryException
     */
    public function __get($key)
    {
        if (!isset($_SESSION[Session::getBaseKey()][$this->name][$key])) {
            throw new LibraryException("The key '{$key}' has not been set for this namespace '{$this->name}'", E_USER_ERROR);
        }
        return $_SESSION[Session::getBaseKey()][$this->name][$key];
    }

    /**
     * Check whether $key has been set for an instance of {@see Session_Namespace}. Allows checking like so
     * isset($namespace->key).
     * @param string $key
     * @return true|false
     */
    public function __isset($key)
    {
        return isset($_SESSION[Session::getBaseKey()][$this->name][$key]);
    }

    /**
     * Unset a value set at $key. Allows unsetting a value like so unset($namespace->key)
     * @see Session_Namespace::__set
     * @see Session_Namespace::__isset
     * @param string $key
     * @return void
     */
    public function __unset($key)
    {
        if (isset($_SESSION[Session::getBaseKey()][$this->name][$key])) {
            unset($_SESSION[Session::getBaseKey()][$this->name][$key]);
        }
    }

    /**
     * Destroy all values stored for an instance of {@see Session_Namespace}.
     * @uses Session::unsetKey
     * @return void
     */
    public function destroy()
    {
        Session::unsetKey($this->name);
    }

    /**
     * Get the name of an instance of {@see Session_Namespace}.
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set a new name for an instance of {@see Session_Namespace}.
     * @uses Session::get To get current saved values for the namespace before setting new name.
     * @uses Session_Namespace::destroy To destroy currently saved values for the old namespace
     * @uses Session::set To reset saved values for the old namespace to the new namespace.
     * @param string $newName
     * @return Session_Namespace To allow method chaining
     */
    public function setName($newName)
    {
        //get the current namespace storage object
        $temp = Session::get($this->name);
        //destroy current
        $this->destroy();
        //reset
        Session::set($newName, $temp);
        $this->name = $newName;
        return $this;
    }

    /**
     * Check whether an instance of {@see Session_Namespace} is writable or not.
     * @uses Session::isWritable
     * @uses Session_Namespace::readOnly
     * @return true|false
     */
    public function isWritable()
    {
        if (!Session::isWritable()) {
            return false;
        }
        if ($this->readOnly) {
            return false;
        }
        return true;
    }

    /**
     * Set an instance of {@see Session_Namespace} as read only.
     * @uses Session_Namespace::readOnly
     * @return Session_Namespace To allow method chaining
     */
    public function setReadOnly()
    {
        $this->readOnly = true;
        return $this;
    }

    /**
     * Set an instance of {@see Session_Namespace} as writable. Does the opposite of {@see Session_Namespace::setReadOnly}
     * @uses Session_Namespace::readOnly
     * @return Session_Namespace To allow method chaining
     */
    public function unsetReadOnly()
    {
        $this->readOnly = false;
        return $this;
    }
}