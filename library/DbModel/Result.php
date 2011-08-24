<?php
/**
 * DbModel_Result.
 *
 * Enables retrieval of query results as objects as opposed to arrays.
 *
 * @author Joel Mukuthu
 * @copyright (c) 2010 Fiqne
 * @package Fiqne_MVC_Framework
 * @subpackage DbModel
 */
class DbModel_Result
{

    /**
     * Stores the values corresponding to a row in a query result set.
     * @var array
     */
    protected $rowValues = array();

    /**
     * Class constructor. Takes in an *associative* array that is row returned by {@see PHP_MANUAL#mysql_fetch_assoc}
     * or {@see PHP_MANUAL#mysqli_stmt_fetch} etc. It's necessary for the array to be associative to allow retrieval
     * of row values as class properties.
     *
     * @param array $row
     */
    public function __construct(array $row)
    {
        $this->rowValues = $row;
    }

    /**
     * Set a row value.
     *
     * Magic method that allows setting or changing of row values like so $row->name = 'Name';
     *
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    public function __set($key, $value)
    {
        $this->rowValues[$key] = $value;
    }

    /**
     * Get a row value.
     *
     * Magic method that allows getting of row values like so echo $row->name;
     *
     * @param string $key
     *
     * @return mixed
     *
     * @throws LibraryException If the supplied key (column name or alias) does not exist.
     */
    public function __get($key)
    {
        if (!isset($this->rowValues[$key])) {
            throw new LibraryException("The supplied column name or alias '{$key}' does not exist", E_USER_ERROR);
        }
        return $this->rowValues[$key];
    }

    /**
     * Check whether a row value has been set (exists) or not.
     *
     * Magic method that allows checking of existence of row values like so isset($row->name);
     *
     * @param string $key
     *
     * @return true|false
     */
    public function __isset($key)
    {
        return isset($this->rowValues[$key]);
    }


    /**
     * Unset a row value.
     *
     * Magic method that allows unsetting of row values like so unset($row->name);
     *
     * @param string $key
     *
     * @return void
     */
    public function __unset($key)
    {
        unset($this->rowValues[$key]);
    }
}