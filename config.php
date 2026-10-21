<?php
// config.php
// Author: David M. Anderson
// Built with AI assistance (Claude, Anthropic)

define('APP_NAME', 'Campfire');
define('APP_VERSION', '1.0.0');
define('APP_ROOT', __DIR__);
define('SCHEMA_PATH', APP_ROOT . '/db/schema.sql');

// The database lives in DATA_DIR, which defaults to db/. To keep it outside
// the web root, define DATA_DIR in a config.local.php beside this file;
// deploys must leave that file in place.
if (is_file(APP_ROOT . '/config.local.php')) {
    require APP_ROOT . '/config.local.php';
}
defined('DATA_DIR') || define('DATA_DIR', APP_ROOT . '/db');
define('DB_PATH', DATA_DIR . '/campfire.db');
