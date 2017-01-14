<?php
/**
 * Utility functions for application
 *
 * @author mmorrison
 */
class Util {
    /**
     * @var \Util\Util
     */
    private static $_instance = null;

    /**
     * Get class singleton.
     * @return \Util\Util
     */
    public static function getInstance() {
        if(is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Gets current memory usage in human readable foramt.
     * @return string
     */
    public function getMemoryUsage() {
        return $this->byteConvert(memory_get_usage(true));
    }

    /**
     * Converts bytes to human readable format.
     * @param int $size
     * @return string
     */
    public function byteConvert($size) {
        $unit=array('b','kb','mb','gb','tb','pb');
        return round($size/pow(1024,($i=floor(log($size,1024)))),2).$unit[$i];
    }
}
