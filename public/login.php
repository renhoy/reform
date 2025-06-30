<?php
// {"_META_file_path_": "public/login.php"}
// Login directo

define('ROOT_PATH', dirname(__DIR__));
define('SRC_PATH', ROOT_PATH . '/src');
define('PUBLIC_PATH', __DIR__);

require_once SRC_PATH . '/config/config.php';
include SRC_PATH . '/views/pages/login.php';