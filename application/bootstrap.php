<?php

$GLOBALS['script_start_time'] = microtime(true);

// Absolute path to root of application
define('PATH', '/var/www/');
set_include_path(PATH);

// Error Handler with Backtraces
function process_error_backtrace($errno, $errstr, $errfile, $errline, $errcontext) {
    if(!(error_reporting() & $errno)) {
        return;
    }
    switch($errno) {
    case E_WARNING:
    case E_USER_WARNING:
    case E_STRICT:
    case E_NOTICE:
    case E_USER_NOTICE:
        $type = 'warning';
        $fatal = false;
        break;
    default:
        $type = 'fatal error';
        $fatal = true;
        break;
    }

    $trace = array_reverse(debug_backtrace());
    array_pop($trace);

    if(php_sapi_name() == 'cli') {
        echo "Backtrace from {$type} '{$errstr}' at {$errfile} {$errline}:\n";
        foreach($trace as $item) {
            $file = isset($item['file']) ? $item['file'] : '<unknown file>';
            $line = isset($item['line']) ? $item['line'] : '<unknown line>';
            echo "  {$file} {$line} calling {$item['function']}()\n";
        }
    } else {
        echo '<p class="error_backtrace">'."\n";
        echo "  Backtrace from {$type} '{$errstr}' at {$errfile} {$errline}:\n";
        echo "  <ol>\n";
        foreach($trace as $item) {
            $file = isset($item['file']) ? $item['file'] : '<unknown file>';
            $line = isset($item['line']) ? $item['line'] : '<unknown line>';
            echo "    <li>{$file} {$line} calling {$item['function']}()</li>\n";
        }
        echo "  </ol>\n";
        echo "</p>\n";
    }

    if(ini_get('log_errors')) {
        $items = array();
        foreach($trace as $item) {
            $file = isset($item['file']) ? $item['file'] : '<unknown file>';
            $line = isset($item['line']) ? $item['line'] : '<unknown line>';
            $items[] = "{$file} {$line} calling {$item['function']}()";
        }
        $message = "Backtrace from {$type} '{$errstr}' at {$errfile} {$errline}: ".join(' | ', $items);
        error_log($message);
    }

    if($fatal) {
        exit(1);
    }
}
set_error_handler('process_error_backtrace');

// Start session
session_start();

spl_autoload_register(function($class_name) {
    // change Model_Class to model/class.php
    $path = strtolower(str_replace('_', '/', $class_name)).'.php';
    // now capitalize classname
    $pos = strrpos($path, '/')+1;
    $path = substr($path, 0, $pos).strtoupper(substr($path, $pos, 1)).substr($path, $pos+1);

    require $path;
});

require_once 'config/configuration.php';
