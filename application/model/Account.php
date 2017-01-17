<?php
/**
 * account table and functions
 *
 * @author mmorrison
 */

class Model_Account {
    /**
     * @var Model_Account
     */
    private static $_instance = null;

    /**
     * Get class singleton.
     * @return Model_Account
     */
    public static function getInstance() {
        if(is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Get account data by ID.
     * @param int $id
     * @return Model_Obj_Account|boolean false if nothing found
     */
    public function getById($id) {
        $DB = Util_DB::getInstance();
        $result = $DB->query('select * from account where id = :id', array(':id' => $id))->fetchOne();

        if($result === false) {
            return false;
        }

        return new Model_Obj_Account($result);
    }

    /**
     * Get account information by account name.  If no account found, then false is returned.
     * @param string $name
     * @return Model_Obj_Account|boolean false if not found
     */
    public function getByName($name) {
        $DB = Util_DB::getInstance();
        $result = $DB->query("select * from account where name like :name", array(':name' => $name))->fetchOne();

        if($result === false) {
            return false;
        }

        return new Model_Obj_Account($result);
    }

    /**
     * Create an account.
     * $row requirements: name, password, email, acl, source_id
     * @param array $row
     * @return array ('success' => boolean, 'message' => string)
     */
    public function create(array $row) {
        if(!isset($row['name']) || !ctype_alnum($row['name'])) {
            return array('success' => false, 'message' => 'Name must be an alphanumeric strings');
        }
        if(!isset($row['password'])) {
            return array('success' => false, 'message' => 'Password is required');
        }
        if(!isset($row['email'])) {
            return array('success' => false, 'message' => 'Email is required');
        }
        if(!isset($row['acl'])) {
            return array('success' => false, 'message' => 'ACL is required');
        }
        if(!isset($row['source_id'])) {
            return array('success' => false, 'message' => 'Source ID is required');
        }

        if($this->getByName($row['name']) !== false) {
            return array('success' => false, 'message' => 'Name already exists');
        }

        $DB = Util_DB::getInstance();
        $Bcrypt = new Util_Bcrypt();

        $password_salt = $Bcrypt->getSalt();
        $password_hash = crypt($row['password'], $password_salt);

        $DB->query("
            insert into account (name, password, password_salt, email, acl, source_id, created_date, updated_date)
            values (
                :name,
                :password,
                :password_salt,
                :email,
                :acl,
                :source_id,
                NOW(),
                NOW()
            )",
            array(
                ':name' =>          ucfirst(strtolower(trim($row['name']))),
                ':password' =>      $password_hash,
                ':password_salt' => $password_salt,
                ':email' =>         trim($row['email']),
                ':acl' =>           $row['acl'],
                ':source_id' =>     $row['source_id'],
            )
        );

        return array('success' => true, 'message' => 'Account created');
    }
}
