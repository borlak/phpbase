<?php
/**
 * Index (root) controller
 *
 * @author mmorrison
 */
class Controller_Index {
    /**
     * Initialization -- this is called before the action.
     */
    public function init() {
    }

    public function indexAction() {
        $HTML = new View_HTML();
        $HTML->getRedirectedData();
        echo $HTML->render('index/index.php');
    }

    public function signinAction() {
        $HTML = new View_HTML();
        $HTML->getRedirectedData();
        echo $HTML->render('index/signin.php');
    }

    public function createAction() {
        return $this->signinAction();
    }

    public function phpinfoAction() {
        echo phpinfo();
    }
}
