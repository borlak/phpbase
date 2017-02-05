<?php
/**
 * Database Row object
 *
 * Any superclass must define these variables:
 * $_cache_keys -> unique or semi-unique data that can be used to save an object in
 *                 memory and will be used to grab the data from the database.
 * $_table_name -> the table name of the class
 * $_database_name -> the database name of the class
 *
 * This class assumes your tables are setup with a column "id" as the primary key.
 *
 * The getBy function will automatically reach out the cache first and then to the
 * database as a backup, using passed in variables and values.
 *
 * @author mmorrison
 */
class Model_Obj_Saveable {
    protected $_original_values = array();

    /**
     * Makes sure class is setup correctly.
     * PHP does not allow abstract variables, so we did this!
     * @throws Exception
     */
    private static function checkSetup() {
        if(!isset(static::$_table_name)) {
            throw new Exception(get_called_class() . ' must have a static $_table_name');
        }
        if(!isset(static::$_database_name)) {
            throw new Exception(get_called_class() . ' must have a static $_database_name');
        }
        if(!isset(static::$_cache_keys)) {
            throw new Exception(get_called_class() . ' must have a static $_cache_keys');
        }
    }

    /**
     * Setup variables for this Database Row object
     * @param array $row
     * @throws Exception
     */
    final public function __construct(array $row) {
        foreach($row as $key => $value) {
            // non-original values should have some kind of default value
            if(!isset($this->$key)) {
                $this->_original_values[$key] = $value;
            }
            $this->$key = $value;
        }
    }

    /**
     * Returns the corresponding cache key used for getting this object by this variable.
     * @param string $variable
     * @return string|boolean false if no cache key for this variable
     */
    protected function getCacheKey($variable) {
        return isset($this->_cache_keys[$variable]) ? $this->_cache_keys[$variable] : false;
    }

    /**
     * This will update the cache for this object for each key identified by
     * the getCacheKeys() function.
     */
    public function updateCache() {
        $cache_keys = static::$_cache_keys;

        if(count($cache_keys) > 0) {
            $Cache = Util_Redis::getInstance();
            foreach($cache_keys as $variable => $cache_key_proto) {
                if(isset($this->$variable)) {
                    $cache_key = $cache_key_proto.$this->$variable;
                    // If value is changed, delete old key so it can't be found
                    if($this->_original_values[$variable] != $this->$variable) {
                        $Cache->delete($cache_key);
                    }
                    $Cache->set($cache_key, $this);
                }
            }
        }
    }

    /**
     * Automatically fetch from cache or DB the data for this object, the key of which is
     * determined by $variable.  If variable is an array, it will fetch the row based on
     * all keys in the array.  The calling object class must have all keys configured as
     * part of its static $_cache_keys variable.
     * If $forced_lowercase is true [default], the end resulting cache-key is forced lowercase,
     * so same $values but with mixed case will end up pointing to the same cache - (ie. hat and hAt)
     * @param string|array  $variable
     * @param string|int    $value
     * @param boolean       $reload force a load from DB and restore in cache
     * @param boolean       $forced_lowercase force the cache key to be all lowercase?
     * @param string        $unique_id unique ID for the database connection, if needed
     * @return App_Model_Obj_called_class
     * @throws Exception
     */
    public static function getBy($variable, $value, $reload = false, $forced_lowercase = true, $unique_id = false) {
        self::checkSetup();

        $DB = Util_DB::getInstance(static::$_database_name, $unique_id);
        $Cache = Util_Redis::getInstance();
        $called_class = get_called_class();
        $called_class_name = end(explode('\\', $called_class));

        // Cache key = <classname>_<variable(s)>_<value(s)> ie. account_id_name_3_joe
        $cache_key = $called_class_name.'_';
        $where = '';
        $where_array = array();
        if(is_array($variable)) {
            $var_name = implode('_',$variable);

            if(!isset(static::$_cache_keys[$var_name])) {
                throw new Exception("No cache key for variables [$var_name]");
            }

            $cache_key .= $var_name;

            $count = 0;
            foreach($variable as $var) {
                if(!isset($value[$var])) {
                    throw new Exception("Value array is missing value for [$var]");
                }
                if($count++ > 0) {
                    $where .= ' and ';
                }
                $where .= " $var = :$var ";
                $where_array[":$var"] = $value[$var];
                $cache_key .= "_{$value[$var]}";
            }
        } else {
            if(!isset(static::$_cache_keys[$variable])) {
                throw new Exception("No cache key for variable [$variable]");
            }
            $cache_key .= "{$variable}_{$value}";
            $where .= " $variable = :$variable ";
            $where_array[":$variable"] = $value;
        }

        if($forced_lowercase === true) {
            $cache_key = strtolower($cache_key);
        }

        if($reload || ($obj = $Cache->get($cache_key)) === false) {
            $sql = "select * from ".static::$_table_name." where $where";
            $result = $DB->query($sql, $where_array)->fetchOne();

            if($result === false) {
                $obj = '';
            } else {
                $obj = new $called_class($result);
            }

            $Cache->set($cache_key, $obj, Util_Redis::DEFAULT_TIMEOUT);
        }

        return $obj == '' ? false : $obj;
    }

    /**
     * This will automatically save (sql UPDATE) the data associated with the
     * creation of this object through the constructor.  It will only update the
     * differences since its construction.
     * This function assumes your data/tables are setup with "id" as the primary
     * key.
     * You may need to specify $unique_id if you are worried about this function
     * stepping on a current resultset (ie. saving a bunch of objects in a loop).
     * @param string $unique_id
     */
    final public function save($unique_id = false) {
        if($unique_id !== false) {
            $DB = Util_DB::getInstance($this->_database_name, $unique_id);
        } else {
            $DB = Util_DB::getInstance($this->_database_name);
        }

        $updates = array();
        foreach($this->_original_values as $key => $value) {
            if($value != $this->$key) {
                $updates[] = "{$key}=".$DB->escape($this->$key);
                $this->_original_values[$key] = $this->$key;
            }
        }

        if(count($updates) > 0) {
            // first update cache
            $this->updateCache();

            $sql = "update ".$this->_table_name." set ".implode(',', $updates).' where id = :id';

            $DB->query($sql, array(
                ':id' => $this->id
            ));
        }
    }
}
