<?php
/**
 * DbModel.
 *
 * This class must be extended by all user models that need to access the database (MySQL). Database login configurations
 * are defined in /application/[MODULE NAME]/configs/config.ini. The class uses PHP's MySQLi extension.
 *
 * All user models that extend this class must have these three class properties:
 * 
 * 	1. protected/public string $table - String whose value gives the actual name of
 *        the table in the database. Access should NOT be private.
 *
 * 	2. protected/public array $columns - Array representing all the table coumns as
 *        keys and their bind types as values. Even though this information can
 *        be gotten by running a DESCRIBE query, an application may run into 
 *        trouble when certain bind types are used such as 'b' for blobs. You 
 *        are advised to use these bind types instead, depending on the column
 *        type:
 * 		Column types summary:
 * 			(UN)SIGNED TINYINT: I
 * 			(UN)SIGNED SMALLINT: I
 * 			(UN)SIGNED MEDIUMINT: I
 * 			SIGNED INT: I
 * 			UNSIGNED INT: S
 * 			(UN)SIGNED BIGINT: S
 * 			(VAR)CHAR, (TINY/SMALL/MEDIUM/BIG)TEXT/BLOB: S
 * 			FLOAT/REAL/DOUBLE (PRECISION): D
 *        Access should NOT be private.
 *
 * 	3. protected/public string $primary - String whose value is the primary key
 *        column of the DB table. This variable is not mandatory as such, only
 *        necessary if the programmer wishes to use DbModel::getRow. Access
 *        should NOT be private.
 *
 * @author Joel Mukuthu
 * @copyright (c) 2010 Joel Mukuthu
 * @category Fiqne
 * @package Library
 * @subpackage DbModel
 */
abstract class DbModel
{
    /**
     * Stores the MySQL database connection object/resource.
     * 
     * @var null|resource
     */
    private $dbConnection = null;

    /**
     * Stores the name of the current database connected to.
     * 
     * @var string
     */
    private $currentDb = '';

    /**
     * Stores the query to be executed on the database.
     * 
     * @var string
     */
    private $query = '';

    /**
     * The column bind types to attach to a MySQLi statement object when 
     * {@link PHP_MANUAL#mysqli_stmt_bind_param mysqli_stmt_bind_param()} is called.
     * 
     * @var string
     */
    private $bindTypes = "";

    /**
     * The params to bind to a MySQLi statement object with
     * {@link PHP_MANUAL#mysqli_stmt_bind_param mysqli_stmt_bind_param()}.
     * 
     * @var array
     */
    private $bindParams = array();

    /**
     * The MySQLi statement object created with {@link PHP_MANUAL#mysqli_stmt_prepare mysqli_stmt_prepare()}.
     * 
     * @var false|resource See notes on {@link PHP_MANUAL#mysqli_stmt mysqli_stmt}
     */
    private $statement = false;

    /**
     * Flag to indicate that whether a current {@link PHP_MANUAL#mysqli_stmt mysqli_stmt} exists or not.
     * 
     * This flag is set to 'true' by the functions that initiate a query i.e. {@link DbModel::initSelect()},
     * {@link DbModel::initQuery()}, {@link DbModel::initInsert()}, {@link DbModel::initUpdate()} and
     * {@link DbModel::initDelete()} and reset to 'false' by {@link DbModel::executeStatement()}.
     * 
     * This may be useful for debugging if {@link DbModel::executeStatement()} doesn't execute correctly and 
     * reset this flag to 'false', although this will rarely be the case since any error while execting any of
     * the functions that initiate a query ({@link DbModel::initSelect()}, {@link DbModel::initQuery()}, 
     * {@link DbModel::initInsert()}, {@link DbModel::initUpdate()} and {@link DbModel::initDelete()}), 
     * {@link DbModel::prepareStatement()}, {@link DbModel::bindParams()} and {@link DbModel::executeStatement()}
     * itself will throw a {@link LibraryException} if any error occurs.
     * 
     * @var boolean
     */
    private $transactionActive = false;

    /**
     * Stores the allowed keys that can be fed to {@link DbModel::select()}. This is so that any unsupported key
     * or option raises a {@link LibraryException}.
     * 
     * @var array.
     */
    private $selectOptions = array(
        'all' , 
        'distinct' , 
        'distinctrow' , 
        'columns' , 
        'from' , 
        'join' , 
        'innerJoin' , 
        'leftJoin' , 
        'rightJoin' , 
        'where' , 
        'orWhere' , 
        'having' , 
        'orHaving' , 
        'groupBy' , 
        'orderBy' , 
        'limit' , 
        'offset'
    );

    /**
     * Whether to cache fetched record sets or not.
     * 
     * @var boolean
     */
    private $cacheResults = false;

    /**
     * Whether to return fetched records as objects or as arrays.
     * 
     * @var boolean If set to 'false', records are returned as arrays (default), else
     *              as an object of type {@link DbModel_Result}.
     */  
    private $rowsAsObjects = false;

    /**
     * The cache frontend options.
     * 
     * @see Zend_Cache_Core::_options
     * 
     * @var array
     */
    protected $cacheFrontendOptions = array(
        'automatic_serialization' => true , 
        'automatic_cleaning_factor' => 0 , 
        'lifetime' => null , 
        'ignore_user_abort' => false
    );

    /**
     * The backend cache options for {@link Zend_Cache_Backend_File}.
     * 
     * @see Zend_Cache_Backend_File::_options
     * 
     * @var array
     */
    protected $cacheBackendFileOptions = array(
        'cache_dir' => null , 'read_control' => true , 'read_control_type' => 'adler32' , 'file_name_prefix' => 'rs'
    );

    /**
     * The cache backend type to use for caching, this can be any of 'File', 'Apc', 'Memcached', 'Sqlite' 
     * or the others that are supported by {@link Zend_Cache} although only 'File' is currently supported.
     * 
     * @todo Support 'Apc' and 'Memcached' backend types.
     * 
     * @var string
     */
    protected $cacheBackendType = 'File';

    /**
     * Stores an object of {@link Zend_Cache} to use for caching records.
     * 
     * @var false|Zend_Cache
     */
    private $cacheObject = false;

    /**
     * The class destructor. This ensures that PHP also closes the database connection when doing garbage
     * collection.
     * 
     * @return void
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Create a database connection handle for subsequent inserts, updates, deletes and queries.
     * 
     * @return DbModel
     */
    private function createDbConnection()
    {
        $configs = Application::getInstance()->getConfig()->getConfigs();
        $dbConfigs = $configs['database'];
        /*Create connection*/
        $connection = new mysqli($dbConfigs['db_host'], $dbConfigs['db_username'], $dbConfigs['db_password'], $dbConfigs['db_name']);
        if ($connection === false || mysqli_connect_errno()) {
            throw new LibraryException("Failed to create database connection: " . mysqli_connect_error(), E_COMPILE_ERROR);
        }
        $this->dbConnection = $connection;
        $this->currentDb = $dbConfigs['db_name'];
        return $this;
    }

    /**
     * Get the database connection handle. This method automatically creates a handle using {@link DbModel::createDbConnection()}
     * if none exists.
     * 
     * @return resource Of type {@link PHP_MANUAL#mysqli mysqli}.
     */
    protected function getDbConnection()
    {
        if (is_null($this->dbConnection)) {
            $this->createDbConnection();
        }
        return $this->dbConnection;
    }

    /**
     * Enable caching of all results fetched by an instance of {@link DbModel}. 
     * 
     * Even though the individual retrieval methods {@link DbModel::get()}, {@link DbModel::getAll()}, {@link DbModel::getRow()}, 
     * {@link DbModel::query()} and {@link DbModel::select()} can be used to enable or disable caching for a single call, this 
     * method allows a user to enable the global setting for all these methods.
     * 
     * @return DbModel
     */
    public function enableCaching()
    {
        $this->cacheResults = true;
        return $this;
    }

    /**
     * Disable caching of all results fetched by an instance of {@link DbModel}. 
     * 
     * Even though the individual retrieval methods {@link DbModel::get()}, {@link DbModel::getAll()}, {@link DbModel::getRow()}, 
     * {@link DbModel::query()} and {@link DbModel::select()} can be used to enable or disable caching for a single call, this 
     * method allows a user to disable the global setting for all these methods.
     * 
     * @return DbModel
     */
    public function disableCaching()
    {
        $this->cacheResults = false;
        return $this;
    }
    
    /**
     * Purge all saved results from the cache.
     * 
     * @return DbModel
     */ 
    public function clearCache()
    {
        $this->getCacheObject()->clean('all');
        return $this;
    }

    /**
     * Enable returning of all results fetched by an instance of {@link DbModel} as objects of type {@link DbModel_Result}.
     * 
     * The individual retrieval methods {@link DbModel::get()}, {@link DbModel::getAll()}, {@link DbModel::getRow()}, 
     * {@link DbModel::query()} and {@link DbModel::select()} cannot be used to enable this setting for a single call although a
     * similar result can be achieved by calling this method before a retrieval method is called and calling 
     * {@link DbModel::rowsAsArrays} before the next.
     * 
     * @return DbModel
     */
    public function rowsAsObjects()
    {
        $this->rowsAsObjects = true;
        return $this;
    }

    /**
     * Enable returning of all results fetched by an instance of {@link DbModel} as arrays. 
     * 
     * Note, however, that this is the default setting. 
     * 
     * @return DbModel
     */
    public function rowsAsArrays()
    {
        $this->rowsAsObjects = false;
        return $this;
    }

    /**
     * Set the cache backend type to use with {@link Zend_Cache} for caching records.
     * 
     * @param string $type
     * 
     * @return DbModel
     */
    public function setCacheBackendType($type)
    {
        $this->cacheBackendType = (string) $type;
        return $this;
    }

    /**
     * Get the number of rows affected by the most recent {@link DbModel::executeStatement()}.
     * 
     * Note, however, that any call of {{@link DbModel::update()}} or {@link DbModel::delete()} will automatically
     * return the number of rows affected.
     * 
     * @return int
     */ 
    public function getAffectedRows()
    {
        return $this->getDbConnection()->affected_rows;
    }

    /**
     * Set the name of the database to work with. This is if you wish to select a different database other than the one 
     * defined in the application's 'config.ini' file.
     * 
     * @param string $dbName
     * 
     * @return DbModel
     */
    public function setDb($dbName)
    {
        $conn = $this->getDbConnection();
        $select = $conn->select_db($dbName);
        if (! $select) {
            throw new LibraryException("Could not select database '{$dbName}' [{$conn->errno}]: {$conn->error}", E_COMPILE_ERROR);
        }
        $this->currentDb = $dbName;
        return $this;
    }

    /**
     * Close a database connection. This method is automatically called by {@link DbModel::__destruct}.
     * 
     * @return void
     */
    public function close()
    {
        $conn = $this->getDbConnection();
        if (! $conn->close()) {
            throw new LibraryException("Could not close the database connection [{$conn->errno}]: {$conn->error}", E_COMPILE_ERROR);
        }
        $this->rewind();
        $this->dbConnection = null;
    }

    /**
     * Set the name of the database table to work with. This is an alternative to declaring the name of the table as a property
     * of a class that extends {@link DbModel}.
     * 
     * @param string $table
     * 
     * @return DbModel
     */
    public function setTable($table)
    {
        $this->table = (string) $table;
        return $this;
    }

    /**
     * Set the table columns and their bind types for an instance of {@link DbModel}. This is an alternative to declaring the 
     * table columns as a property of a class that extends {@link DbModel}.
     * 
     * @param array $columns This should contain the table column names as array keys and their bind types as their values. 
     * 
     * @return DbModel
     */
    public function setColumns(array $columns)
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Set the table's primary key field name for an instance of {@link DbModel}. This is an alternative to declaring it as a 
     * property of a class that extends {@link DbModel}.
     * 
     * @param string $primary
     * 
     * @return DbModel
     */
    public function setPrimaryKey($primary)
    {
        $this->primary = (string) $primary;
        return $this;
    }

    /**
     * Get the bind type of a column.
     * 
     * @param string $column
     * 
     * @return string
     * 
     * @throws LibraryException If the column doesn't exist in the columns array.
     */
    protected function getColumnType($column)
    {
        $columns = $this->columns;
        if (! array_key_exists((string) $column, $columns)) {
            throw new LibraryException("Invalid column name '{$column}' supplied", E_USER_ERROR);
        }
        return $columns[$column];
    }

    /**
     * Check whether a column name is one of the table's columns or not.
     * 
     * @param string $column
     * 
     * @return true|false
     */
    public function isTableColumn($column)
    {
        return array_key_exists((string) $column, $this->columns);
    }

    /**
     * Get records from a database table. This method is an upper-level implementation of {@link DbModel::select()}.
     * If you wish to have more SELECT options, use that method instead.
     * 
     * @param array $returnFields
     *  
     *      The columns to return from the table. If not provided, this method returns all the table's
     *          colummns in the result set.
     * 
     *      The array keys/values should be specified as follows:
     * 
     *          array('column' => 'alias')
     * 
     *      i.e. provide the column name as key and its alias as it's value. The alias is what will be in 
     *        the returned result set.
     *                              
     * @param array $where 
     *          
     *      Array that will map to the 'WHERE' clause of the MySQL query. If not provided, no 'WHERE' clause 
     *        is put in the MySQL query. 
     * 
     *      This should be specifed as follows:
     * 
     *          array('where' => 'value')
     * 
     *      i.e. provide a where clause as key and it's value as 'value'. E.g. array('name !=' => 'John') will
     *        map to "WHERE name != 'Joel'".
     * 
     *      Note that if more that one where clauses are provided, they will be joined with 'AND' and not 'OR'.
     *        If you wish to use 'OR', use {@link DbModel::select()} instead.
     * 
     * @param boolean $doNotCache 
     * 
     *      Whether to cache results for this request or not.
     * 
     * @return array
     * 
     *      If no records are matched, an empty array is returned.
     */    
    public function get($returnFields = array(), $where = array(), $doNotCache = false)
    {
        $options = array();
        if ($returnFields) {
            $options['columns'] = $returnFields;
        }
        if ($where) {
            $options['where'] = $where;
        }
        $this->initSelect($options);
        if ($this->cacheResults) {
            $cached = $this->getCacheObject()
                           ->load($this->getCacheId());
            if ($cached) {
                $this->rewind();
                return $cached;
            }
        }
        return $this->prepareStatement()
                    ->bindParams()
                    ->executeStatement('get', $doNotCache);
    }

    /**
     * Get records from a database table. This method is an upper-level implementation of {@link DbModel::select()}.
     * If you wish to have more SELECT options, use that method instead.
     * 
     * It is also a simplified version of {@link DbModel::get()}.
     * 
     * @param array $returnFields
     *  
     *      The columns to return from the table. If not provided, this method returns all the table's
     *          colummns in the result set.
     * 
     *      The array keys/values should be specified as follows:
     * 
     *          array('column' => 'alias')
     * 
     *      i.e. provide the column name as key and its alias as it's value. The alias is what will be in 
     *        the returned result set.
     * 
     * @param boolean $doNotCache 
     * 
     *      Whether to cache results for this request or not.
     * 
     * @return array
     * 
     *      If no records are matched, an empty array is returned.
     */
    public function getAll($returnFields = array(), $doNotCache = false)
    {
        $options = array();
        if ($returnFields) {
            $options['columns'] = $returnFields;
        }
        $this->initSelect($options);
        if ($this->cacheResults) {
            $cached = $this->getCacheObject()
                           ->load($this->getCacheId());
            if ($cached) {
                $this->rewind();
                return $cached;
            }
        }
        return $this->prepareStatement()
                    ->bindParams()
                    ->executeStatement('getAll', $doNotCache);
    }

    /**
     * Get records from a database table. This method is an upper-level implementation of {@link DbModel::select()}.
     * If you wish to have more SELECT options, use that method instead.
     * 
     * It is also a simplified version of {@link DbModel::get()}. It's implementation allows getting a single row from
     * a database table using it's primary key.
     * 
     * @param string|int $primaryKeyValue The value for the primary key.
     * 
     * @param array $returnFields
     *  
     *      The columns to return from the table. If not provided, this method returns all the table's
     *          colummns in the result set.
     * 
     *      The array keys/values should be specified as follows:
     * 
     *          array('column' => 'alias')
     * 
     *      i.e. provide the column name as key and its alias as it's value. The alias is what will be in 
     *        the returned result set.
     * 
     * @param boolean $doNotCache 
     * 
     *      Whether to cache results for this request or not.
     * 
     * @return array
     * 
     *      If no records are matched, an empty array is returned.
     */
    public function getRow($primaryKeyValue, $returnFields = array(), $doNotCache = false)
    {
        $options = array();
        $options['where'] = array(
            $this->primary . ' = ' => $primaryKeyValue
        );
        if ($returnFields) {
            $options['columns'] = $returnFields;
        }
        $this->initSelect($options);
        if ($this->cacheResults) {
            $cached = $this->getCacheObject()
                           ->load($this->getCacheId());
            if ($cached) {
                $this->rewind();
                return $cached;
            }
        }
        return $this->prepareStatement()
                    ->bindParams()
                    ->executeStatement('getRow', $doNotCache);
    }

    /**
     * Get records from a database table. This method allows running already constructed queries in the database.
     * 
     * @param string $query The query.
     * 
     * @param array $values
     * 
     *      If provided, this method will assume the query contains MySQL value placeholders ('?') and try to bind 
     *          the values provided to the MySQL statement created.
     * 
     *      The bind types for the values will be determined using their data type, i.e. ints will be mapped to 'i', 
     *          strings to 's' and floats to 'd'.
     * 
     *      The array has no particular specification, just provide the values as array values, no need for array keys.
     * 
     *      Note that the number values must match the number of placeholders and the order in which they were passed
     *          or {@link DbModel::bindParams()} will throw an exception.
     * 
     * @param boolean $doNotCache 
     * 
     *      Whether to cache results for this request or not.
     * 
     * @return array
     * 
     *      If no records are matched, an empty array is returned.
     */
    public function query($query, $values = array(), $doNotCache = false)
    {
        $this->initQuery($query);
        if ($this->cacheResults) {
            $cached = $this->getCacheObject()
                           ->load($this->getCacheId());
            if ($cached) {
                $this->rewind();
                return $cached;
            }
        }
        if ($values) {
            foreach ($values as $val) {
                $this->addBindType($val, false)
                     ->addBindParam($val);
            }
        }
        return $this->prepareStatement()
                    ->bindParams()
                    ->executeStatement('getAll', $doNotCache);
    }

    /**
     * Get records from a database table. This is the lower-level implementation for SELECTing from the database.
     * {@link DbModel::get()}, {@link DbModel::getAll()} and {@link DbModel::getRow()} use this method to construct
     * queries.
     * 
     * @param array $options 
     * 
     *      The SELECT options. The keys in this array must match the values in {@link DbModel::selectOptions} or 
     *          this method will throw an error. The options should be specified as follows:
     * 
     *      The 'distinct' key:
     *          As long as this key exists, whatever its value, a DISTINCT clause will be added to the SELECT query.
     *          -- example --> 'distinct' => true
     *      
     *     	The 'distinctrow' key:
     *          As long as this key exists, whatever its value, a DISTINCTROW clause will be added to the SELECT query.
     *          -- example --> 'distinctrow' => true
     * 
     * 	    The 'all' key:
     *          As long as this key exists, whatever its value, an ALL clause will be added to the SELECT query.
     *          -- example --> 'all' => true
     * 
     *      The 'columns' key:
     *          The table columns to return. It can take two forms:
     *          -- string --
     *              If a string is supplied, it is used in the query as is. 
     *              It's recommended that a well formed (e.g. quoted) SQL expression is passed, as shown here: 
     *                  'columns' => '`col1`,`col2`'
     *          -- array -- 
     *              If an array is supplied, the 'columns' expression is constructed using the passed array, whereby 
     *                  the array keys represent column names while the values represent aliases to use for each column.
     *                  -- example 1 --
     *                      'columns' => array(
     *             					'col1' => 'a',
     *             					'col2' => 'b',
     *             					'col3' => 'd',
     *             				)
     *             	    This maps to "`table`.`col1` AS a,`table`.`col2` AS b,`table`.`col3` AS d"
     *                  -- example 2 --
     *             		   'columns' => array(
     *             					'col1' => 'a',
     *             					'col2' => 'b',
     *             					'SUM(1 + 2)' => 'sum',
     *             				)
     *             	    This maps to "`table`.`col1` AS a,`table`.`col2` AS b,SUM(1 + 2) AS sum"
     * 
     *              If an array is supplied, this method will make an educated guess on whether the column name is an SQL 
     *                  function or a column name. If you get any unexpected results when using any SQL functions, pass a 
     *                  string instead (option 1 above).
     * 
     *      The 'from' key:
     *          If the 'from' key is ommitted, this method will use the default table name.
     *          It can take two forms:
     *              -- string --
     *                  If a string is supplied, it is used in the query as is. It's recommended that a well formed 
     *                  SQL FROM expression is passed, as shown here: 
     *                      'from' => '`table1`, `table2`, `table3`'
     *              -- array --
     *                  If an array is supplied, the FROM expression is constructed using the passed array, whereby
     *                      the array values represent table names, e.g. 
     *                          'from' => array('table1', 'table2', 'table3')
     *      The 'join' key:
     *          This key must be passed an array as a value with either 'on' or 'using' keys and an optional 'columns'
     *              key. The values of 'on' and 'using' keys are used as provided to construct the SELECT statement so
     *              it's recommended to use well formed SQL in them.
     *          If the 'columns' key is not provided, all columns from the join table are included in the result set.
     *          -- example --
     *              'join' => array(
     * 				     'table2' => array(
     * 						'on' 	  => '`table1`.`id`=`table2`.`id`',
     * 						'columns' => array(
     * 								 'col1' => 'a','col2' => 'b',
     *							   )
     * 					    ),
     *				      'table3' => array(
     *					    'using'   => '`id`',
     * 					    'columns' => array(
     * 								'col1' => 'a','col2' => 'b',
     *					           )
     *					    ),
     *			       )
     * 
     *      The 'innerJoin' key:
     *          See notes on the 'join' key above.
     * 
     *      The 'leftJoin' key:
     *          See notes on the 'join' key above.
     * 
     *      The 'rightJoin' key:
     *          See notes on the 'join' key above.
     * 
     * 
     *      The 'where' key:
     *          This key must be passed an array that will map to the 'WHERE' clause of the MySQL query. 
     *          This can be specifed in any of these ways:
     *              -- example1 --
     *                  'where' => array(
     *                      'table1.col = ?'   => '1',
     *         	            'table2.col2 >= ?' => 13,
     *         	            'table3.col3 <= ?' => 13,
     *         	            'table.name != ?'  => 'John'
     *                  )
     *              -- example2 --
     *                  'where' => array(
     *                      array( 'table1.col = ?' => '1' ),
     *                      array( 'table1.col = ?' => '2' ),
     *                      array( 'table1.col = ?' => '3' )
     *                  )
     *          The second example demonstrates how you would use the same field in more than one WHERE claues. 
     *          Note that the if more than one where clauses are provided, they will be joined using 'AND'.
     * 
     *      The 'orWhere' key:
     *          This implementation for this key is the same as 'where' above except that if more than one clauses,
     *              they are joined using 'OR'.
     * 
     *      The 'groupBy' key:
     *          This key must be passed an array that specifies the SELECT's GROUP BY clause. It is specified as follows:
     *              'groupBy' => array(
     *                      'col1' => 'ASC',
     *                      'col2' => 'DESC',
     *               )
     *          -- or --
     *              'groupBy' => array('col1', 'col2') 
     * 
     *      The 'having' key:
     *          This key must be passed an array which defines the SELECT statement's HAVING clause. It's implementation
     *              is similar to the 'where' key above.
     * 
     *      The 'orHaving' key:
     *          This key must be passed an array which defines the SELECT statement's HAVING clause. It's implementation
     *              is similar to the 'orWhere' key above.
     * 
     *      Note that the options can only contain either the 'having' or the 'orHaving' key, if both are supplied only the
     *          'having' is considered.
     * 
     *      The 'orderBy' key:
     *          This key is passed a string or array that defines the SELECT statement's ORDER BY clause. It's specified as
     *              follows:
     *              -- string --
     *                  'orderBy' => '`table`.`column` ASC'
     *              -- array --
     *                  'orderBy' => array('column' => 'ASC')
     *              Note that if the array is passedd, it should only have *one* value. If more are passed, only the first is 
     *                  considered.
     * 
     *      The 'limit' key: 
     *          This key is passed an integer that specifies the limit of records to retrieve from the database table i.e. the
     *              LIMIT clause of the SELECT statement. 
     *          It's specified as follows:
     *              'limit' => 10
     * 
     *      The 'offset' key:
     *          This key is passed an integer that specifies the offset from where to start retrieving records. It's used in 
     *              conjunction with the 'limit' key and is only considered if the 'limit' key exists. As such it's specified
     *              as follows:
     *                  'limit'  => 5
     *                  'offset' => 10
     *      
     *       Supplying a key other than the ones above causes this method to throw a {@link LibraryException}.
     * 
     * @param boolean $doNotCache 
     * 
     *      Whether to cache results for this request or not.
     * 
     * @return array
     * 
     *      If no records are matched, an empty array is returned.
     * 
     * @todo Research more on 'distinct', 'distinctrow' and 'all' keys.
     * 
     * @throws LibraryException
     */
    public function select(array $options, $doNotCache = false)
    {
        foreach ($options as $key => $value) {
            if (! in_array($key, $this->selectOptions)) {
                throw new LibraryException("Invalid option '{$key}' supplied in options array", E_USER_ERROR);
            }
        }
        $this->initSelect($options);
        if ($this->cacheResults) {
            $cached = $this->getCacheObject()
                           ->load($this->getCacheId());
            if ($cached) {
                $this->rewind();
                return $cached;
            }
        }
        return $this->prepareStatement()
                    ->bindParams()
                    ->executeStatement('getAll', $doNotCache);
    }

    public function insert($data)
    {
        return $this->initInsert($data)->prepareStatement()->bindParams()->executeStatement('insert');
    }

    public function update($data, $where)
    {
        return $this->initUpdate($data, $where)->prepareStatement()->bindParams()->executeStatement('update');
    }

    public function delete($where)
    {
        return $this->initDelete($where)->prepareStatement()->bindParams()->executeStatement('delete');
    }

    protected function getCacheId()
    {
        return md5('salt' . $this->query);
    }

    protected function addBindType($spec, $isColumn = true)
    {
        if ($this->transactionActive) {
            if ($isColumn) {
                $this->bindTypes .= $this->getColumnType($spec);
            } else {
                $this->bindTypes .= $this->getBindType($spec);
            }
        } else {
            throw new LibraryException("An active transaction has not yet been executed", E_USER_NOTICE);
        }
        return $this;
    }

    protected function addBindParam($value)
    {
        if ($this->transactionActive) {
            $this->bindParams[] = $value;
        } else {
            throw new LibraryException("An active transaction has not yet been executed", E_USER_NOTICE);
        }
        return $this;
    }

    protected function getBindType($value)
    {
        if (is_int($value)) {
            return 'i';
        } elseif (is_double($value)) {
            return 'd';
        } elseif (is_string($value)) {
            return 's';
        }
        return 's'; //fail safety feature
    }

    protected function arrayByReference(array $array)
    {
        $refs = array();
        foreach ($array as $key => $value) {
            $refs[$key] = &$array[$key];
        }
        return $refs;
    }

    protected function initInsert($values)
    {
        $this->query = "";
        $this->transactionActive = true;
        $placeholders = '';
        $query = "INSERT INTO `{$this->table}` (";
        foreach ($values as $col => $val) {
            $query .= "`{$col}`,";
            $placeholders .= '?,';
            $this->addBindType($col)->addBindParam($val);
        }
        $this->query = substr($query, 0, - 1) . ') VALUES (' . substr($placeholders, 0, - 1) . ')';
        return $this;
    }

    protected function initUpdate($values, $where = array())
    {
        $this->query = "";
        $this->transactionActive = true;
        $table = $this->table;
        $query = "UPDATE `{$table}` SET ";
        foreach ($values as $col => $val) {
            $query .= "`{$table}`.`{$col}` = ?,";
            $this->addBindType($col)->addBindParam($val);
        }
        $query = substr($query, 0, - 1);
        if ($where) {
            $query .= " WHERE ";
            foreach ($where as $col => $val) {
                $query .= "`{$table}`.`{$col}` = ? AND ";
                $this->addBindType($col)->addBindParam($val);
            }
            $query = substr($query, 0, - 5);
        }
        $this->query = $query;
        return $this;
    }

    protected function initDelete($where = array())
    {
        $this->query = "";
        $this->transactionActive = true;
        $table = $this->table;
        if ($where) {
            $query = "DELETE FROM `{$table}` WHERE ";
            foreach ($where as $col => $val) {
                $query .= "`{$table}`.`{$col}` = ? AND ";
                $this->addBindType($col)->addBindParam($val);
            }
            $query = substr($query, 0, - 5);
        } else {
            $query = "TRUNCATE TABLE `{$table}`";
        }
        $this->query = $query;
        return $this;
    }

    protected function initQuery($query)
    {
        $this->transactionActive = true;
        $this->query = $query;
        return $this;
    }

    protected function initSelect($options)
    {
        $this->transactionActive = true;
        $this->query 			 = "";
        $table 					 = $this->table;
        $distinct 				 = "";
        $columns 				 = "";
        $from 					 = "";
        $join 					 = "";
        $where 					 = "";
        $groupBy 				 = "";
        $having 				 = "";
        $orderBy 				 = "";
        $limit 					 = "";
        
        if (array_key_exists('distinct', $options)) {
            $distinct = "DISTINCT";
        
        } elseif (array_key_exists('distinctrow', $options)) {
            $distinct = "DISTINCTROW";
        } elseif (array_key_exists('all', $options)) {
            $distinct = "ALL";
        }
        
        //The 'columns' key.
        if (array_key_exists('columns', $options)) {
            $columnsClause = $options['columns'];
            //string
            if (is_string($columnsClause)) {
                $columns .= $columnsClause;
            //array
            } elseif (is_array($columnsClause)) {
                foreach ($columnsClause as $col => $alias) {
                    if ($this->isTableColumn($col)) {
                        $columns .= "`{$table}`.`{$col}` AS {$alias},";
                    } else {
                        if (preg_match('/.+\(.*\)/', (string) $col)) {
                            $columns .= "{$col} AS {$alias},"; //if a MySQL function, don't quote
                        } else {
                            $columns .= "`{$col}` AS {$alias},"; //else, quote
                        }
                    }
                }
                $columns = substr($columns, 0, - 1);
            } else {
                throw new LibraryException("Invalid 'columns' clause supplied", E_USER_ERROR);
            }
        } else {
            $columns .= "*";
        }
        
        //The 'from' key.
        if (array_key_exists('from', $options)) {
            $fromClause = $options['from'];
            //string
            if (is_string($fromClause)) {
                $from .= $fromClause;
            //array
            } elseif (is_array($fromClause)) {
                foreach ($fromClause as $table) {
                    $from .= "`{$table}`,";
                }
                $from = substr($from, 0, - 1);
            } else {
                throw new LibraryException("Invalid 'from' clause supplied", E_USER_ERROR);
            }
        } else {
            $from .= "`{$table}`";
        }
        
        //The 'join' key
        if (array_key_exists('join', $options)) {
            $arr = $this->join($options['join'], '');
            $join .= $arr[0];
            $columns .= $arr[1];
        }
        
        //The 'innerJoin' key
        if (array_key_exists('innerJoin', $options)) {
            $arr = $this->join($options['innerJoin'], 'INNER', 'innerJoin');
            $join .= $arr[0];
            $columns .= $arr[1];
        }
        
        //The 'leftJoin' key
        if (array_key_exists('leftJoin', $options)) {
            $arr = $this->join($options['leftJoin'], 'LEFT',  'leftJoin');
            $join .= $arr[0];
            $columns .= $arr[1];
        }
        
        //The 'rightJoin' key
        if (array_key_exists('rightJoin', $options)) {
            $arr = $this->join($options['rightJoin'], 'RIGHT', 'rightJoin');
            $join .= $arr[0];
            $columns .= $arr[1];
        }
        
        //The 'where' key
        if (array_key_exists('where', $options)) {
            $whereClause = $options['where'];
            if (!$where) {
                $where = "WHERE ";
                foreach ($whereClause as $col => $val) {
                    if (is_int($col)) {
                        if (!is_array($val)) {
                            throw new LibraryException("Invalid 'where' clause specified", E_USER_ERROR);
                        }
                        foreach ($val as $c => $v) {
                            $where .= "{$c} AND ";
                            $this->addBindType($v, false)
                                 ->addBindParam($v);
                        }
                    } else {
                        $where .= "{$col} AND ";
                        $this->addBindType($val, false)
                             ->addBindParam($val);
                    }
                }
                $where = substr($where, 0, - 5);
            } else {
                foreach ($whereClause as $col => $val) {
                    if (is_int($col)) {
                        if (!is_array($val)) {
                            throw new LibraryException("Invalid 'where' clause specified", E_USER_ERROR);
                        }
                        foreach ($val as $c => $v) {
                            $where .= " AND {$c}";
                            $this->addBindType($v, false)
                                 ->addBindParam($v);
                        }
                    } else {
                        $where .= " AND {$col}";
                        $this->addBindType($val, false)
                             ->addBindParam($val);
                    }
                }
            }
        }
        
        //The 'orWhere' key
        if (array_key_exists('orWhere', $options)) {
            $whereClause = $options['orWhere'];
            if (!$where) {
                $where = "WHERE ";
                foreach ($whereClause as $col => $val) {
                    $where .= "{$col} OR ";
                    $this->addBindType($val, false)
                         ->addBindParam($val);
                }
                $where = substr($where, 0, - 4);
            } else {
                foreach ($whereClause as $col => $val) {
                    $where .= " OR {$col}";
                    $this->addBindType($val, false)
                         ->addBindParam($val);
                }
            }
        }
        if (!$where) {
            $where = "WHERE 1";
        }
        
        //The 'groupBy' key
        if (array_key_exists('groupBy', $options)) {
            $groupByClause = $options['groupBy'];
            $groupBy = "GROUP BY ";
            foreach ($groupByClause as $col => $order) {
                if (is_int($col)) {
                    $groupBy .= "({$order}),";
                } else {
                    $order = strtoupper($order);
                    $groupBy .= "({$col}) " . strtoupper($order) . ",";
                }
            }
            $groupBy = substr($groupBy, 0, - 1);
        }
        
        //The 'having' key
        if (array_key_exists('having', $options)) {
            $havingClause = $options['having'];
            if (! $having) {
                $having = "HAVING ";
                foreach ($havingClause as $spec => $value) {
                    $having .= "{$spec} AND ";
                    $this->addBindType($value, false)
                         ->addBindParam($value);
                }
                $having = substr($having, 0, - 5);
            } else {
                foreach ($havingClause as $spec => $value) {
                    $having .= " AND {$spec}";
                    $this->addBindType($value, false)
                         ->addBindParam($value);
                }
            }
            
        //The 'orHaving' key
        } elseif (array_key_exists('orHaving', $options)) {
            $havingClause = $options['orHaving'];
            if (! $having) {
                $having = "HAVING ";
                foreach ($havingClause as $spec => $value) {
                    $having .= "{$spec} OR ";
                    $this->addBindType($value, false)->addBindParam($value);
                }
                $having = substr($having, 0, - 4);
            } else {
                foreach ($havingClause as $spec => $value) {
                    $having .= " OR {$spec}";
                    $this->addBindType($value, false)->addBindParam($value);
                }
            }
        }
        
        //The 'orderBy' key
        if (array_key_exists('orderBy', $options)) {
            $orderByClause = $options['orderBy'];
            if (is_array($orderByClause)) {
                $keys = array_keys($orderByClause);
                $orderBy = "ORDER BY " . $keys[0] . " " . strtoupper($orderByClause[$keys[0]]);
            } elseif (is_string($orderByClause)) {
                $orderBy = "ORDER BY " . $orderByClause;
            } else {
                throw new LibraryException("Invalid 'orderBy' clause supplied", E_USER_ERROR);
            }
        }
        
        //The 'limit' key
        if (array_key_exists('limit', $options)) {
            $limitClause = $options['limit'];
            if (is_int($limitClause)) {
                $limit = "LIMIT ";
            } else {
                throw new LibraryException("Invalid 'limit': {$limitClause} clause supplied", E_USER_ERROR);
            }
            //The 'offset' key
            if (array_key_exists('offset', $options)) {
                $offsetClause = $options['offset'];
                if (is_int($offsetClause)) {
                    $limit .= "?,";
                    $this->bindTypes .= 'i';
                    $this->addBindParam($offsetClause);
                } else {
                    throw new LibraryException("Invalid 'offset': {$offsetClause} clause supplied", E_USER_ERROR);
                }
            }
            $limit .= "?";
            $this->bindTypes .= 'i';
            $this->addBindParam($limitClause);
        }
        $this->query = "SELECT {$distinct} {$columns} FROM {$from} {$join} {$where} {$groupBy} {$having} {$orderBy} {$limit}";
        return $this;
    }

    private function join($joinClause, $type = "", $exceptionWord = 'join')
    {
        foreach ($joinClause as $table => $opts) {
            if (isset($opts['on'])) {
                $join = $type . " JOIN `{$table}` ON ({$opts['on']}) ";
            } elseif (isset($opts['using'])) {
                $join = $type . " JOIN `{$table}` USING (`{$opts['using']}`) ";
            } else {
                throw new LibraryException("Specify an 'on' or 'using' value for '{$exceptionWord}'", E_USER_ERROR);
            }
            if (isset($opts['columns'])) {
                $cols = $opts['columns'];
                $columns = "";
                foreach ($cols as $col => $alias) {
                    if (preg_match('/.+\(.*\)/', (string) $col)) {
                        $columns .= ",{$col} AS {$alias}"; //if a MySQL function, don't quote
                    } else {
                        $columns .= ",`{$table}`.`{$col}` AS {$alias}"; //else, quote
                    }
                }
            } else {
                $columns = ",`{$table}`.*";
            }
        }
        return array(
            $join , $columns
        );
    }

    private function createCacheObject()
    {
        //file caching
        if ($this->cacheBackendType == 'File') {
            /*Set caching directory*/
            $route = Application::getInstance()->getRouter()->getRoute();
            $dir   = ROOT 
                   . DIRECTORY_SEPARATOR 
                   . 'public_html' 
                   . DIRECTORY_SEPARATOR 
                   . 'cache' 
                   . DIRECTORY_SEPARATOR 
                   . 'db' 
                   . DIRECTORY_SEPARATOR 
                   . $this->table 
                   . DIRECTORY_SEPARATOR;
            if (!file_exists($dir)) {
                if (!Util::createDir($dir)) {
                    throw new LibraryException("Could not create the cache directory", E_COMPILE_ERROR);
                }
            }
            $this->cacheBackendFileOptions['cache_dir'] = $dir; 
            try {
                $this->cacheObject = Zend_Cache::factory('Core', 'File', $this->cacheFrontendOptions, $this->cacheBackendFileOptions);
            } catch (Exception $e) {
                throw new LibraryException("Could not create the cache object", E_COMPILE_ERROR, $e);
            }
        //apc caching
        } elseif ($this->cacheBackendType == 'Apc') {
            throw new LibraryException("APC caching is not yet supported", E_COMPILE_ERROR);
            /*
            try {
                $this->cacheObject = Zend_Cache::factory('Core', 'Apc', $this->cacheFrontendOptions);
            } catch (Exception $e) {
                throw new LibraryException("Could not create the cache object", E_COMPILE_ERROR, $e);
            }
            */
        }
    }

    protected function getCacheObject()
    {
        if (! $this->cacheObject) {
            $this->createCacheObject();
        }
        return $this->cacheObject;
    }

    protected function prepareStatement()
    {
        if (! $this->query) {
            throw new LibraryException("No query object exists", E_COMPILE_ERROR);
        }
        $conn = $this->getDbConnection();
        $this->statement = $conn->prepare($this->query);
        if (! $this->statement) {
            throw new LibraryException("Unable to prepare statement [{$conn->errno}]: {$conn->error}", E_COMPILE_ERROR);
        }
        return $this;
    }

    protected function bindParams()
    {
        if (! $this->statement) {
            throw new LibraryException("No statement object exists", E_COMPILE_ERROR);
        }
        if ($this->bindParams && $this->bindTypes) {
            array_unshift($this->bindParams, $this->bindTypes);
            if (! call_user_func_array(array(
                $this->statement , 'bind_param'
            ), $this->arrayByReference($this->bindParams))) {
                throw new LibraryException("Unable to bind statement params [{$this->statement->errno}]: {$this->statement->error}", E_COMPILE_ERROR);
            }
        }
        return $this;
    }

    protected function executeStatement($type, $doNotCache = false)
    {
        if (! $this->statement) {
            throw new LibraryException("No statement object exists", E_COMPILE_ERROR);
        }
        if ($this->statement->execute()) {
            switch ($type) {
                case 'get':
                case 'getAll':
                case 'getRow':
                    $this->statement->store_result();
                    $meta = $this->statement->result_metadata();
                    $data = array();
                    $row = array();
                    while ($field = $meta->fetch_field()) {
                        $params[] = &$row[$field->name];
                    }
                    call_user_func_array(array(
                        $this->statement , "bind_result"
                    ), $params);
                    while ($this->statement->fetch()) {
                        foreach ($row as $key => $val) {
                            $c[$key] = $val;
                        }
                        if ($this->rowsAsObjects) {
                            $data[] = new DbModel_Result($c);
                        } else {
                            $data[] = $c;
                        }
                    }
                    if ($this->cacheResults && ! $doNotCache) {
                        $this->getCacheObject()
                             ->save($data, $this->getCacheId());
                    }
                    $spec = $data;
                    $meta->free();
                    break;
                    
                case 'insert':
                    if ($this->cacheResults) {
                        $this->getCacheObject()
                             ->clean('all');
                    }
                    $spec = $this->statement->insert_id;
                    break;
                    
                case 'update':
                case 'delete':
                    if ($this->cacheResults) {
                        $this->getCacheObject()
                             ->clean('all');
                    }
                    $spec = $this->statement->affected_rows;
                    break;
                    
                default:
                    break;
            }
            //TODO: Should we let the statement stay open if we'll want to bind other params and re-execute?
            $this->statement->close();
            $this->rewind();
            return $spec;
        } else {
            throw new LibraryException("Unable to execute statement [{$this->statement->errno}]: {$this->statement->error}", E_COMPILE_ERROR);
        }
    }

    protected function rewind()
    {
		$this->statement 		 = false;
        $this->transactionActive = false;
        $this->query 			 = "";
        $this->bindParams 		 = array();
        $this->bindTypes 		 = "";
		return $this;
    }
}