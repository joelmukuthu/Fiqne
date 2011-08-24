<?php

/**
 * @author Joel Mukuthu
 * @copyright (c) 2010 Fiqne
 *
 */
class Util
{
    /**
     * Generate a random string.
     * @param int $length
     * @param bool $special Whether to use special characters such as !@#$.
     * @param bool $caseSensitive Whether to use both upper and lower case characters.
     * @return string
     */
    public static function genRandomString($length = 40, $special = false, $caseSensitive = false)
    {
        $characters = "abcdefghijklmnopqrstuvwxyz0123456789";
        if ($caseSensitive) {
            $characters .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        }
        if ($special) {
            $characters .= "!@#$%^&*()}{[]?\\/.,";
        }
        $string = '';
        for($p = 0; $p < $length; $p++)
        {
            $string .= $characters[mt_rand(0, strlen($characters) - 1)];
        }
        return $string;
    }
    
    /**
     * Create a directory. This method recursively creates parent directories if they don't exist.
     * @param string $dir The full directory path to create.
     * @return true|false Returns true if the directory exists or is created successfully or false
     *  if unsuccessful.
     */
    public static function createDir($dir)
    {
        if (file_exists($dir)) {
            return true;
        }
        if (@mkdir($dir, 0600)) {
            return true;
        }
        return self::createDir(dirname($dir)) && mkdir($dir, 0600);
    }

    /**
     * PHP's echo function adapted for web output. Used to display output in a styled div.
     * Uses a default CSS class 'echo'. This class should only be used during development.
     * @param mixed $output The output to display.
     * @param string $heading The heading to display for the output.
     * @return void
     */
    public static function e($output, $heading = "Result")
    {
        echo '<div class="echo"><h1>' . self::htmlEnts($heading) . '</h1>';
        if(is_array($output)) {
            self::eArray($output);
        } else {
            echo '<p>' . self::htmlEnts($output) . '</p>';
        }
        echo '</div>';
    }

    /**
     * Loop through an array and output it for the web. To 'echo' an array, do not use this function, use {@see Util::e} instead
     * and pass the array as the first param. Uses a 'ul' element.
     * @param array $array
     * @return void
     */
    protected static function eArray($array)
    {
        if ($array) {
            echo '<ul>';
            foreach ($array as $key => $value) {
                echo '<li>' . $key . ' => ';
                if (is_array($value)) {
                    self::eArray($value);
                } else {
                    echo self::htmlEnts($value) . '</li>';
                }
            }
            echo '</ul>';
        }
    }

    /**
     * @see PHP_MANUAL#htmlentities
     * @param string $str
     * @return void
     */
    protected static function htmlEnts($str)
    {
        $str = is_object($str) ? get_class($str) : $str;
        return htmlentities((string) $str);
    }
}