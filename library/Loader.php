<?php
/**
 * @author Joel Mukuthu
 * @copyright (c) 2010 Fiqne
 * @package Fiqne_MVC_Framework
 * @subpackage Loader
 */
class Loader
{
    /**
     * The default file extension for the autoloader.
     * @var string
     */
    protected static $autoloadExtension = ".php";

    /**
     * The library autoloader method.
     *
     * If the class name contains an underscore ('_'), the underscore maps to
     * a slash ('/' or '\') thus trying to load from the folder specified by
     * substr($className, 0, strpos($className, "_")).
     *
     * @param string $className The class name of the library class to load.
     * @throws LibraryException
     */
    public static function autoloader($className)
    {
        /*
         * Check if $className has already been initialized.
         */
        if(class_exists($className) || interface_exists($className)) {
            return;
        }
        $filename = str_replace('_', DIRECTORY_SEPARATOR, (string) $className) . self::$autoloadExtension;
        if(!self::isSecure($filename)) {
            /*
             * @see LibraryException
             */
            require_once ('LibraryException.php');
            throw new LibraryException("The filename '{$filename}' contains illegal characters", E_USER_ERROR);
        }
        /*
         * Check if file exists and is readable to avoid warning from include_once().
         */
        $includePath = get_include_path();
        $includePaths = explode(PATH_SEPARATOR, $includePath);
        $isReadable = false;
        foreach ($includePaths as $path) {
            $isReadable = is_readable($path . DIRECTORY_SEPARATOR . $filename);
            if($isReadable) {
                break;
            }
        }
        if ($isReadable) {
            require $filename;
        } else {
            /*
             * @see LibraryException
             */
            require_once ('LibraryException.php');
            throw new LibraryException("The file '{$filename}' containing class '{$className}' cannot be found or is not readable", E_COMPILE_ERROR);
        }

    }

    /**
     * Check if a supplied filename contains illegal characters.
     *
     * @param string $filename
     * @return bool True if $filename is secure, false otherwise,
     */
    public static function isSecure($filename)
    {
        return !preg_match('/[^a-z0-9\\/\\\\_.:-]/i', $filename);
    }

}