<?php
/**
 * Dispatcher.
 *
 * Handles dispatching of a request to the appropriate module/controller/action.
 *
 * @author Joel Mukuthu
 * @copyright (c) 2010 Fiqne
 * @package Fiqne_MVC_Framework
 * @subpackage Dispatcher
 */
class Dispatcher
{
    /**
     * The controller filename extension.
     *
     * @var string
     */
    protected $fileNameExtension = '.php';

    /**
     * The route of the current request.
     *
     * @var array
     */
    protected $route;

    /**
     * The dispatch object. This will be an instance of a user controller an thus {@see Controller}.
     *
     * @var resource|Controller
     */
    protected $dispatchObject;

    /**
     * Class constructor.
     */
    public function __construct ()
    {
        $this->route = array();
        $this->dispatchObject = false;
    }

    /**
     * Dispatch a request. Calls three methods, {@see Controller::initialize}, {@see Controller::render} and the appropriate action.
     * This method does not create a new dispatch object to cater for re-dispatches via {@see Controller::redispatch} which would
     * not require initializing a new dispatch object if the needed one already exists.
     *
     * @uses Dispatcher::getDispatchObject To get the dispatch object.
     * @uses Dispatcher::getActonName To get the action to dispatch.
     * @uses PHP_MANUAL#call_user_func To call {@see Controller::initialize}, the appropriate action and {@see Controller::render}.
     *
     * @throws LibraryException
     *
     * @return void
     */
    public function dispatch()
    {
        $dispatch = $this->getDispatchObject();
        $controllerName = $this->getControllerName();
        $action = $this->getActionName();
        if (!is_callable(array($dispatch, $action))) {
            throw new LibraryException("The action '{$controllerName}::{$action}()' does not exist", PAGE_NOT_FOUND);
        }
        try {
            call_user_func(array($dispatch, 'initialize'));
            call_user_func(array($dispatch, $action));
            call_user_func(array($dispatch, 'render'));
        } catch(Exception $e) {
            throw new LibraryException("The dispatch could not be completed", E_COMPILE_ERROR, $e);
        }
    }

    /**
     * Set a route to dispatch. This would usually be called before dispatching a new request, for instance as applied by
     * {@see Controller::redispatch}.
     *
     * @uses Application::getInstance To get an instance of {@see Application}.
     * @uses Application::getRouter To get the router object.
     * @uses Router::getRoute To get the route of the current request, if no route has been supplied to route to.
     * @uses Router::setRoute To set the new route if passed to the method.
     *
     * @param array $route Must contain the keys 'controller', 'action' and an optional 'params'. If not passed, this method uses
     * {@see Router::route}.
     *
     * @return Dispatcher
     */
    public function setRoute($route = array())
    {
        $app = Application::getInstance();
        if (!$route) {
            $this->route = $app->getRouter()->getRoute();
        } else {
            $this->route = $app->getRouter()->setRoute($route)->getRoute();
        }
        return $this;
    }

    /**
     * Get the route currently being dispatched.
     *
     * @uses Dispatcher::setRoute if no route has been set at the time of calling this method.
     *
     * @return array
     */
    public function getRoute()
    {
        if (!$this->route) {
            $this->setRoute();
        }
        return $this->route;
    }

    /**
     * Get the controller name of the current route being dispatched.
     *
     * @return string
     */
    protected function getControllerName()
    {
        $route = $this->getRoute();
        return $this->formatControllerName($route['controller']);
    }

    /**
     * Get the action name of the current route being dispatched.
     *
     * @return string
     */
    protected function getActionName()
    {
        $route = $this->getRoute();
        return $this->formatActionName($route['action']);
    }

    /**
     * Get the module name of the current route being dispatched.
     *
     * @return string
     */
    protected function getModuleName()
    {
        $route = $this->getRoute();
        return $route['module'];
    }

    /**
     * Set a dispatch object for the route being dispatched. This would be an instance of a user controller.
     *
     * @uses Dispatcher::getControllerName To get the name of the controller.
     * @uses Loader::isSecure To check that the controller filename contain illegal characters.
     * @uses PHP_MANUAL#file_exists To check whether the controller exists.
     * @uses PHP_MANUAL#is_readable To check whether the cotroller file is readable.
     * @uses PHP_MANUAL#include To call the controller file.
     *
     * @throws LibraryException
     *
     * @return void
     */
    protected function setDispatchObject()
    {
        $controller = $this->getControllerName();
        $filename = ROOT
        . DIRECTORY_SEPARATOR
        . 'application'
        . DIRECTORY_SEPARATOR
        . $this->getModuleName()
        . DIRECTORY_SEPARATOR
        . 'controllers'
        . DIRECTORY_SEPARATOR
        . $controller
        . $this->getFileNameExtension();
        if (!Loader::isSecure($filename)) {
            throw new LibraryException("The controller filename '{$filename}' contains illegal characters", PAGE_NOT_FOUND);
        }
        if (!is_readable($filename)) {
            throw new LibraryException("Cannot access controller file '{$filename}'. It may not exist or is not readable.", PAGE_NOT_FOUND);
        }
        include $filename;
        $this->dispatchObject = new $controller;
    }

    /**
     * Get the dispatch object for the current dispatch.
     *
     * @uses Dispatcher::getDispatchObject To create a dispatch object if none exists at the time of calling this method. If
     *       one exists, it will check to see if it is for the current controller.
     *
     * @return Controller In actual sense the return type is a class that extends {@link Controller}, i.e. a user controller.
     */
    protected function getDispatchObject()
    {
        if (!$this->dispatchObject) {
            $this->setDispatchObject();
        } elseif (get_class($this->dispatchObject) !== $this->getControllerName()) {
            $this->setDispatchObject();
        }
        return $this->dispatchObject;
    }

    /**
     * Set the controller file extension to use in calling the controller file.
     *
     * @uses Dispatcher::fileNameExtension
     *
     * @param string $ext Must start with the dot(.) e.g. '.php' or the method throws an exception.
     *
     * @throws LibraryException
     *
     * @return Dispatcher
     */
    public function setFileNameExtension($ext)
    {
        if (substr($ext, 0, 1) !== '.') {
            throw new LibraryException("The filename extension provided '{$ext}' is not valid", E_USER_ERROR);
        }
        $this->fileNameExtension = $ext;
        return $this;
    }

    /**
     * Get the controller file extension used to call the controller file.
     *
     * @return string
     */
    public function getFileNameExtension()
    {
        return $this->fileNameExtension;
    }

    /**
     * Format the supplied controller name via {@see Dispatcher::route} to one that can be mapped onto to a
     * valid controller file/class.
     *
     * @param string $unformattedName
     *
     * @throws LibraryException Page not found exception, if the supplied controller name is not valid as
     * a feature for handling user errors gracefully.
     *
     * @return string Formatted controller name.
     */
    protected function formatControllerName($unformattedName)
    {
        $pos = strpos($unformattedName, "-");
        $formatted = "";
        if ($pos === 0 || $pos === strlen($unformattedName) - 1) {
            /*
             * Instead of throwing an E_USER_ERROR, handle the error gracefully since it could be an eror by the end-user.
             *
             * throw new LibraryException("Invalid controller specified", E_USER_ERROR);
             */
            throw new LibraryException("The requested controller does not exist", PAGE_NOT_FOUND);
        } else if ($pos > 0) {
            $formatted = str_replace(" ", "", ucwords(str_replace("-", " ", $unformattedName)));
        } else {
            $formatted = ucfirst($unformattedName);
        }
        return $formatted . "Controller";
    }

    /**
     * Format the supplied action name via {@see Dispatcher::route} to one that can be mapped onto to a
     * valid controller file/class.
     *
     * @param string $unformattedName
     *
     * @throws LibraryException Page not found exception, if the supplied action name is not valid as
     * a feature for handling user errors gracefully.
     *
     * @return string Formatted action name.
     */
    protected function formatActionName($unformattedName)
    {
        $pos = strpos($unformattedName, "-");
        $formatted = "";
        if ($pos === 0 || $pos === strlen($unformattedName) - 1) {
            /*
             * Instead of throwing an E_USER_ERROR, handle the error gracefully since it could be an eror by the end-user.
             *
             * throw new LibraryException("Invalid controller specified", E_USER_ERROR);
             */
            throw new LibraryException("The requested action does not exist", PAGE_NOT_FOUND);
        } else if ($pos > 0) {
            $unformattedName = str_replace("-", " ", $unformattedName);
            //If PHP >= 5.3
            if (strnatcmp(phpversion(), '5.3') >= 0) {
                $formatted = lcfirst(str_replace(" ", "", ucwords($unformattedName)));
            } else {
                $formatted = str_replace(" ", "", ucwords($unformattedName));
                $formatted = strtolower(substr($formatted, 0, 1)) . substr($formatted, 1);
            }
        } else {
            $formatted = $unformattedName;
        }
        return $formatted;
    }
}