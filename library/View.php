<?php
/**
 * View.
 *
 * @author Joel Mukuthu
 * @copyright (c) 2010 Fiqne
 * @package Fiqne_MVC_Framework
 * @subpackage View
 */
class View extends AutoMagic
{
    /**
     * Whether to render a view file or not.
     *
     * @var bool
     */
    protected $_render = true;

    /*
     * The view file.
     *
     * @var string
     */
    protected $_viewScript = "";

    /**
     * The layout file.
     *
     * @var string
     */
    protected $_layoutScript = "";

    /**
     * The view content.
     * @var string
     */
    protected $_content = "";

    /**
     * The {@see Router} object.
     *
     * @var Router|null
     */
    protected $_router = null;
    
    /**
     * Whether to render the leftpane or not.
     * 
     * @var string
     */
    protected $_renderLeftpane = true;
    
    /**
     * Whether to render the rightpane or not.
     * 
     * @var string
     */
    protected $_renderRightpane = true;

    /**
     * Class constructor. Initializes {@see View::layoutScript} and {@see View::viewScript}.
     *
     * @uses Application::getInstance To get an instance of the application (enforcing sinlgeton).
     * @uses Application::getRouter To get the router object.
     * @uses Router::getRoute To get the current route.
     */
    public function __construct()
    {
        $this->_router = Application::getInstance()->getRouter();
        $this->setLayoutScript()->setViewScript();
    }
    
    /**
     * Checks whether the view content (including layout) is set to be rendered or not and also sets the same.
     * 
     * @param true|false $spec
     * 
     * @return View|true|false If param $spec is passed, returns the {@link View view} object,
     *  else returns true or false depending on whether the view content is renderable or not.
     */   
    public function render($spec = null) 
    {
        if (is_null($spec)) {
            return $this->_render;
        } else {
            $this->_render = (bool) $spec;
            return $this;
        }
    }
    
    /**
     * Checks whether the sidepane is set to be rendered or not and also sets the same.
     * 
     * @param true|false $spec
     * 
     * @return View|true|false If param $spec is passed, returns the {@link View view} object,
     *  else returns true or false depending on whether the sidepane is renderable or not.
     */   
    public function renderLeftpane($spec = null) 
    {
        if (is_null($spec)) {
            return $this->_renderLeftpane;
        } else {
            $this->_renderLeftpane = (bool) $spec;
            return $this;
        }
    }
    
    /**
     * Checks whether the sidepane is set to be rendered or not and also sets the same.
     * 
     * @param true|false $spec
     * 
     * @return View|true|false If param $spec is passed, returns the {@link View view} object,
     *  else returns true or false depending on whether the sidepane is renderable or not.
     */   
    public function renderRightpane($spec = null) 
    {
        if (is_null($spec)) {
            return $this->_renderRightpane;
        } else {
            $this->_renderRightpane = (bool) $spec;
            return $this;
        }
    }

    /**
     * Set the view script to call for a request.
     *
     * @uses Router::getRoute To get the route of the current request.
     *
     * @param string $filename If not passed, this method attempts to call a view script for the request via
     * {@see Router::route}
     *
     * @return View
     */
    public function setViewScript( $filename = '' )
    {
        if ( !$filename ) {
            $route = $this->_router->getRoute();
            $filename = ROOT . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . $route['module'] . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $route['controller'] . DIRECTORY_SEPARATOR . $route['action'] . '.phtml';
        }
        if ( !Loader::isSecure( $filename ) ) {
            throw new LibraryException( "The view script filename '{$filename}' contains illegal characters", E_USER_ERROR );
        }
        if ( !is_readable( $filename ) ) {
            throw new LibraryException( "Cannot access view script '{$filename}'. It may not exist or is not readable", E_COMPILE_ERROR );
        }
        $this->_viewScript = $filename;
        return $this;
    }

    /**
     * Get the view script for the current request.
     *
     * @uses View::setViewScript
     *
     * @return string
     */
    public function getViewScript()
    {
        if ( !$this->_viewScript ) {
            $this->setViewScript();
        }
        return $this->_viewScript;
    }

    /**
     * Set the layout script to call for a request.
     *
     * @uses Router::getRoute To get the route of the current request.
     *
     * @param string $filename If not passed, this method attempts to call a layout script for the request via
     * {@see Router::route}, using the default view filename 'layout.phtml'.
     *
     * @return View
     */
    public function setLayoutScript( $filename = '' )
    {
        if ( !$filename ) {
            $route = $this->_router->getRoute();
            $filename = ROOT . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . $route['module'] . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'layout.phtml';
        }
        if ( !Loader::isSecure( $filename ) ) {
            throw new LibraryException( "The layout script filename '{$filename}' contains illegal characters", E_USER_ERROR );
        }
        if ( !is_readable( $filename ) ) {
            throw new LibraryException( "Cannot access layout script '{$filename}'. It may not exist or is not readable", E_COMPILE_ERROR );
        }
        $this->_layoutScript = $filename;
        return $this;
    }

    /**
     * Get the layout script for the current request.
     *
     * @uses View::setLayoutScript
     *
     * @return string
     */
    public function getLayoutScript()
    {
        if ( !$this->_layoutScript ) {
            $this->setLayoutScript();
        }
        return $this->_layoutScript;
    }

    /**
     * Get the content of the layout. Uses output buffering ({@see PHP_MANUAL#ob_start, PHP_MANUAL#ob_get_clean}).
     *
     * @uses View::getLayoutScript
     * @uses Loader::isSecure To validate that the layout script doesn't contain illegal characters before proceeding.
     *
     * @return string
     *
     * @throws LibraryException
     */
    public function getLayout()
    {
        ob_start();
        include $this->getLayoutScript();
        return ob_get_clean();
    }

    /**
     * Get the content of the view. Uses output buffering ({@see PHP_MANUAL#ob_start, PHP_MANUAL#ob_get_clean}).
     *
     * @uses View::getViewScript
     * @uses Loader::isSecure To validate that the view script doesn't contain illegal characters before proceeding.
     *
     * @return string
     *
     * @throws LibraryException
     */
    public function getView()
    {
        ob_start();
        include $this->getViewScript();
        return ob_get_clean();
    }

    /**
     * Set the view content to be echoed within the layout.
     *
     * @return $this;
     */
    public function setContent()
    {
        $this->_content = $this->getView();
        return $this;
    }

    /**
     * Get the view content. This method is used within the layout to get and echo content.
     *
     * @return string
     */
    public function getContent()
    {
        if (!$this->_content) {
        	$this->setContent();
        }
        return $this->_content;
    }

    /**
     * Get the URL from the supplied options.
     *
     * @uses Router::getRoute To get the route for the current request.
     *
     * @param array $options The options to use to construct a URL. Valid array keys that are considered are:
     * 		-> 'controller' => (string) the name of the controller that the URL will route to.
     * 		-> 'action' 	=> (string) the name of the action that the URL will route to.
     * 		-> 'module' 	=> (string) the name of the module that the URL will route to.
     * 		-> 'params' 	=> (array) array of params that will be passed via the URL.
     *
     * Note that these are all optional, since this parameter itself is optional. If not passed, this method will
     * attempt to construct a similar URL to the one currently being processed. This means that any key not passed
     * will be contructed using the current one, including params. Also note that when a new param value is passed
     * in the param array using the key of an exesting param, this new value replaces the existing one.
     *
     * If you don't wish an existing param to be passed along, use {@see Router::clearParam}.
     *
     * @return string Like "/module/controller/action/param1/value1/param2/value2/param3/".
     */
    public function url( $options = array() )
    {
        $route = $this->_router->getRoute();
        $location  = isset($options['module']) ? "/" . (string) $options['module'] : "";
        $location .= isset($options['controller']) ? "/" . (string) $options['controller'] : "/" . $route['controller'];
        $location .= isset($options['action']) ? "/" . (string) $options['action'] : "/" . $route['action'];
        $oldParamsUnsorted = isset($route['params']) ? $route['params'] : array();
        $oldParams = array();
        if ($oldParamsUnsorted) {
            $count = count($oldParamsUnsorted);
            for ($i = 0; $i < $count; $i++) {
                //ensure first precedence params are not replaced by preceeding ones.
                $k = $oldParamsUnsorted[$i];
                if ($i % 2 == 0 && !isset($oldParams[$k])) {
                	$oldParams[$k] = isset($oldParamsUnsorted[$i + 1]) ? $oldParamsUnsorted[$i + 1] : "";
                }
            }
        }
        $newParams = isset($options['params']) ? $options['params'] : array();
        //this will replace any old params (in $_SERVER['REQUEST_URI']) with new re-declared ones
        $params = array_merge($oldParams, $newParams);
        if ($params) {
            foreach ( $params as $key => $value ) {
                $location .= "/" . (string) $key . "/" . (string) $value;
            }
        }
        return $location;
    }

    /**
     * Escape special characters in a string.
     * @uses PHP_MANUAL#htmlspecialchars
     * @param string $string String to escape.
     * @return string The escaped string.
     */
    public function escape($string)
    {
        return htmlspecialchars( $string, ENT_QUOTES );
    }

    /**
     * Get currency-type representantion of a number.
     * 
     * @uses PHP_MANUAL#number_format
     * 
     * @param float|int $amount The number to format.
     * 
     * @return float The formatted number.
     */
    public function moneyFormat($amount)
    {
        return number_format((float) $amount, 2, '.', ',' );
    }
    
    /**
     * Appends a suffix to a number. Note that this method uses the HTML 'sup' element in order to
     * display the suffix in the correct superscript position.
     * 
     * @param string|int $num
     * 
     * @return string
     */
    public function appendSuffix($num) 
    {
        $num   = (string) $num;
        $last  = substr($num, strlen($num) - 1);
        switch ((int) $last) {
            case 1:
                $num .= '<sup>st</sup>';
                break;
            case 2:
                $num .= '<sup>nd</sup>';
                break;
            case 3:
                $num .= '<sup>rd</sup>';
                break;
            default:
                $num .= '<sup>th</sup>';
                break;
        }
        return $num;
    }
}