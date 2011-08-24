<?php
/**
 * Application.
 *
 * @author Joel Mukuthu
 * @copyright (c) 2010 Fiqne
 * @package Fiqne_MVC_Framework
 * @subpackage Application
 */
class Application
{
    /**
     * Holds the {@see Config} object.
     * @var Config|null
     */
    protected $config = null;

    /**
     * Holds the {@see Acl} object.
     * @var Acl|null
     */
    protected $acl = null;

    /**
     * Holds the {@see Request} object.
     * @var Request|null
     */
    protected $request = null;

    /**
     * Holds the {@see Router} object.
     * @var Router|null
     */
    protected $router = null;

    /**
     * Holds the {@see Dispatcher} object.
     * @var Dispatcher|null
     */
    protected $dispatcher = null;

    /**
     * Holds the {@see Response} object.
     * @var Response|null
     */
    protected $response = null;

    /**
     * Stores the names of the application's modules. These are automatically
     *  detected and can be added by creating a folder inside the 'application'
     *  folder.
     * @var array
     */
    protected $modules = array();

    /**
     * Holds an instance of {@see Application}.
     * @var Application|null
     */
    protected static $instance = null;

    /**
     * Get the single {@see Application} instance. This enforces singleton of the
     *  {@see Application} object.
     * 
     * @return Application
     */
    public static function getInstance()
    {
        if (!self::$instance instanceof Application) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Class constructor. Enforces singleton of any {@see Application} object by
     *  setting access to private.
     *
     * @return void
     */
    private function __construct()
    {
        $this->registerLibraryAutoloader();
    }

    /**
     * Prevents cloning of an Application instance.
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Run the application. This method does these things:
     *  -> Initiates page cache - NOT YET IMPLEMENTED!
     *  -> Configures the application using 'config.ini' options.
     *  -> Dispatches the user's request.
     * 
     * If an exception is caught it's saved to the {@link Registry} using the key 'exception'.
     * Thus this key ('exception') is reserved.
     *
     * @return void
     */
    public function run()
    {
        try {
            $this->getConfig()->run();
            $this->getDispatcher()->dispatch();
        } catch (LibraryException $e) {
            Registry::set('exception', $e);
            if ($e->getCode() === PAGE_NOT_FOUND) {
                $this->routeToError404();
            } else {
                $this->routeToError500();
            }
        }
    }

    /**
     * Set the application's modules. A module is any directory contained within
     *  the 'application' directory.
     *
     * @throws LibraryException
     *
     * @return void
     */
    protected function setModules()
    {
        $modulesDir = ROOT . DIRECTORY_SEPARATOR . 'application';
        if (!Loader::isSecure($modulesDir)) {
            throw new LibraryException("The modules directory name '{$modulesDir}' contains illegal characters", E_COMPILE_ERROR);
        }
        if (!is_readable($modulesDir)) {
            throw new LibraryException("Cannot access modules directory '{$modulesDir}'. It may not exist or is not readable", E_COMPILE_ERROR);
        }
        $handle = opendir($modulesDir);
        while (($file = readdir($handle)) !== false) {
            if ($file != "." && $file != "..") {
                $this->modules[] = $file;
            }
        }
    }

    /**
     * Get the application's modules.
     *
     * @return array
     */
    public function getModules()
    {
        if (!$this->modules) {
            $this->setModules();
        }
        return $this->modules;
    }

    /**
     * Get the {@link Request Request} object.
     *
     * @return Request
     */
    public function getRequest()
    {
        if(!$this->request) {
            $this->request = new Request();
        }
        return $this->request;
    }

    /**
     * Get the {@link Response Response} object.
     * 
     * @return Response
     */
    public function getResponse()
    {
        if(!$this->response) {
            $this->response = new Response();
        }
        return $this->response;
    }

    /**
     * Get the {@link Router Router} object.
     *
     * @return Router
     */
    public function getRouter()
    {
        if(!$this->router) {
            $this->router = new Router();
        }
        return $this->router;
    }

    /**
     * Get the {@link Acl Acl} object.
     * 
     * @return Acl
     */
    public function getAcl()
    {
        if(!$this->acl) {
            $this->acl = new Acl;
        }
        return $this->acl;
    }

    /**
     * Get the {@link Config Config} object.
     * @return Config
     */
    public function getConfig()
    {
        if(!$this->config) {
            $this->config = new Config();
        }
        return $this->config;
    }

    /**
     * Get the {@link Dispatcher Dispatcher} object.
     * @return Dispatcher
     */
    public function getDispatcher()
    {
        if(!$this->dispatcher) {
            $this->dispatcher = new Dispatcher();
        }
        return $this->dispatcher;
    }

    /**
     * Register the library class autoloader
     * 
     * @uses Loader::autoloader()
     *
     * @return true|false True if registration is successful, false otherwise
     */
    protected function registerLibraryAutoloader()
    {
        require 'Loader.php';
        return spl_autoload_register(array('Loader', 'autoloader'));
    }

    /**
     * Routes a request to the error404 action of the ErrorController. Therefore
     *  every module must have the ErrorController with an 'error404' method.
     * 
     * @return void
     */
    protected function routeToError404()
    {
        try {
            $this->getDispatcher()->setRoute(array('controller' => 'error', 'action' => 'error404'))->dispatch();
        } catch (LibraryException $e) {
            throw new LibraryException("There was an error routing to ErrorController::route404()", E_COMPILE_ERROR, $e);
        }
    }

    /**
     * Routes a request to the error500 action of the ErrorController. Therefore
     *  every module must have the ErrorController with an 'error500' method.
     * 
     * @return void
     */
    protected function routeToError500()
    {
        try {
            $this->getDispatcher()->setRoute(array('controller' => 'error', 'action' => 'error500'))->dispatch();
        } catch (LibraryException $e) {
            throw new LibraryException("There was an error routing to ErrorController::route500()", E_COMPILE_ERROR, $e);
        }
    }
}
