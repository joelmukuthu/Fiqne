<?php
/**
 * Router.
 *
 * Handles routes supplied via the end-user's request or the programmers routes.
 *
 * @author Joel Mukuthu
 * @copyright (c) 2010, Fiqne
 * @package Fiqne_MVC_Framework
 * @subpackage Router
 */
class Router
{
    /**
     * The route. Once a route is constructed, it consists of values of the following keys: 'module', 'controller', 'action', 'params'.
     * Route is constructed by {@see Router::setRoute} but a call to {@see Router::getRoute} will construct it if is not yet constructed.
     *
     * @var array
     */
    protected $route = array();

    /**
     * Temporary holds route values while constructing {@see Router::route}.
     *
     * @var array
     */
    protected $tempRoute = false;

    /**
     * Holds the module name for the current request. This is set for each request.
     *
     * @var string
     */
    protected $currentModule = "";

    /**
     * Construct a route that is used to dispatch a request. Before any user redirects that will use this method via {@see Dispatch::setRoute}
     * or {@see Controller::redispatch}, the application will have already set a default route passed via $_SERVER['REQUEST_URI']; thus all user
     * redirects that use {@see Dispatch::setRoute} or {@see Controller::redispatch} will not need to specify a module name.
     *
     * @uses Router::setCurrentModule To set the default module name for the current request.
     * @uses Router::setModule
     * @uses Router::setController
     * @uses Router::setAction
     * @uses Router::setParams
     * @uses Application::getModules To determine if the {@see Router::requestUri} contains a module name.
     *
     * @param array $route If not passed, this method attempts to construct a route for dispatch from the {@see Router::requestUri}.
     * If passed, it must contain values with the following keys: 'controller', 'action' and an optional 'params'.
     *
     * @throws LibraryException
     *
     * @return Router
     */
    public function setRoute($route = array())
    {
        if (!$route) {
            $tempRoute = $this->getTempRoute();
            if (!$tempRoute) {
                $this->setCurrentModule('pc')
                     ->setModule('pc')
                     ->setController('index')
                     ->setAction('index');
            } else {
                //if first index is a module name
                if (in_array($tempRoute[0], Application::getInstance()->getModules())) {
                    $this->setCurrentModule($tempRoute[0])
                         ->setModule()
                         ->setController()
                         ->setAction()
                         ->setParams();
                    //an it's a controller name, set default module as 'pc'
                } else {
                    $this->setCurrentModule('pc')
                         ->setModule('pc')
                         ->setController()
                         ->setAction()
                         ->setParams();
                }
            }
        } else {
            $keys = array_keys($route);
            $count = count($route);
            if ($count == 2) {
                if (!in_array('controller', $keys) && !in_array('action', $keys)) {
                    throw new LibraryException("The route array must contain the following keys: 'controller', 'action' and an optional 'params' keys", E_USER_ERROR);
                }
                $this->setModule($this->getCurrentModule())
                     ->setController($route['controller'])
                     ->setAction($route['action']);
            } else if ($count == 3) {
                if (!in_array('controller', $keys) && !in_array('action', $keys) && in_array('params', $keys)) {
                    throw new LibraryException("The route array must contain the following keys: 'controller', 'action' and an optional 'params' keys", E_USER_ERROR);
                }
                $this->setModule($this->getCurrentModule())
                     ->setController($route['controller'])
                     ->setAction($route['action'])
                     ->setParams($route['params']);
            } else {
                throw new LibraryException("The route array is not valid", E_USER_ERROR);
            }
        }
        return $this;
    }

    /**
     * Get a route.
     *
     * @uses Router::setRoute To construct a route if it's not constructed.
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
     * Set the default module name for the current request.
     *
     * @param string $module
     *
     * @return Router To allow method chaining $this->setCurrentModule('pc')->setModule()->setController()->setAction();
     */
    public function setCurrentModule($module)
    {
        $this->currentModule = (string) $module;
        return $this;
    }

    /**
     * Get the default module name for the current request.
     *
     * @return string
     */
    public function getCurrentModule()
    {
        return $this->currentModule;
    }

    /**
     * Set a route's module name.
     *
     * @uses Router::getTempRoute
     * @uses Router::shiftTempRoute
     * @uses Application::getModules To determine if the module name passed is a valid module name.
     *
     * @param string $module If not passed, this method will attempt to set a route's module name from the {@see Router::requestUri}.
     *
     * @return Router To allow method chaining $this->setModule()->setController()->setAction();
     */
    public function setModule($module = '')
    {
        if (!$module) {
            $tempRoute = $this->getTempRoute();
            if ($tempRoute) {
                $this->route['module'] = $tempRoute[0];
            } else {
                $this->route['module'] = 'pc';
            }
            $this->shiftTempRoute();
        } else {
            if (!in_array($module, Application::getInstance()->getModules())) {
                throw new LibraryException("Invalid module name '{$module}' specified", E_USER_ERROR);
            }
            $this->route['module'] = $module;
        }
        return $this;
    }

    /**
     * Get a route's module name.
     *
     * @uses Router::setRoute To construct a route if it's not constructed.
     *
     * @return string
     */
    public function getModule()
    {
        if (!$this->route) {
            $this->setRoute();
        }
        return $this->route['module'];
    }

    /**
     * Set a route's controller name.
     *
     * @uses Router::getTempRoute
     * @uses Router::shiftTempRoute
     *
     * @param string $controller If not passed, this method will attempt to set a route's controller name from the {@see Router::requestUri}.
     *
     * @return Router To allow method chaining $this->setController()->setAction();
     */
    public function setController($controller = '')
    {
        if (!$controller) {
            $tempRoute = $this->getTempRoute();
            if ($tempRoute) {
                $this->route['controller'] = $tempRoute[0];
            } else {
                $this->route['controller'] = 'index';
            }
            $this->shiftTempRoute();
        } else {
            $this->route['controller'] = $controller;
        }
        return $this;
    }

    /**
     * Get a route's controller name.
     *
     * @uses Router::setRoute To construct a route if it's not constructed.
     *
     * @return string
     */
    public function getController()
    {
        if (!$this->route) {
            $this->setRoute();
        }
        return $this->route['controller'];
    }

    /**
     * Set a route's action name.
     *
     * @uses Router::getTempRoute
     * @uses Router::shiftTempRoute
     *
     * @param string $action If not passed, this method will attempt to set a route's action name from the {@see Router::requestUri}.
     *
     * @return Router To allow method chaining $this->setAction()->setParams();
     */
    public function setAction($action = '')
    {
        if (!$action) {
            $tempRoute = $this->getTempRoute();
            if ($tempRoute) {
                $this->route['action'] = $tempRoute[0];
            } else {
                $this->route['action'] = 'index';
            }
            $this->shiftTempRoute();
        } else {
            $this->route['action'] = $action;
        }
        return $this;
    }

    /**
     * Get a route's action name.
     *
     * @uses Router::setRoute To construct a route if it's not constructed.
     *
     * @return string
     */
    public function getAction()
    {
        if (!$this->route) {
            $this->setRoute();
        }
        return $this->route['action'];
    }

    /**
     * Set a route's params.
     *
     * @uses Router::getTempRoute
     *
     * @param array $params If not passed, this method will attempt to set a route's params from the {@see Router::requestUri}.
     */
    public function setParams($params = array())
    {
        if (!$params) {
            $tempRoute = $this->getTempRoute();
            if ($tempRoute) {
                $this->route['params'] = $tempRoute;
            } else {
                $this->route['params'] = array();
            }
        } else {
            $this->route['params'] = $params;
        }
    }

    /**
     * Get a route's params.
     *
     * @uses Router::setRoute To construct a route if it's not constructed.
     *
     * @return array Of RAW params.
     */
    public function getParams()
    {
        if (!$this->route) {
            $this->setRoute();
        }
        return isset($this->route['params']) ? $this->route['params'] : false;
    }

    /**
     * Get a value passed via the $_SERVER['REQUEST_URI'], {@see Controller::redirect} or {@see Controller::redispatch}.
     *
     * Note that with {@see Controller::redispatch}, if no params have been passed as the third parameter, the old params
     * passed via the $_SERVER['REQUEST_URI'] will still exist. However this is not the case with {@see Controller::redirect}.
     *
     * Params passed via the $_SERVER['REQUEST_URI'], e.g. /param1/value are accessed like so getParam('param1');
     * Params passed via {@see Controller::redispatch} e.g. array('param2' => 'value2') are accessed like so getParam('param2');
     * Params passed via {@see Controller::redirect} e.g. array('param3' => 'value3') are accessed like so getParam('param3');
     *
     * @uses Router::getParams To get params supplied to the current request.
     *
     * @param string $key If not passed, this method returns sorted params (as oppposed to raw params). In the sorted array, 
     *
     * @return string|true|null|array 
     *         Returns true if the param was passed via $_SERVER['REQUEST_URI'] without a value,
     *         e.g. /param1, null if the param doesn't exist and the string value if it exists 
     *         and has a value.
     *  
     *         Note that if two (or more) params were passed (using whichever method) using the 
     *         same key e.g. /param/value/param/value2, only the first value is returned (with 
     *         this example 'value2' is returned).
     *  
     *         Returns an array if no key was passed. Note that if an array is returned, for any
     *         param that did not have a value, in the array returned it's value will be an empty
     *         string. And if there were two (or more) params with the same key, the FIRST param 
     *         key takes precedence and only its value will be in the array.
     *         E.g. if /param/value/param/value2, the array will contain a key 'param' with value 
     *         'value'.
     */
    public function getParam($key = '')
    {
        $params = $this->getParams();
        if (!$params) {
            return null;
        }
        if ($key) {
            if (!in_array((string) $key,$params)) {
                return null;
            }
            $paramKeys = array_keys($params, (string) $key, true);
            foreach ( $paramKeys as $paramKey ) {
                return isset($params[$paramKey + 1]) ? $params[$paramKey + 1] : true;
            }
        } else {
            $arr = array();
            $count = count($params);
            for ($i = 0; $i < $count; $i++) {
                //ensure first precedence params are not replaced by preceeding ones.
                $k = $params[$i];
                if ($i % 2 == 0 && !isset($arr[$k])) {
                	$arr[$k] = isset($params[$i + 1]) ? $params[$i + 1] : "";
                }
            }
            return $arr;
        }
    }

    /**
     * Clear a route's params.
     * 
     * @return Router
     */
    public function clearParams()
    {
        unset($this->route['params']);
        return $this;
    }

    /**
     * Clear a single param and it's value. If the are two (or more) params with the same key, both 
     * (and their values) will be cleared.
     * 
     * @param string $param
     * 
     * @return Router
     */
    public function clearParam($param)
    {
        if (isset($this->route['params'])) {
            if (in_array((string) $param, $this->route['params'])) {
                $keys = array_keys($this->route['params'], (string) $param, true);
                foreach ($keys as $key) {
                    //unset the occurrence param key
                    unset($this->route['params'][$key]);
                    //unset it's value if it exists
                    if (isset($this->route['params'][$key + 1])) {
                        unset($this->route['params'][$key + 1]);
                    }   
                }
            }
        }
        return $this;
    }

    /**
     * Set the temp route.
     *
     * @uses Router::getRequestUriStripped To get the request uri, stripped of extra slashes.
     *
     * @return Router To allow method chaining
     */
    protected function setTempRoute()
    {
        $requestUri = $this->getRequestUriStripped();
        if ($requestUri) {
            $this->tempRoute = explode('/', $requestUri);
        } else {
            $this->tempRoute = array();
        }
        return $this;
    }

    /**
     * Get the temp route.
     *
     * @uses Router::setTempRoute To set the temp route if it is not set.
     *
     * @return array
     */
    protected function getTempRoute()
    {
        if ($this->tempRoute === false) {
            $this->setTempRoute();
        }
        return $this->tempRoute;
    }

    /**
     * Shift the temp route array.
     *
     * @return Router To allow method chaining.
     */
    protected function shiftTempRoute()
    {
        array_shift($this->tempRoute);
        return $this;
    }

    /**
     * Strip extra slashes from the {@see Router::requestUri}. These are leading, trailing and any double or more slashes.
     * e.g. //mod/controller//action/ becomes mod/controller/action
     *
     * @uses Router::getRequestUri
     *
     * @return string
     */
    protected function getRequestUriStripped()
    {
        $requestUri = $_SERVER['REQUEST_URI'];
        //remove double or more slashes
        $requestUri = preg_replace('/\/{1,}/', '/', $requestUri);
        //remove leading and trailing slashes
        $requestUri = preg_replace('/^\/|\/$/', '', $requestUri);
        return $requestUri;
    }
}