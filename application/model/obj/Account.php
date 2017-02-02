<?php
/**
 * Account object
 *
 * @author mmorrison
 */
class Model_Obj_Account extends Model_Obj_Saveable {
    protected static $_table_name = 'account';
    protected static $_database_name = 'main';
    protected static $_cache_keys = array(
        'id' => 1,
        'name' => 1,
        'id_name' => 1,
    );

    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $password;
    /**
     * @var string
     */
    public $password_salt;
    /**
     * @var string
     */
    public $email;
    /**
     * @var string
     */
    public $acl;
    /**
     * @var string
     */
    public $created_date;
    /**
     * @var string
     */
    public $updated_date;
}
