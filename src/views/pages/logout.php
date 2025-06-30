<?php
// {"_META_file_path_": "logout.php"}
// Cerrar sesión

require_once 'config.php';

session_destroy();
header('Location: login.php');
exit;