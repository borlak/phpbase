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

// Default to Index
if(!isset($_SERVER['REQUEST_URI'])) {
    $controller_name = ucfirst('index');
    $action_name = 'index';
} else {
    $request_uri = filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_STRING);
    $parts = explode('/', $request_uri, 10);

    if(count($parts) >= 2 && !empty($parts[1])) {
        $controller_name = ucfirst($parts[1]);
    } else {
        $controller_name = ucfirst('index');
    }

    if(count($parts) >= 3 && !empty($parts[2])) {
        $action_name = $parts[2];
    } else {
        $action_name = 'index';
    }
}

// Find Controller
$controller = "Controller_{$controller_name}";
$action =     $action_name.'Action';

// Create ErrorController and Util classes
$Util = Util::getInstance();
$ErrorController = Controller_Error::getInstance();
$Redis = Util_Redis::getInstance();
$ACL = Util_ACL::getInstance();

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

$Log->log(Util_Log::DEBUG, "Memory usage before controller: ".$Util->getMemoryUsage());

// Create Controller and attempt to call the method asked for, otherwise error out
if(class_exists($controller)) {
    $controller = new $controller();

    if(!method_exists($controller, $action)) {
        $ErrorController->indexAction("Action $action doesn't exist for Controller $controller_path");
    } else {
        $acl = isset($_SESSION['account']) ? $_SESSION['account']->acl : 'User';
        $acl_role = "{$controller_name}.{$action_name}";

        if($ACL->check($acl, $acl_role)) {
            $controller->init();
            $controller->$action();
        } else {
            $ErrorController->indexAction("Permission denied");
        }
    }
} else {
    $ErrorController->indexAction("No such controller $controller_name");
}

$Log->log(Util_Log::DEBUG, 'Final memory usage: '.$Util->getMemoryUsage());
$Log->log(Util_Log::DEBUG, 'Script took: '.round(microtime(true) - $GLOBALS['script_start_time'],5).' seconds to run.');
if(config('log_display') === true) {
    $Log->displayHTML();
}
