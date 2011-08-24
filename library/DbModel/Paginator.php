<?php
/**
 * DbModel_Paginator.
 *
 * Enables pagination of results. It's recommended that you use results caching (see {@see DbModel::cacheResults}, {@see DbModel::enableCaching})
 * whenever you are paginating to reduce load on the database server.
 *
 * @author Joel Mukuthu
 * @copyright (c) 2010 Fiqne
 * @package Fiqne_MVC_Framework
 * @subpackage DbModel
 */
class DbModel_Paginator
{
    /**
     * The page number to go to. This will be passed via URL (query string) params; if not given, defaults to 1.
     * @var int
     */
    protected $page = 1;

    /**
     * Number of results per page. Also passed via URL params. Defaults to 10.
     * @var int
     */
    protected $recordsPerPage;

    /**
     * The name of the view script file that contains the pagination controls.
     * @var string
     */
    protected $scriptFile;

    /**
     * The string to display on the pagination controls e.g. 'Items 1 - 10 of 10,000'. In this example, $item is 'Items'.
     * You can choose not to use this property.
     * @var unknown_type
     */
    protected $item = '';

    /**
     * Stores the paginated records i.e. a subset of the full result set.
     * @var array
     */
    protected $records = array();

    /**
     * Total number of pages.
     * @var int
     */
    protected $pageCount = 0;

    /**
     * Total number of records.
     * @var int
     */
    protected $recordCount = 0;

    /**
     * The (index of the) first record.
     * @var int
     */
    protected $firstRecord = 0;

    /**
     * The (index of the) last record.
     * @var int
     */
    protected $lastRecord = 0;

    /**
     * The (index of the) current page. Defaults to 1.
     * @var int
     */
    protected $current = 1;

    /**
     * The (index of the) next page.
     * @var int
     */
    protected $next = 0;

    /**
     * The (index of the) previous page.
     * @var int
     */
    protected $previous = 0;

    /**
     * The (index of the) first page. Always 1.
     * @var int
     */
    protected $first = 1;

    /**
     * The (index of the) last page.
     * @var int
     */
    protected $last;

    /**
     * The page number to start looping from when displaying page links.
     * @var int
     */
    protected $pageStart = 1;

    /**
     * The page number to end looping at when displaying page links.
     * @var int
     */
    protected $pageEnd = 10;

    /**
     * Class constructor. Handles all the pagination logic.
     * 	Note that the pagination logic expects these as the keys for pagination offset and results per page:
     * 		-> Offset, within record set, to start displaying from - 'page'
     * 		-> Results per page - 'per-page'
     * 	These values for these keys are to be passed via the URL and are thus reserved as parameter keys when
     *  pagination is used.
     *
     * @param array $results The entire record set to paginate.
     * @param string $item If you wish to use {@see DbModel_Paginator::item}, pass the item name as
     * 	this parameter.
     * @param int $recordsPerPage The number of records to show per page.
     * @param string $scriptFile The filename to the view script that contains pagination controls. If not passed,
     *        this constructor will try to get a file named 'paginator.phtml' in the layouts folder of the current
     *        module (be it 'pc' or 'mobi').
     *
     * @throws LibraryException If param $resuult is false, empty, or null.
     */
    public function __construct(array $results, $item = 'Records', $recordsPerPage = 10, $scriptFile = '')
    {
        if (!$scriptFile) {
            $route = Application::getInstance()->getRouter()->getRoute();
            $scriptFile = ROOT 
                        . DIRECTORY_SEPARATOR 
                        . 'application' 
                        . DIRECTORY_SEPARATOR 
                        . $route['module'] 
                        . DIRECTORY_SEPARATOR 
                        . 'layouts' 
                        . DIRECTORY_SEPARATOR 
                        . 'paginator.phtml';
        }
        if (!Loader::isSecure($scriptFile)) {
            throw new LibraryException("The paginator filename '{$scriptFile}' contains illegal characters", E_USER_ERROR);
        }
        if (!is_readable($scriptFile)) {
            throw new LibraryException("Could not access paginator filename '{$scriptFile}'. It may not exist or is not readable", E_COMPILE_ERROR);
        }
        $this->scriptFile = $scriptFile;
        $this->recordsPerPage = (int) $recordsPerPage;
        $this->item = (string) $item;
        if (!$results) {
            throw new LibraryException("Invalid data provided to DbModel_Paginator", E_COMPILE_ERROR);
        }
        $router  = Application::getInstance()->getRouter();
        $perPage = $router->getParam('per-page');
        $page    = $router->getParam('page');
        if (!is_bool($perPage) && $perPage > 0) {
            $this->recordsPerPage = $perPage;
        }
        if (!is_bool($page) && $page > 0) {
            $this->page = $page;
        }
        $this->recordCount = count($results);
        if ($this->page <= 1) {
            $offset = 0;
        } else {
            $offset = ($this->page - 1) * $this->recordsPerPage;
            if ($offset >= $this->recordCount) {
                $offset = 0;
            }
        }
        $this->records     = array_slice($results, $offset, $this->recordsPerPage);
        $this->firstRecord = $offset ==  0 ? 1 : $offset + 1;
        $this->lastRecord  = $this->firstRecord + count($this->records) - 1;
        if ($this->lastRecord > $this->recordCount) {
            $this->lastRecord = $this->recordCount;
        }
        $this->pageCount = intval($this->recordCount / $this->recordsPerPage);
        if ($this->recordCount % $this->recordsPerPage) {
        	$this->pageCount++;
        }
        $this->current = intval($offset / $this->recordsPerPage) + 1;
        if ($offset != 0) {
            $this->previous = $this->current - 1;
        }
        if (($offset + $this->recordsPerPage) < $this->recordCount) {
            $this->next = $this->current + 1;
        }
        $this->last      = $this->pageCount;
        $pageIndex       = intval($this->current / 10);
        $this->pageStart = $pageIndex == 0 ? 1 : $pageIndex * 10;
        $pageIndex       = intval(($this->pageStart + 10) / 10) * 10;
        $this->pageEnd   = $this->pageCount <= $pageIndex ? $this->pageCount : $pageIndex;
    }

    /**
     * Get the content of the paginator view script {@see DbModel_Paginator::scriptFile}. Uses bufferring (
     * {@see PHP_MANUAL#ob_start}, {@see PHP_MANUAL#ob_get_clean}).
     *
     * @uses Loader::isSecure
     * @uses View::url To display links (or other view operations) in the paginator script.
     *
     * @return string
     */
    public function getScript()
    {
        //create view object for use within paginator script
        $view = new View();
        ob_start();
        include $this->scriptFile;
        return ob_get_clean();
    }

    /**
     * Get paginated records.
     *
     * @return array
     */
    public function getRecords()
    {
        return $this->records;
    }
}