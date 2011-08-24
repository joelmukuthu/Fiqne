<?php
/**
 * Controller.
 *
 * This is the base controller class that must be extended by all user controllers.
 * Provides functionality (methods) that is common to and needed by user controllers.
 *
 * @author Joel Mukuthu
 * @copyright (c) 2010 Fiqne
 * @package Fiqne_MVC_Framework
 * @subpackage Controller
 */
abstract class Controller extends AutoMagic
{
    /**
     *
     * @var View
     */
    protected $view = null;

    /**
     * Stores an instance of {@see Request}.
     * @var Request|null
     */
    protected $request = null;

    /**
     * Stores an instance of {@see Response}.
     * @var Response|null
     */
    protected $response = null;

    /**
     * Stores an instance of {@see Router}.
     * @var Router|null
     */
    protected $router = null;

    /**
     * Stores an instance of {@see Application}.
     * @var Application|null
     */
    protected $application = null;

    /**
     * Stores an instance of {@see Dispatcher}.
     * @var Dispatcher|null
     */
    protected $dispatcher = null;

    /**
     * Stores an instance of {@see Config}.
     * @var Config|null
     */
    protected $config = null;

    /**
     * Class constructor.
     *
     * @uses Application::getInstance To get the singleton instance of the application.
     * @uses Application::getRequest
     * @uses Application::getRouter
     * @uses Application::getResponse
     * @uses Application::getDispatcher
     * @uses Application::getConfig
     * @uses Controller::configureModelLoader To configure PHP's include path.
     * @uses Controller::init To call the init() function of child/user controllers
     */
    public function __construct()
    {
        $app = Application::getInstance();
        $this->application = $app;
        $this->router      = $app->getRouter();
        $this->request     = $app->getRequest();
        $this->response    = $app->getResponse();
        $this->dispatcher  = $app->getDispatcher();
        $this->config      = $app->getConfig();
        $this->configureModelLoader();
    }

    /**
     * Initialize the view object.
     *
     * This is not done when initializing the constructor since the view script has to be reset if the request is a {@see Controller::redispatch}.
     *
     * @uses View::__construct To construct the view object.
     *
     * @return void
     */
    public function initialize()
    {
        $this->view = new View();
        $this->init();
    }

    /**
     * Configure PHP's include path so that user controllers can have an easy time initializing their models.
     *
     * @uses PHP_MANUAL#set_include_path
     * @uses PHP_MANUAL#get_include_path
     *
     * @return Controller
     */
    private function configureModelLoader()
    {
        $route = $this->router->getRoute();
        $modelsPath = ROOT . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . $route['module'] . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR;
        set_include_path( implode( PATH_SEPARATOR, array(
            $modelsPath , get_include_path()
        ) ) );
        return $this;
    }

    /**
     * The purpose of this function is so that child/user controllers that don't have an init() function don't cause PHP to
     * raise an error when the constructor is initialized.
     *
     * @return void
     */
    protected function init()
    {
    }

    /**
     * Render the view. This method is called by {@link Dispatcher::dispatch()} as the last call of the 
     *  dispatch routine. This method allows several options for rendering a request:
     * 
     *    1. The normal rendering routine: rendering depending on whether {@link View::_render} is 
     *       true or false.
     * 
     *    2. For XHR requests only the view content is sent (done automatically) unless 
     *       {@link View::_render} is set to false.
     *  
     *       This behaviour can be changed by unsetting $_SERVER['HTTP_X_REQUESTED_WITH'] in the action.
     *  
     *       As a fail safety feature, this option also checks to see if a {@link Router::params} param 
     *       with key 'xhr' or a {@link Request} param (GET, POST, SERVER or ENV) with key 'xhr' has been 
     *       sent along with the request. This is due to a case in IE where the X-Requested-With header is 
     *       not sent for *some* requests (don't know why). 
     * 
     *       Thus for such requests, the key 'xhr' is reserved and should always be sent along with the 
     *       request either via $_SERVER['REQUEST_URI'] or GET, POST, SERVER or ENV. It also means that 
     *       to avoid the rendering only of view content for XHR requests, save for unsetting
     *       $_SERVER['HTTP_X_REQUESTED_WITH'], you must also unset 'xhr' in {@link Router::params} with
     *       {@link Router::clearParam()} and/or in {@link Request} using {@link Request::__unset}.
     * 
     *       Also note that with this option, this method explicitly sets the HTTP response code for the
     *       request to '200' (using {@link Response::setResponseCode()}) so that for '404' and '500' 
     *       errors that are generated by {@link Dispatcher::dispatch()} the views for 
     *       {@link ErrorController::error404()} and {@link ErrorController::error500()} are sent back to
     *       the browser. This is because whenever and error occurs for an XHR request, no html is returned. 
     * 
     *   3. To send the view content only, set {@link View::_render} to false and add the key 
     *      'renderViewOnly' (with whatever value) to the {@link Registry} using {@link Registry::set()}.
     * 
     *   4. To send headers only, set {@link View::_render} to false and add the key 
     *      'sendHeadersOnly' (with whatever value) to the {@link Registry} using {@link Registry::set()}.
     * 
     *      This option will also look for the key 'responseCode' within the {@link Registry} to add to 
     *      the response headers. If it's not set, the default 200 is sent. 
     * 
     * @todo Static chaching for XHR requests. These requests will render the view content only so why not 
     *       cache them (where applicable) as static HTML files e.g in pagination?
     *  
     *       For these requests, when caching is implemented, the key 'no-cache' will be reserved in 
     *       {@link Router::params} and in {@link Request}. 
     *
     * @uses View::getLayout
     * @uses View::getView
     * @uses Router::getParam and {@link Request::get()}} to look for key 'xhr' for option 2.
     * @uses Response::setOutput
     * @uses Response::sendOutput
     * @uses Response::sendHeaders for option 4.
     * @uses Registry::exists for options 3 and 4.
     * @uses Registry::get to get the HTTP response code to send for option 4.
     *
     * @return void
     */
    //TODO:Static caching for XHR requests
    public function render()
    {
        $render = $this->view->render();
        if ($this->view->render()) {
            //First see if it's XHR
            if ($this->request->isXhr() || $this->router->getParam('xhr') || $this->request->get('xhr')) {
                $this->response->setOutput($this->view->getView())
                               ->setResponseCode(200)
                               ->sendOutput();
                return;
            }
            //Normal request
            $this->response->setOutput($this->view->getLayout())
                           ->sendOutput();
            return;
        } else {
            if (Registry::exists('renderViewOnly')) {
                $this->response->setOutput($this->view->getView())
                               ->sendOutput();
                return;
            }
            if (Registry::exists('sendHeadersOnly')) {
                $responseCode = Registry::exists('responseCode') ? Registry::get('responseCode') : 200;
                $this->response->addHeader('HTTP/1.1 ' . $responseCode)
                               ->sendHeaders();
                return;
            }   
        }
    }

    /**
     * Redispatch a request to another controller/action with new params. This method *may* be faster 
     * than {@link Controller::redirect()} as it's not a HTTP redirect (subject to testing).
     *
     * @uses Dispatcher::setRoute To set the route for the new dispatch.
     * @uses Dispatcher::dispatch To dispatch the new request.
     * @uses View::render To disable rendering the view for the current request.
     *
     * @param string $action
     * @param string $controller
     * @param array $params
     * @param bool $renderCurrent Whether to render the view (including layout) for the current request
     *        or not.
     *  
     *        This is useful where you want to perform some action after output has already been sent
     *        to the browser, in which case you would set this parameter to 'true' and pass 'false' to 
     *        {@link View::render()} in the action that you re-dispatch to.
     *
     * @return void
     */
    protected function redispatch($action, $controller, $params = array(), $renderCurrent = false)
    {
        $route = array(
            'controller' => (string) $controller , 'action' => (string) $action
        );
        if ($params) {
            $arr = array();
            foreach ($params as $key => $value) {
                $arr[] = (string) $key;
                $arr[] = (string) $value;
            }
            $route['params'] = $arr;
        }
        $this->dispatcher->setRoute($route)
                         ->dispatch();
        $this->view->render((bool) $renderCurrent);
    }

    /**
     * Redirect a request to a new module/controller/action with new params. Once the current request is redirected, a new
     * request now exists.
     *
     * @uses PHP_MANUAL#header To do the redirect.
     * @uses PHP_MANUAL#exit To exit execution of the current request.
     *
     * @param string $action
     * @param string $controller
     * @param string $module
     * @param array $params
     * @param bool $exit Whether to {@link PHP_MANUAL#exit exit()} after redirect or not.
     *
     * @return void
     */
    protected function redirect( $action , $controller , $module = '' , $params = array(), $exit = true )
    {
        if ( $module ) {
            $location = "/" . (string) $module;
        } else {
            $location = "";
        }
        $location .= "/{$controller}/{$action}";
        if ( $params ) {
            foreach ( $params as $key => $value ) {
                $location .= "/{$key}/{$value}";
            }
        }
        $location = isset( $_SERVER['HTTPS'] ) ? 'https://' : 'http://' . $_SERVER['HTTP_HOST'] . $location;
        header( "Location: " . $location );
        if ($exit) {
            exit();
        }
    }

    /**
     * Handle exception. This method is used in the ErrorController to handle exceptions caught
     * in the application dispatch.
     *
     * @uses Zend_Mail
     *
     * @return void
     */
    protected function handleException()
    {
        if ( !Registry::exists( 'exception' ) ) {
            return;
        }
        $exception = Registry::get( 'exception' );
        if ($exception instanceof Exception) {
            if ($exception->getCode() === PAGE_NOT_FOUND) {
                $this->response->setResponseCode(404);
            } else {
                $this->response->setResponseCode(500);
            }
            try {
                $configs = $this->config->getConfigs();
            } catch (LibraryException $e) {
                throw new LibraryException("Could not get configs. Config file may not exist or is not readable", E_COMPILE_ERROR, $e);
            }
            $env = $configs['application']['environment'];
            if ($env == "development") {
                $this->view->exception = $exception;
            } elseif ($env == "production") {
                //if it's a 404 do nothing
                if ($exception->getCode() === PAGE_NOT_FOUND) {
                    return;
                }
                $errorLog = $configs['application']['error_log'];
                $exceptionLog = $configs['application']['exception_log'];
                ini_set('error_log', $exceptionLog);
                $prev = $exception->getPrevious();
                if ($prev) {
                    $ex = "\nFirst Exception [{$prev->getCode()}]: {$prev->getMessage()}. Trace:\n{$prev->getTraceAsString()}"
                        . "\nNext Exception [{$exception->getCode()}]: {$exception->getMessage()}. Trace:\n{$exception->getTraceAsString()}\n";
                } else {
                    $ex = "\nException [{$exception->getCode()}]: {$exception->getMessage()}. Trace:\n{$exception->getTraceAsString()}\n";
                }
                error_log($ex, 0);
                $sendmail = false;
                $getExceptionLogContent = false;
                $date = date('jS M Y');
                $time = date('g:i a');
                if (file_exists($exceptionLog . '.info.txt')) {
                    $lastLogTime = intval( file_get_contents( $exceptionLog . '.info.txt' ) );
                    if ($lastLogTime + $configs['application']['exception_mailing_delay'] <= time()) {
                        $sendmail = true;
                        $getExceptionLogContent = true;
                    }
                } else {
                    $sendmail = true;
                }
                if ($sendmail) {
                    //construct the email body
                    ob_start();
                    echo '
                    <html>
                    <head>
                    <title>Exception Caught</title>
                    <style type="text/css">
                    html,body{margin:0;padding:0;}
                    body{font-family:Futura,\'Lucida Grande\',\'Bitstream Vera Sans\',Tahoma,\'Trebuchet MS\',Verdana,Arial,sans-serif;font-size:12px;line-height:20px;color:#222;background-color:#ecf7ef;}
                    .echo{clear:both;border:1px #c9c7c7 solid;padding:5px;}
                    .echo ul{list-style-type: none;}
                    .echo h1{background-color:#c9c7c7;font-size:18px;margin:-5px;padding:5px;}
                    </style>
                    </head>
                    <body>
                    <h1>Exception Caught!</h1>
                    <p>An exception was caught and saved to the exception log at ' . $time . ' on '. $date . ':</p>';
                    //we'll use our good ol' Util::e()
                	if ($prev) {
                        Util::e($prev->getMessage(), "First Exception [{$prev->getCode()}]");
                        Util::e($prev->getTrace(), "Trace");
                        Util::e($exception->getMessage(), "Next Exception [{$exception->getCode()}]");
                        Util::e($exception->getTrace(), "Trace");
                	} else {
                        Util::e($exception->getMessage(), "Exception [{$exception->getCode()}]");
                        Util::e($exception->getTrace(), "Trace");
                	} 
                    if ($getExceptionLogContent) {
                        echo '<p>These are the current contents of the exception log:</p>'; 
                        Util::e(file_get_contents($exceptionLog), 'Exception Log');
                    }
                    echo '
                    </body>
                    </html>';
                    $body = ob_get_clean();
                    try {
                        $transport = new Zend_Mail_Transport_Smtp($configs['mail']['host'], array(
                            'auth'     => 'login',
                            'username' => $configs['mail']['username'],
                            'password' => $configs['mail']['password']
                        ));
                        $mail = new Zend_Mail();
                        $mail->setFrom($configs['application']['from_email'], 'System Autogenerated')
                             ->setSubject('Exception Caught!')
                             ->addTo($configs['application']['to_email'], 'Fiqne Support')
                             ->setBodyText($body)
                             ->send($transport);
                    } catch (Exception $e) {
                        error_log("\nException (Caught while attempting to email Fiqne Support) [{$e->getCode()}]: {$e->getMessage()}Trace:\n{$e->getTraceAsString()}\n", 0);
                    }
                    file_put_contents($exceptionLog . '.info.txt', time());
                }
                ini_set('error_log', $errorLog);
            }
        }
    }
}