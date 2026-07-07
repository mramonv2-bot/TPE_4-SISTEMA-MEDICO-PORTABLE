<?php
// db_config.php
$base_dir = __DIR__;
$db_path = $base_dir . DIRECTORY_SEPARATOR . 'citas_medicas.db';

try {
    $pdo = new PDO("sqlite:" . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Tabla de usuarios
    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        usuario TEXT UNIQUE,
        password TEXT
    )");

    // Tabla de citas
    $pdo->exec("CREATE TABLE IF NOT EXISTS citas (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        paciente_nombre TEXT,
        paciente_apellido TEXT,
        cedula TEXT,
        especialidad TEXT,
        fecha DATE,
        hora TEXT,
        estado TEXT DEFAULT 'Registrada'
    )");

    // Usuario admin por defecto
    $pdo->exec("INSERT OR IGNORE INTO usuarios (usuario, password) VALUES ('admin', '1234')");

} catch (PDOException $e) {
    die("Error en el almacenamiento local: " . $e->getMessage());
}
?>