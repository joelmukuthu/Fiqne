<?php
/**
 * LibraryException.
 *
 * Custom exception Handler. Extends {@link PHP_MANUAL#Extension PHP's Exception class}.
 *
 * @author Joel Mukuthu
 * @copyright (c) 2010, Fiqne
 * @package Fiqne_MVC_Framework
 */
class LibraryException extends Exception
{
    /**
     * The previous exception for use in PHP < 5.3
     * @var Exception|null
     */
    protected $previous = null;

    /**
     * Class constructor.
     * 
     * @uses PHP_MANUAL#Exception::__construct
     * 
     * @param string $message
     * @param int $code
     * @param Exception $previous
     */
    public function __construct($message, $code = 0, Exception $previous = null)
    {
        if (strnatcmp(phpversion(), '5.3') >= 0) {
            parent::__construct($message, $code, $previous);
        } else {
            parent::__construct($message, $code);
            $this->previous = $previous;
        }
    }

    /**
     * Overloads {@see PHP_MANUAL#Exception::getPrevious} for use in PHP < 5.3
     *  
     * @param string $method
     * @param array $args
     * 
     * @return LibraryException
     */
    public function __call($method, array $args)
    {
        if ($method == 'getPrevious') {
        	return $this->previous = $previous;;
        }
    }
}