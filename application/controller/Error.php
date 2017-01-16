<?php
/**
 * Description of Error
 *
 * @author mmorrison
 */
class Controller_Error {
    /**
     * @var Controller_Error
     */
    private static $_instance = null;

    /**
     * Get class singleton.
     * @return Controller_Error
     */
    public static function getInstance() {
        if(is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Initialization -- this is called before the action.
     */
    public function init() {
    }

    public function indexAction($error) {
        $Log = Util_Log::getInstance();
        $Log->log(Util_Log::ERROR, $error);
    }

    public function exceptionHandler($exception) {
        echo $exception;
    }
}
