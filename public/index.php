<?php

require_once('../application/bootstrap.php');

// Setup Logging
switch(strtolower(config('log_level'))) {
    case 'debug':   $log_level = Util_Log::DEBUG;   break;
    case 'info':    $log_level = Util_Log::INFO;    break;
    case 'error':   $log_level = Util_Log::ERROR;   break;
    case 'verbose': $log_level = Util_Log::VERBOSE; break;
    default:        $log_level = Util_Log::INFO;    break;
}
$Log = Util_Log::getInstance();
$Log->setLogLevel($log_level);

$Util = Util::getInstance();
/*
// Default to Index
if(!isset($_GET['url'])) {
    $_GET['url'] = 'index';
}

// Find Controller
$url_path =         explode('/', $_GET['url']);
$controller_name =  ucfirst($url_path[0]);
$controller_path =  "\\App\\Controller\\$controller_name";
$action_name =      isset($url_path[1]) ? $url_path[1] : 'root';
$action =           $action_name.'Action';

// Create ErrorController and Util classes
$ErrorController = \App\Controller\Error::getInstance();

$Cache = \Util\Cache::getInstance();
$ACL = \Util\ACL::getInstance();

// Load ACLs
$acl = config('acl');
$acl_levels = $acl['levels'];
foreach($acl_levels as $level => $parent) {
    $ACL->addLevel($level, $parent);
}
$acl_permissions = $acl['permissions'];
foreach($acl_permissions as $role => $permission) {
    $ACL->addRole($role);
    $ACL->addPermission($permission[0], $role, $permission[1]);
}
unset($acl, $acl_levels, $acl_permissions);

$Log->log(\Util\Log::DEBUG, "Memory usage before controller: ".$Util->getMemoryUsage());

// Create Controller and attempt to call the method asked for, otherwise error out
if(class_exists($controller_path)) {
    $controller = new $controller_path();

    if(!method_exists($controller, $action)) {
        $ErrorController->rootAction("Action $action doesn't exist for Controller $controller_path");
    } else {
        $acl = isset($_SESSION['account']) ? $_SESSION['account']->acl : 'User';
        $acl_role = "{$controller_name}.{$action_name}";

        if($ACL->check($acl, $acl_role)) {
            $controller->init();
            $controller->$action();
        } else {
            $ErrorController->rootAction("Permission denied");
        }
    }
} else {
    $ErrorController->rootAction("No such controller $controller_name");
}
*/

$Log->log(Util_Log::DEBUG, 'Final memory usage: '.$Util->getMemoryUsage());
$Log->log(Util_Log::DEBUG, 'Script took: '.round(microtime(true) - $GLOBALS['script_start_time'],5).' seconds to run.');
if(config('log_display') === true) {
    $Log->displayHTML();
}
