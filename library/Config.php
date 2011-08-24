<?php
/**
 * @author Joel Mukuthu
 * @copyright (c) 2010, Fiqne
 * @package Fiqne_MVC_Framework
 * @subpackage Config
 */
class Config
{
    /**
     * Stores the configurations parsed from /application/[module]/configs/config.ini
     * @var array
     */ 
    protected $configs = array();
    
    /**
     * Set {@link Config::configs}. This method defines the constants PHP_EXCEPTION_LOG and PHP_ERROR_LOG 
     * used in the config.ini file and then parses it. 
     * 
     * @return void
     * 
     * @throws LibraryException
     */
    public function setConfigs()
    {
        $module = Application::getInstance()->getRouter()->getModule();
        $configFile  = ROOT 
                     . DIRECTORY_SEPARATOR 
                     . 'application' 
                     . DIRECTORY_SEPARATOR 
                     . $module 
                     . DIRECTORY_SEPARATOR 
                     . 'configs' 
                     . DIRECTORY_SEPARATOR 
                     . 'config.ini';
        if (! Loader::isSecure($configFile)) {
            throw new LibraryException("The config filename '{$configFile}' contains illegal characters", E_COMPILE_ERROR);
        }
        if (! is_readable($configFile)) {
            throw new LibraryException("Cannot access config file '{$configFile}'. It may not exist or is not readable", E_COMPILE_ERROR);
        }
        $error_log = ROOT 
                   . DIRECTORY_SEPARATOR 
                   . 'application' 
                   . DIRECTORY_SEPARATOR 
                   . $module 
                   . DIRECTORY_SEPARATOR 
                   . 'logs' 
                   . DIRECTORY_SEPARATOR 
                   . 'php_error.log';
        $exception_log = ROOT 
                       . DIRECTORY_SEPARATOR 
                       . 'application' 
                       . DIRECTORY_SEPARATOR 
                       . $module 
                       . DIRECTORY_SEPARATOR 
                       . 'logs' 
                       . DIRECTORY_SEPARATOR 
                       . 'exception.log';
        defined('PHP_ERROR_LOG') ||
            define('PHP_ERROR_LOG', $error_log);
        defined('PHP_EXCEPTION_LOG') ||
            define('PHP_EXCEPTION_LOG', $exception_log);
            
        $this->configs = parse_ini_file($configFile, true);
    }

    /**
     * Get {@link Config::configs}. This method uses {@link Config::setConfigs()} to set configs if they 
     * have not been set.
     * 
     * @return array
     */ 
    public function getConfigs()
    {
        if (!$this->configs) {
            $this->setConfigs();
        }
        return $this->configs;
    }

    /**
     * Configure the application.
     * 
     * @uses @link Config::configs To configure the application.
     * 
     * @return void
     */ 
    public function run()
    {
        $configs = $this->getConfigs();
        //define timezone
        date_default_timezone_set($configs['application']['timezone']);
        //configure application
        $env = $configs['application']['environment'];
        $errCongigs = $configs[$env];
        //set error reporting mode
        error_reporting($errCongigs['error_reporting']);
        //email and log errors
        if ($errCongigs['email_errors'] == 1 && $errCongigs['log_errors'] == 1) {
            ini_set('error_log', $configs['application']['error_log']);
            set_error_handler(array($this, 'errorHandlerLogAndEmail'));
        //only email errors
        } elseif ($errCongigs['email_errors'] == 1 && $errCongigs['log_errors'] == 0) {
            set_error_handler(array($this, 'errorHandlerEmailOnly'));
        //only log errors
        } elseif ($errCongigs['email_errors'] == 0 && $errCongigs['log_errors'] == 1) {
            ini_set('log_errors', 1);
            ini_set('error_log', $configs['application']['error_log']);
        }
        //displaying of errors
        ini_set('display_errors', $errCongigs['display_errors']);
        ini_set('display_startup_errors', $errCongigs['display_startup_errors']);
        //handling of exceptions that haven't been caught
        set_exception_handler(array($this, 'exceptionHandler'));
    }
    
    //TODO: Why is this email going to junk while exception email doesn't?
    public function errorHandlerLogAndEmail($errno, $errstr, $errfile, $errline)
    {
        $error = "Error [{$errno}]: {$errstr} in {$errfile} on line {$errline}";
        error_log($error, 0);
        $this->sendErrorEmail($error);
        //avoid executing PHP's default error handler
        return true;
    }
    
    public function errorHandlerEmailOnly($errno, $errstr, $errfile, $errline)
    {
        $error = "Error [{$errno}]: {$errstr} in {$errfile} on line {$errline}";
        $this->sendErrorEmail($error);
        //avoid executing PHP's default error handler
        return true;
    }
    
    public function exceptionHandler(Exception $e)
    {
        Registry::set('exception', $e);
        try {
            //try to route to 500
            Application::getInstance()->getDispatcher()->setRoute(array('controller' => 'error', 'action' => 'error500'))->dispatch();
        } catch (LibraryException $e) {
            $configs = $this->getConfigs();
            $env = $configs['application']['environment'];
            if ($env == 'development') {
            	$prev = $e->getPrevious();
            	if ($prev) {
                    Util::e($prev->getMessage(), "First [{$prev->getCode()}]");
                    Util::e($prev->getTrace(), "Trace");
                    Util::e($e->getMessage(), "Next [{$e->getCode()}]");
                    Util::e($e->getTrace(), "Trace");
            	} else {
                    Util::e($e->getMessage(), "Exception [{$e->getCode()}]");
                    Util::e($e->getTrace(), "Trace");
            	}
            } elseif ($env == 'production') {
                $message = 'An unexpected application error occurred while processing your request. Please try again later.';
                Util::e($message, 'Unexpected Application Error');
            }    
        }
    }
    
    protected function sendErrorEmail($error)
    {
        $configs  = $this->getConfigs();
        $sendmail = false;
        $body     = "A PHP error occurred and was saved to the error log at " 
                  . date('g:i a') 
                  . " on " 
                  . date('jS M Y') 
                  . ".\n\n{$error}";
        $errInfoFile = $configs['application']['error_log'];
        if (file_exists($errInfoFile . '.info.txt')) {
            $lastLogTime = intval(file_get_contents($errInfoFile . '.info.txt'));
            if ($lastLogTime + $configs['application']['error_mailing_delay'] <= time()) {
                $body = file_get_contents($errInfoFile) . "\n" . $body;
                $sendmail = true;
            }
        } else {
            $sendmail = true;
        }
        if ($sendmail) {
            try {
                $transport = new Zend_Mail_Transport_Smtp($configs['mail']['host'], array(
                    'auth'     => 'login',
                    'username' => $configs['mail']['username'],
                    'password' => $configs['mail']['password']
                ));
                $mail = new Zend_Mail();
                $mail->setFrom($configs['application']['from_email'], 'System Autogenerated')
                     ->setSubject('An error occurred!')
                     ->addTo($configs['application']['to_email'], 'Fiqne Support')
                     ->setBodyHtml($body)
                     ->send($transport);
            } catch (Exception $e) {
                ini_set('error_log', $configs['application']['exception_log']);
                error_log("\nException [{$e->getCode()}]: {$e->getMessage()}Trace:\n{$e->getTraceAsString()}\nThis exception was caught while trying to email Fiqne Support with details regarding this error: {$error}\n", 0);
                ini_set('error_log', $configs['application']['error_log']);
            }
            file_put_contents($errInfoFile . '.info.txt', time());
        }
    }
}