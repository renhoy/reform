<?php
// {"_META_file_path_": "src/views/templates/404.php"}
// Página de error 404
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Página no encontrada</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin-top: 50px; }
        h1 { color: #e8951c; }
        a { color: #109c61; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>404 - Página no encontrada</h1>
    <p>La página que buscas no existe.</p>
    <a href="<?= url() ?>">Volver al inicio</a>
</body>
</html>