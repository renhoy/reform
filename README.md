Nuevo usuario

Usuario por defecto:
- **Usuario**: `admin`
- **Contraseña**: `password`

Ve a `login.php` en tu servidor local e introduce esas credenciales.

Si necesitas crear un nuevo usuario, ejecuta esto en tu base de datos:
```sql
INSERT INTO users (username, password_hash) VALUES 
('tu_usuario', '$2y$10$hash_de_tu_contraseña');
```

Para generar el hash de contraseña usa:
```php
echo password_hash('tu_contraseña', PASSWORD_DEFAULT);
```