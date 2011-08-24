<?php
/**
 * @author Joel Mukuthu
 * @copyright (c) 2010, Fiqne
 * @package Fiqne_MVC_Framework
 * @subpackage Response
 */
class Response
{
    /**
     * The HTTP response code to send with the response. Ideally it should be 200, 304, 404 or 500.
     * @var int
     */
    protected $responseCode = 200;

    /**
     * Stores headers to be sent before output is sent to the browser.
     * @var array
     */
    protected $headers = array();

    /**
     * The output to sent to the browser.
     * @var string
     */
    protected $output = "";

    /**
     * Add a header to send before sending output.
     *
     * @param mixed $header May be a string or an array. If a string is passed, when sending the header it's sent as
     * passed e.g.
     * 		addHeader("HTTP/1.1 404 Not Found");
     *
     * If an array is passed, two posibilities are allowed, as shown by these examples:
     * 		array(404 => 'Not Found'); or
     * 		array('code' 	=> 500,
     * 			  'string'  => 'Internal Server Error',
     * 			  'replace' => true);
     * Note that in the second way of passing a header the keys 'code' and 'replace' are optional, in line with the
     * function definition of {@see PHP_MANUAL#header}; however, the 'string' key must exist.
     *
     * Also note that these are just examples, to send a "404 Not Found" header, it's recommended to pass a string instead.
     *
     * @return Response
     */
    public function addHeader($header)
    {
        $this->headers[] = $header;
        return $this;
    }

	/**
     * Sends headers to the browser before output is sent.
     *
     * @uses PHP_MANUAL#header
     *
     * @return Response
     */
    public function sendHeaders()
    {
        foreach ( $this->headers as $header ) {
            if ( is_string( $header ) ) {
                header( $header );
            } elseif ( is_array( $header ) ) {
                if ( array_key_exists( 'string', $header ) ) {
                    $string = $header['string'];
                    $code = null;
                    $replace = null;
                    if ( array_key_exists( 'code', $header ) ) {
                        $code = $header['code'];
                    }
                    if ( array_key_exists( 'replace', $header ) ) {
                        $replace = $header['replace'];
                    }
                    if ( is_null( $code ) && is_null( $replace ) ) {
                        header( $string );
                    } elseif ( is_null( $code ) && !is_null( $replace ) ) {
                        header( $string, $replace );
                    } elseif ( !is_null( $code ) && is_null( $replace ) ) {
                        header( $string, true, $code );
                    } else {
                        header( $string, $replace, $code );
                    }
                } else {
                    foreach ( $header as $code => $string ) {
                        header( $string, true, $code );
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Set the {@see Response::responseCode}.
     *
     * @param int $code
     *
     * @return Response
     */
    public function setResponseCode($code)
    {
        $this->responseCode = $code;
        return $this;
    }

    /**
     * Get the {@see Response::responseCode} for a response instance.
     *
     * @return int
     */
    public function getResponseCode()
    {
        return $this->responseCode;
    }

    /**
     * Set the output to echo to the browser.
     *
     * @param string $output
     *
     * @return Response
     */
    public function setOutput($output)
    {
        $this->output = $output;
        return $this;
    }
    
    /**
     * Get the response output.
     * 
     * @return string
     */ 
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Send response to the browser. Sends headers before any output is echoed.
     *
     * @uses Response::responseCode To send the appropriate HTTP response code to the browser. For this reason, for 200, 304, 404 and
     * 	500 headers instead of using {@see Response::addHeader}, use {@see Response::setResponseCode} instead.
     * @uses Response::sendHeaders
     *
     * @return void
     */
    public function sendOutput()
    {
    	header('HTTP/1.1 ' . $this->responseCode);
        if ( $this->headers ) {
            $this->sendHeaders();
        }
        if ($this->output) {
        	echo $this->output;
        }
    }

}