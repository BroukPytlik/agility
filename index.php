<?php

// uncomment this line if you must temporarily take down your site for maintenance
// require '.maintenance.php';

// absolute filesystem path to this web root
define('WWW_DIR', __DIR__);

// absolute filesystem path to the application root
define('APP_DIR', WWW_DIR . '/new/system/app');

// absolute filesystem path to the libraries
define('LIBS_DIR', WWW_DIR . '/new/system/libs');

// load bootstrap file
require APP_DIR . '/bootstrap.php';
