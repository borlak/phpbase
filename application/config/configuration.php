<?php

define('APP_NAME', 'phpbase');

// Global configuration array, to be accessed via config() function below.
$configuration = array(
	'production' => array(
		// List of available database servers and their databases.
		'databases' => array(
			'main' => array(
				'database'	=> 'phpbase',
				'user'		=> 'phpbase',
				'password'	=> 'phpbase',
				'host'		=> 'localhost',
			)
		),
		// List of caching options
		'cache' => array(
            'local' => array(
                'enabled' => true,
            ),
            'redis' => array(
                'enabled' => true,
                'server' => array(
                    'host' => 'localhost',
                    'port' => 6379
                ),
            ),
            'memcached' => array(
                'enabled' => false,
                'compression' => true,
                'hash_consistent' => true,
                'servers' => array(
                    0 => array(
                        'host' => 'localhost',
                        'port' => 11211
                    ),
                ),
            ),
            'apc' => array( // not implemented yet -- want to create a kind of decorator design for caching instead
                'enabled' => false,
            ),
		),
        // Access Control Levels
        'acl' => array(
            'levels' => array(
                // Level => Parent level (inherits permissions)
                'User' => 'Developer',
                'Developer' => 'Admin',
                'Admin' => 'Super',
                'Super' => null,
            ),
            'permissions' => array(
                // Permission => level,include parents in permission?
                'Example.something' => array('User', true),
                'Index.index' => array('User', true),
                'Index.phpinfo' => array('Super', true),
                'Account.login' => array('User', true),
                'Account.create' => array('User', true),
                'Account.profile' => array('User', true),
            ),
        ),
		// Password encryption level.  higher = better, but slower.
		'bcrypt' => 12,
        // Logging.  log_display = display on frontend?
        'log_level' => 'debug',
        'log_display' => true,
	),

	'staging' => array(
        'log_level' => 'debug',
        'log_display' => true,
	),

	'development' => array(
        'log_level' => 'debug',
        'log_display' => true,
	)
);

/**
 * Configuration options for application.  If the variable you are requesting
 * doesn't exist in the specified environment, it will attempt to get that
 * variable from the next level 'up', the levels being:
 * development -> staging -> production
 *
 * Variable can have a path component to it, split up by periods.
 * ie: cache.memcached.enabled
 *
 * @global array $configuration
 * @param string $variable
 * @param string $environment
 * @return mixed|boolean false if not found
 */
function config($variable, $environment = 'production') {
	global $configuration;

    if(!is_string($variable)) {
        throw new Exception('config called with no variable');
    }

    $config = $configuration[$environment];

    $path = explode('.', $variable);
    foreach($path as $index) {
        if(!isset($config[$index])) {
            if($environment == 'production') {
                return false;
            }
            if($environment == 'staging') {
                return config($variable, 'production');
            }
            if($environment == 'development') {
                return config($variable, 'staging');
            }
        }
        $config = $config[$index];
    }

	return $config;
}
