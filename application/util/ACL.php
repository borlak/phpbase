<?php
/**
 * Description of ACL
 *
 * @author mmorrison
 */
class Util_ACL {
    private $_roles = array();
    private $_permissions = array();
    private $_levels = array();
    private $_parents = array();

    /**
     * @var Util_ACL
     */
    private static $_instance = null;

    /**
     * Get class singleton.
     * @return Util_ACL
     */
    public static function getInstance() {
        if(is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Add a role that levels can get permission to.
     * @param string $role
     */
    public function addRole($role) {
        $this->_roles[$role] = true;
    }

    /**
     * Add a level (ie.User) that you may assign permissions to.
     * @param string $level
     * @param string $parent
     * @throws Exception
     */
    public function addLevel($level, $parent = null) {
        $this->_levels[$level] = true;

        if(!is_null($parent)) {
            if(!isset($this->_levels[$parent])) {
                $this->_levels[$parent] = true;
            }

            $this->_parents[$level] = $parent;
        }
    }

    /**
     * Add a permission to a level.  If $includeParents is true, then
     * all parents (hierarchy) of the level will also receive the permission.
     * @param string $level
     * @param string $role
     * @param boolean $includeParents
     * @throws Exception
     */
    public function addPermission($level, $role, $includeParents = true) {
        if(!isset($this->_levels[$level])) {
            throw new \Exception("Level $level doesn't exist");
        }

        if(!isset($this->_roles[$role])) {
            throw new \Exception("Role $role doesn't exist");
        }

        $this->_permissions[$level][$role] = true;

        if($includeParents === true
        && isset($this->_parents[$level])) {
            $this->addPermission($this->_parents[$level], $role);
        }
    }

    /**
     * Check if a level has access to a role.
     * @param string $level
     * @param string $role
     * @return boolean
     */
    public function check($level, $role) {
        if(!isset($this->_permissions[$level])) {
            return false;
        }
        if(!isset($this->_permissions[$level][$role])) {
            return false;
        }
        return true;
    }
}
