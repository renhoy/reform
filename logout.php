<?php
// {"_META_file_path_": "refor/logout.php"}
// Cerrar sesión simple

session_start();
session_destroy();
header('Location: login.php');
exit;