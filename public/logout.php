<?php
// {"_META_file_path_": "public/logout.php"}
// Logout directo

session_start();
session_destroy();
header('Location: login.php');
exit;