<?php
/**
 * Redis class
 *
 * Local cache -> a key/value array in the cache class.  doesn't persist between PHP processes
 * Redis -> persistent key/value store shared between any/all servers.
 *
 * @author mmorrison
 */
class Util_Redis extends Redis {
    private $_local_cache = array();
    private $_local_cache_enabled = true;
    private $_redis_enabled = true;

    /**
     * @var \Redis
     */
    private static $_redis = null;

    const DEFAULT_TIMEOUT = 86400;

    /**
     * @var Util_Redis
     */
    private static $_instance = null;

    /**
     * Get class singleton.
     * @return Util_Redis
     */
    public static function getInstance() {
        if(is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        parent::__construct();

        $this->setupRedis();

        $this->_local_cache_enabled = config('cache.local.enabled');
        $this->_redis_enabled   = config('cache.redis.enabled');

        $Log = Util_Log::getInstance();
        $Log->log(Util_Log::DEBUG,
            'Cache:: Local(' .($this->_local_cache_enabled ? 'Enabled' : 'Disabled').
            ') Redis('   .($this->_redis_enabled   ? 'Enabled' : 'Disabled').
            ')'
        );
    }

    /**
     * Gets the redis config and connects.
     */
    private function setupRedis() {
        $server = config('cache.redis.server');
        if(!$server || !is_array($server)) {
            throw new Exception("Redis server is not setup");
        }

        if(!parent::connect($server['host'], $server['port'])) {
            throw new Exception("Redis server failed to connect host[{$server['host']}] port[{$server['port']}]");
        }

        parent::setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_IGBINARY);
    }

    /**
     * Enable Local Cache.  Enabled by default.  Config setting (mostly for testing)
     */
    public function enableLocalCache() {
        $this->_local_cache_enabled = true;
    }
    /**
     * Disable Local Cache.  Enabled by default.  Config setting (mostly for testing)
     */
    public function disableLocalCache() {
        $this->_local_cache_enabled = false;
    }

    /**
     * First checks local cache (saved in class) and then redis if available.
     *
     * TODO FIX: flag for not using local cache -- you may want to get any potential updates from redis
     *
     * @param string   $key
     * @return mixed|boolean false if not found
     */
    public function get($key) {
        $Log = Util_Log::getInstance();

        if(!is_string($key)) {
            $Log->log(Util_Log::ERROR, 'Cache key is not a string! -- '.var_export($key,true));
            return false;
        }

        if($this->_local_cache_enabled && isset($this->_local_cache[$key])) {
            $Log->log(Util_Log::DEBUG, "Cache get [$key] by local cache");
            return $this->_local_cache[$key];
        }

        if($this->_redis_enabled) {
            $result = parent::get($key);
            $Log->log(Util_Log::DEBUG, "Cache get [$key] by Redis");

            if($this->_local_cache_enabled && $result !== false) {
                $this->_local_cache[$key] = $result;
            }
            return $result;
        }

        $Log->log(Util_Log::DEBUG, "Cache get [$key] Failure");
        return false;
    }

    /**
     * Get multiple keys at once.
     * @param array $keys
     * @return array|boolean false if not successful, else an array of keys found
     */
    public function getMulti(array $keys) {
        $Log = Util_Log::getInstance();
        $results = array();

        foreach($keys as $key) {
            if(!is_string($key)) {
                $Log->log(Util_Log::ERROR, 'Redis getMulti key is not a string! -- '.var_export($key,true));
            }
        }

        if($this->_local_cache_enabled) {
            $found_keys = array();
            foreach($keys as $key) {
                if(isset($this->_local_cache[$key])) {
                    $results[$key] = $this->_local_cache[$key];
                    unset($keys[$key]);
                    $found_keys[] = $key;
                }
            }
            $Log->log(Util_Log::DEBUG, 'Cache getMulti ['.implode(',',$found_keys).'] by local cache');

            // did we find all keys?  if so then return, otherwise keep looking...
            if(count($keys) == 0) {
                return $results;
            }
        }

        if($this->_redis_enabled) {
            $result = parent::getMultiple($keys);

            $found_keys = array();
            foreach($result as $key => $value) {
                if($this->_local_cache_enabled) {
                    $this->_local_cache[$key] = $value;
                }
                $results[$key] = $value;
                $found_keys[] = $key;
            }

            $Log->log(Util_Log::DEBUG, 'Cache getMulti ['.implode(',',$found_keys).'] by Redis');
        }

        if(count($results) == 0) {
            $Log->log(Util_Log::DEBUG, 'Cache multiGet ['.implode(',',$keys).'] Failure');
            return false;
        }

        return $results;
    }

    /**
     * Save first in local cache (to the class), and to redis if available.
     * Returns true if all sets were successful.
     * @param string $key
     * @param mixed $data
     * @param int $timeout
     * @return mixed
     */
    public function set($key, $data, $timeout = self::DEFAULT_TIMEOUT) {
        $Log = Util_Log::getInstance();
        $redis_result = true;

        if(!is_string($key)) {
            $Log->log(Util_Log::ERROR, 'Cache key is not a string! -- '.var_export($key,true));
            return false;
        }

        if($this->_local_cache_enabled) {
            $this->_local_cache[$key] = $data;
        }

        if($this->_redis_enabled) {
            $redis_result = parent::set($key, $data, $timeout);
        }

        $Log->log(Util_Log::DEBUG, "Cache set [$key] ".($redis_result ? 'Success' : 'Failure'));

        return $redis_result;
    }

    /**
     * Deletes a key from all enabled caches.
     * @param string $key
     * @return boolean
     */
    public function delete($key) {
        $Log = Util_Log::getInstance();
        $redis_result = true;

        if(!is_string($key)) {
            $Log->log(Util_Log::ERROR, 'Cache key is not a string! -- '.var_export($key,true));
            return false;
        }

        if($this->_local_cache_enabled) {
            unset($this->_local_cache[$key]);
        }

        if($this->_redis_enabled) {
            $redis_result = parent::del($key);
        }

        return $redis_result;
    }

    /**
     * Increment $key by $amount.  If $key doesn't exist, it will set the value to 0+$amount.
     * @param string $key
     * @param int    $amount
     * @param int    $initial_value
     * @param int    $flags
     * @return int   returns the new value, or false
     */
    public function increment($key, $amount = 1, $initial_value = 1) {
        $Log = Util_Log::getInstance();

        if($this->_redis_enabled) {
            $new_value = parent::incrByFloat($key, $amount);
        }

        $Log->log(Util_Log::DEBUG, "Cache increment [$key] = ". ($new_value === false ? 'Failure' : $new_value));

        return $new_value === false ? 0 : $new_value;
    }

    /**
     * Decrement $key by $amount.  If they key doesn't exist, it will set the value to 0 - $amount.
     * @param string $key
     * @param int    $amount
     * @return int   returns the new value, or false
     */
    public function decrement($key, $amount = 1) {
        $Log = Util_Log::getInstance();
        $new_value = false;

        if($new_value === false && $this->_redis_enabled) {
            $new_value = parent::decrBy($key, $amount);
        }

        $Log->log(Util_Log::DEBUG, "Cache decrement [$key] = ". ($new_value === false ? 'Failure' : $new_value));

        return $new_value === false ? 0 : $new_value;
    }
}
