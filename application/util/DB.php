<?php
/**
 * Database class.
 *
 * @author mmorrison
 */

class DB extends PDO {
    /**
     * @var PDOStatement
     */
    private $_stmt = null;
    private $_queryString = null;
    private $_queryParams = null;

    /**
    * @var Util_DB[]
    */
    private static $_database_instances = array();

    /**
     * TODO FIX -- don't connect on first instance, only connect on query
     * Get singleton object for a particular database connection.  You may have multiple unique
     * connections to a single database by using the $unique_id parameter.
     * @param string $database  Name of the database from the configuration file
     * @param string $unique_id A unique identifier for the connection
     * @return Util_DB
     * @throws Exception
     */
    public static function getInstance($database = 'main', $unique_id = '') {
        $local_database_id = $database.$unique_id;

        if(!isset(self::$_database_instances[$local_database_id])) {
            $databases = config('databases');

            if(!isset($databases[$database])) {
                throw new Exception("No database found [$database]");
            }

            $database			= $databases[$database];
            $connect_string		= "mysql:host={$database['host']};dbname={$database['database']}";
            $pdo				= new self($connect_string, $database['user'], $database['password']);

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            self::$_database_instances[$local_database_id] = $pdo;
        }
        return self::$_database_instances[$local_database_id];
    }

    /**
     * Returns the last query string ran, or null if none.
     * @return string|null
     */
    public function getQueryString() {
        return preg_replace_callback('/:([0-9a-z_]+)/i', array($this, 'queryReplace'), $this->_queryString);
    }

    /**
     * Callback function for getQueryString.
     * @param array $params
     * @return string
     */
    private function queryReplace($params)
    {
        if(!isset($this->_queryParams[$params[0]])) {
            return "''";
        }

        $var = $this->_queryParams[$params[0]];

        if(!is_numeric($var)) {
            $var = str_replace("'", "''", $var);
        }

        return "'$var'";
    }

    /**
     * With no params provided it will call PDO::query, else it creates a prepared statement.
     * With no params the $sql needs to be properly escaped via quote() or escape()
     * If params are provided they should be colon-identified, ie:
     *    $sql=select * from account where id = :id
     *    $params = array(':id' => $some_id)
     * @param string $sql
     * @param array  $params
     * @return Util_DB
     */
    public function query($sql, array $params=array()) {
        if(count($params) == 0) {
            $this->_stmt = parent::query($sql);
        } else {
            $stmt = parent::prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
            $stmt->execute($params);
            $this->_stmt = $stmt;
        }

        // Debugging stuff
        $this->_queryString = $this->_stmt->queryString;
        $this->_queryParams = $params;

        $Log = Util_Log::getInstance();
        if($Log->willLog(Util_Log::DEBUG)) {
            $Log->log(Util_Log::DEBUG, "[SQL] ".$this->getQueryString());
        }

        return $this;
    }

    /**
     * Get an associated array of rows from recent query.
     * @return array[]
     * @throws Exception
     */
    public function fetchAll() {
        if(is_null($this->_stmt)) {
            throw Exception("fetchAll called with a null statement");
        }

        return $this->_stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch a single result from a query.
     * If no result returned, then false.
     * @return array|boolean false if no record returned
     * @throws Exception
     */
    public function fetchOne() {
        if(is_null($this->_stmt)) {
            throw Exception("fetchOne called with a null statement");
        }

        $results = $this->fetchAll();
        return count($results) > 0 ? $results[0] : false;
    }

    /**
     * Fetches a single column from the next row.
     * @param int $column_number which column to fetch (by index #)
     * @return string
     * @throws Exception
     */
    public function fetchColumn($column_number = 0) {
        if(is_null($this->_stmt)) {
            throw Exception("fetchColumn called with a null statement");
        }

        return $this->_stmt->fetchColumn($column_number);
    }

    /**
     * Escape a string -- calls the PDO::quote function.
     * @param string $string
     * @return string
     */
    public function escape($string) {
        return $this->quote($string);
    }
}
