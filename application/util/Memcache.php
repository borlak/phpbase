<?php
/**
 * Memcache class
 *
 * Local cache -> a key/value array in the cache class.  doesn't persist between PHP processes
 * Memcached -> persistent key/value store shared between any/all servers.
 *
 * @author mmorrison
 */
class Util_Memcache {
    private $_local_cache = array();
    private $_local_cache_enabled = true;
    private $_memcached_enabled = true;

    /**
     * @var \Memcached
     */
    private static $_memcached = null;

    const DEFAULT_TIMEOUT = 86400;
    // bit flags
    const COMPRESS  = 0x1;

    /**
     * @var Util_Memcache
     */
    private static $_instance = null;

    /**
     * Get class singleton.
     * @return Util_Memcache
     */
    public static function getInstance() {
        if(is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        $this->setupMemcached();

        $this->_local_cache_enabled = config('cache.local.enabled');
        $this->_memcached_enabled   = config('cache.memcached.enabled');

        $Log = Util_Log::getInstance();
        $Log->log(Util_Log::DEBUG,
            'Cache:: Local(' .($this->_local_cache_enabled ? 'Enabled' : 'Disabled').
            ') Memcached('   .($this->_memcached_enabled   ? 'Enabled' : 'Disabled').
            ')'
        );
    }

    /**
     * Gets the memcached config and adds all the servers.
     */
    private function setupMemcached() {
        $servers = config('cache.memcached.servers');

        if($servers !== false && count($servers) > 0) {
            self::$_memcached = new \Memcached();
            foreach($servers as $server) {
                if(self::$_memcached->addServer($server['host'], $server['port']) === false) {
                    throw new \Exception("Unable to add memcached server {$server['host']}:{$server['port']}");
                }
            }
            if(config('cache.memcached.compression') === true) {
                self::$_memcached->setOption(\Memcached::OPT_COMPRESSION, true);
            }
            if(config('cache.memcached.hash_consistent') === true) {
                self::$_memcached->setOption(\Memcached::DISTRIBUTION_CONSISTENT, true);
                self::$_memcached->setOption(\Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
            }
        }
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
     * Enable Memcached.  Enabled by default.  Config setting (mostly for testing)
     */
    public function enableMemcached() {
        $this->_memcached_enabled = true;
    }
    /**
     * Disable Memcached.  Enabled by default.  Config setting (mostly for testing)
     */
    public function disableMemcached() {
        $this->_memcached_enabled = false;
    }

    /**
     * First checks local cache (saved in class) and then memcached if available.
     *
     * TODO FIX: flag for not using local cache -- you may want to get any potential updates from memcache
     *
     * @param string   $key
     * @param bitfield $flags
     * @return mixed|boolean false if not found
     */
    public function get($key, $flags = 0) {
        $Log = Util_Log::getInstance();

        if(!is_string($key)) {
            $Log->log(Util_Log::ERROR, 'Cache key is not a string! -- '.var_export($key,true));
            return false;
        }

        if($this->_local_cache_enabled && isset($this->_local_cache[$key])) {
            $Log->log(Util_Log::DEBUG, "Cache get [$key] by local cache");
            return $this->_local_cache[$key];
        }

        if($this->_memcached_enabled && !is_null(self::$_memcached)) {
            $result = self::$_memcached->get($key);
            $Log->log(Util_Log::DEBUG, "Cache get [$key] by Memcached");

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
     * @param int   $flags
     * @return array|boolean false if not successful, else an array of keys found
     */
    public function getMulti(array $keys, $flags = 0) {
        $Log = Util_Log::getInstance();
        $results = array();

        if(!is_string($key)) {
            $Log->log(Util_Log::ERROR, 'Cache key is not a string! -- '.var_export($key,true));
            return false;
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

        if($this->_memcached_enabled && !is_null(self::$_memcached)) {
            $result = self::$_memcached->getMulti($keys);

            $found_keys = array();
            foreach($result as $key => $value) {
                if($this->_local_cache_enabled) {
                    $this->_local_cache[$key] = $value;
                }
                $results[$key] = $value;
                $found_keys[] = $key;
            }

            $Log->log(Util_Log::DEBUG, 'Cache getMulti ['.implode(',',$found_keys).'] by Memcached');
        }

        if(count($results) == 0) {
            $Log->log(Util_Log::DEBUG, 'Cache multiGet ['.implode(',',$keys).'] Failure');
            return false;
        }

        return $results;
    }

    /**
     * Save first in local cache (to the class), and to memcached if available.
     * Returns true if all sets were successful.
     * @param string $key
     * @param mixed $data
     * @param int $timeout
     * @param boolean $compress
     * @return mixed
     */
    public function set($key, $data, $timeout = self::DEFAULT_TIMEOUT, $flags = 0) {
        $Log = Util_Log::getInstance();
        $memcached_result = true;

        if(!is_string($key)) {
            $Log->log(Util_Log::ERROR, 'Cache key is not a string! -- '.var_export($key,true));
            return false;
        }

        if($this->_local_cache_enabled) {
            $this->_local_cache[$key] = $data;
        }

        if($this->_memcached_enabled) {
            if(!is_null(self::$_memcached)) {
                $memcached_result = self::$_memcached->set($key, $data, $timeout);
            }
        }

        $Log->log(Util_Log::DEBUG, "Cache set [$key] ".($memcached_result ? 'Success' : 'Failure'));

        return $memcached_result;
    }

    /**
     * Deletes a key from all enabled caches.
     * @param string $key
     * @return boolean
     */
    public function delete($key) {
        $Log = Util_Log::getInstance();
        $memcached_result = true;

        if(!is_string($key)) {
            $Log->log(Util_Log::ERROR, 'Cache key is not a string! -- '.var_export($key,true));
            return false;
        }

        if($this->_local_cache_enabled) {
            unset($this->_local_cache[$key]);
        }

        if($this->_memcached_enabled) {
            if(!is_null(self::$_memcached)) {
                $memcached_result = self::$_memcached->delete($key);
            }
        }

        return $memcached_result;
    }

    /**
     * Increment $key by $amount.  If $key doesn't exist, it will set the value to $initial_value.  Resets
     * the timer to DEFAULT_TIMEOUT.
     * @param string $key
     * @param int    $amount
     * @param int    $initial_value
     * @param int    $flags
     * @return int   returns the new value, or false
     */
    public function increment($key, $amount = 1, $initial_value = 1, $flags = 0) {
        $Log = Util_Log::getInstance();
        $new_value = false;

        if($new_value === false && $this->_memcached_enabled) {
            if(!is_null(self::$_memcached)) {
                $new_value = self::$_memcached->increment($key, $amount, $initial_value, self::DEFAULT_TIMEOUT);
            }
        }

        $Log->log(Util_Log::DEBUG, "Cache increment [$key] = ". ($new_value === false ? 'Failure' : $new_value));

        return $new_value === false ? 0 : $new_value;
    }

    /**
     * Decrement $key by $amount.  If they key doesn't exist, it will set the value to $intial_value.  Resets
     * the timer to DEFAULT_TIMEOUT.
     * @param string $key
     * @param int    $amount
     * @param int    $initial_value
     * @param int    $flags
     * @return int   returns the new value, or false
     */
    public function decrement($key, $amount = 1, $initial_value = 0, $flags = 0) {
        $Log = Util_Log::getInstance();
        $new_value = false;

        if($new_value === false && $this->_memcached_enabled) {
            if(!is_null(self::$_memcached)) {
                $new_value = self::$_memcached->decrement($key, $amount, $initial_value, self::DEFAULT_TIMEOUT);
            }
        }

        $Log->log(Util_Log::DEBUG, "Cache decrement [$key] = ". ($new_value === false ? 'Failure' : $new_value));

        return $new_value === false ? 0 : $new_value;
    }
}
