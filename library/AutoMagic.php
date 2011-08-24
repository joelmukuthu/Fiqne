<?php
/**
 * AutoMagic.
 *
 * @author Joel Mukuthu
 * @copyright (c) 2010 Fiqne
 * @package Fiqne_MVC_Framework
 * @subpackage Application
 */
class AutoMagic
{
    /**
     * Holds variables manipulated using the automagic methods {@link AutoMagic::__set()}, {@link AutoMagic::__get()},
     *  {@link AutoMagic::__isset()} and {@link AutoMagic::__unset()}.
     *
     * @var array
     */
    private $_vars = array();
    
    /**
     * Set a variable.
     *
     * @uses AutoMagic::_vars
     *
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    public function __set($key, $value)
    {
        $this->_vars[(string) $key] = $value;
    }

    /**
     * Get a variable.
     *
     * @uses AutoMagic::_vars
     *
     * @param string $key
     *
     * @return mixed. Returns false if the variable is not set.
     */
    public function __get($key)
    {
        return isset($this->_vars[(string) $key]) ? $this->_vars[(string) $key] : false;
    }

    /**
     * Check whether a variable has been set.
     * 
     * @uses AutoMagic::_vars
     *
     * @param string $key
     *
     * @return true|false
     */
    public function __isset($key)
    {
        return isset($this->_vars[$key]);
    }

    /**
     * Unset a variable.
     *
     * @uses AutoMagic::_vars
     * 
     * @param string $key
     *
     * @return void
     */
    public function __unset($key)
    {
        unset($this->_vars[$key]);
    } 
    
    /**
     * Unset all variables.
     *
     * @uses AutoMagic::_vars
     * 
     * @return void
     */
    public function unsetAll()
    {
        $this->_vars = array();
    } 
    
    /**
     * Get all variables.
     *
     * @uses AutoMagic::_vars
     * 
     * @return void
     */
    public function getAll()
    {
        return $this->_vars;
    }
}