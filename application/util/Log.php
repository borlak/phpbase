<?php
/**
 * Logging utility
 *
 * @author mmorrison
 */
class Util_Log {
    private $_log_file_location = null;
    private $_log_level = self::INFO;
    private $_lines = array();
    const ERROR     = 1;
    const INFO      = 2;
    const DEBUG     = 3;
    const VERBOSE   = 4;
    const MAX_LINES = 2000;

    /**
     * @var \Util\Log
     */
    private static $_instance = null;

    /**
     * Get class singleton.
     * @return \Util\Log
     */
    public static function getInstance() {
        if(is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * @param int $log_level class constant
     */
    public function __construct($log_level = self::INFO) {
        $this->_log_file_location = ini_get('error_log');

        if(empty($this->_log_file_location)) {
            throw new Exception('No error_log set in php configuration, logging fail.');
        }

        $this->_log_level = $log_level;
    }

    /**
     * Set the log level
     * @param int $log_level class constant
     */
    public function setLogLevel($log_level) {
        $this->_log_level = $log_level;
    }

    /**
     * Check if the logger will log based on the passed in level.
     * @param int $log_level
     * @return boolean
     */
    public function willLog($log_level) {
        return $this->_log_level >= $log_level;
    }

    /**
     * Outputs to PHP logger.
     * @param int $level class constant ::ERROR,INFO,DEBUG,VERBOSE
     * @param string $message
     */
    public function log($level, $message) {
        if(!$this->willLog($level)) {
            return;
        }

        $error_data = '';
        switch($level) {
            case self::ERROR:
                $level_str = "Error";
                $error_data = $this->getBacktrace();
                break;
            case self::INFO:    $level_str = "Info";    break;
            case self::DEBUG:   $level_str = "Debug";   break;
            case self::VERBOSE: $level_str = "Verbose"; break;
            default:            $level_str = "Unknown"; break;
        }
        $date_str = date('d-m-Y h:i:s');
        $message = "[$date_str] [$level_str] :: $message\n";
        $message .= $error_data;

        // 3 = set your own log location, which we are just using the default.  have to
        // do this for our custom date formatting, otherwise the date shows up twice.
        error_log($message, 3, $this->_log_file_location);

        if(count($this->_lines) < self::MAX_LINES) {
            $this->_lines[] = $message;
        }
    }

    /**
     * Calls PHP debug_backtrace() and outputs a user-friendly string.
     * Ignores this class' functions log and getBacktrace, so uh, don't ever break those!
     * @return string
     */
    private function getBacktrace() {
        $backtraces = debug_backtrace();

        $string = "--Backtrace--\n";
        if(count($backtraces) > 0) {
            foreach($backtraces as $backtrace) {
                // Don't show the log or getBacktrace function being called..
                if($backtrace['class'] == 'Util\Log'
                && ($backtrace['function'] == 'log' || $backtrace['function'] == 'getBacktrace')) {
                    continue;
                }

                $string .= "File {$backtrace['file']} - Line {$backtrace['line']} - Function [{$backtrace['function']}]\n";
            }
        }

        return $string;
    }

    public function displayHTML() {
        $lines = 0;

        echo "<pre>::Log Output for level {$this->_log_level}::\n";
        foreach($this->_lines as $line) {
            $lines++;

            echo $line;

            if($lines >= self::MAX_LINES) {
                echo "<br>...truncated...<br>";
            }
        }
        echo "</pre>";
    }
}
