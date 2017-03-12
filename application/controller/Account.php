<?php
/**
 * Account controller
 *
 * @author mmorrison
 */
class Controller_Account {
    public function init() { }

    public function loginAction() {
        $HTML = new View_HTML();
        $login = filter_input(INPUT_POST, 'login', FILTER_SANITIZE_STRING);
        $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

        if(!$login) {
            $HTML->redirect('/index', array('error' => 'Login name must be a string'));
            die;
        }
        if(!$password) {
            $HTML->redirect('/index', array('error' => 'Password must be a string'));
            die;
        }

        $accountObj = Model_Obj_Account::getBy('name', $login);
        if($accountObj === false) {
            $HTML->redirect('/index', array('error' => 'Account does not exist'));
            die;
        }

        $hash = crypt($password, $accountObj->password_salt);

        if(strcmp($hash, $accountObj->password)) {
            $HTML->redirect('/index', array('error' => 'Invalid password'));
            die;
        }

        // Login validated, lets remove password info before saving into session
        unset($accountObj->password, $accountObj->password_salt);

        $_SESSION['account_id'] = $accountObj->id;

        $HTML->redirect('/index');
    }

    public function logoutAction() {
        $HTML = new View_HTML();

        if(isset($_SESSION['account'])) {
            session_destroy();
        }

        $HTML->redirect('/index');
        die;
    }

    public function createAction() {
        $HTML = new View_HTML();

        $name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING));
        $password = trim(filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING));
        $email = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL));

        if(!$name || !ctype_alnum($name)) {
            $HTML->redirect('/index', array('error' => 'Name must be an alphanumeric strings'));
            die;
        }
        if(!$password) {
            $HTML->redirect('/index', array('error' => 'Password must be a string'));
            die;
        }
        if(!$email) {
            $HTML->redirect('/index', array('error' => 'No email provided or invalid email'));
            die;
        }

        $Account = Model_Account::getInstance();

        $result = $Account->create(array(
            'name' => $name,
            'password' => $password,
            'email' => $email,
            'acl' => 'User',
        ));

        if($result['success'] === false) {
            $HTML->redirect('/index', array('error' => $result['message']));
            die;
        }

        $HTML->redirect('/index', array('success' => "User {$name} created!"));
        die;
    }

    public function profileAction() {
        $HTML = new View_HTML();
        echo $HTML->render('Account/Profile.php');
    }
}
