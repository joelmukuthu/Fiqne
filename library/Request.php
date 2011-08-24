<?php
/**
 * @author Joel Mukuthu
 * @copyright (c) 2010, Fiqne
 * @package Fiqne_MVC_Framework
 * @subpackage Request
 */
class Request
{
    public function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function isGet()
    {
        return 'GET' == $_SERVER['REQUEST_METHOD'] ? true : false;
    }

    public function isPost()
    {
        return 'POST' == $_SERVER['REQUEST_METHOD'] ? true : false;
    }

    public function isPut()
    {
        return 'PUT' == $_SERVER['REQUEST_METHOD'] ? true : false;
    }

    public function isDelete()
    {
        return 'DELETE' == $_SERVER['REQUEST_METHOD'] ? true : false;
    }

    public function isHead()
    {
        return 'HEAD' == $_SERVER['REQUEST_METHOD'] ? true : false;
    }
        
    /**
     * Is the request a Javascript XmlHttpRequest (AJAX) request? This will work with jQuery.
     * 
     * NOTE: This method has been borrowed from Zend Framework.
     * 
     * @return true|false
     */  
    public function isXhr()
    {
        return ($this->getHeader('X_REQUESTED_WITH') == 'XMLHttpRequest');
    }

    /**
     * Is this a Flash request?
     * 
     * NOTE: This method has been borrowed from Zend Framework.
     *
     * @return true|false
     */
    public function isFlash()
    {
        $header = strtolower($this->getHeader('USER_AGENT'));
        return (strstr($header, ' flash')) ? true : false;
    }
    
    /**
     * Return the value of the given HTTP header. Pass the header name as the
     * plain, HTTP-specified header name. Ex.: Ask for 'Accept' to get the
     * Accept header, 'Accept-Encoding' to get the Accept-Encoding header.
     * 
     * NOTE: This method has been borrowed from Zend Framework.
     *
     * @param string $header HTTP header name
     * @return string|false HTTP header value, or false if not found
     */
    public function getHeader($header)
    {
        // Try to get it from the $_SERVER array first
        $temp = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
        if (!empty($_SERVER[$temp])) {
            return $_SERVER[$temp];
        }

        // This seems to be the only way to get the Authorization header on
        // Apache
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (!empty($headers[$header])) {
                return $headers[$header];
            }
        }

        return false;
    }

    public function __get( $key )
    {
        switch ( true ) {
            case isset( $_GET[$key] ):
                return $_GET[$key];
            case isset( $_POST[$key] ):
                return $_POST[$key];
            case isset( $_SERVER[$key] ):
                return $_SERVER[$key];
            case isset( $_ENV[$key] ):
                return $_ENV[$key];
            default:
                return false;
        }
    }

    public function get( $key )
    {
        switch ( true ) {
            case isset( $_GET[$key] ):
                return $_GET[$key];
            case isset( $_POST[$key] ):
                return $_POST[$key];
            case isset( $_SERVER[$key] ):
                return $_SERVER[$key];
            case isset( $_ENV[$key] ):
                return $_ENV[$key];
            default:
                return false;
        }
    }

    public function getServer( $key )
    {
        if ( !isset( $_SERVER[$key] ) ) {
            throw new LibraryException( "The key '{$key}' doesn't exist in SERVER superglobal", E_USER_ERROR );
        }
        return $_SERVER[$key];
    }

    public function getGet( $key )
    {
        if ( isset( $_GET[$key] ) ) {
            return $_GET[$key];
        }
        return false;
    }

    public function getPost( $key )
    {
        if ( isset( $_POST[$key] ) ) {
            return $_POST[$key];
        }
        return false;
    }

    public function getEnv( $key )
    {
        if ( isset( $_ENV[$key] ) ) {
            return $_ENV[$key];
        }
        return false;
    }
}