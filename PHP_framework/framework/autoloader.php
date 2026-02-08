<?php
spl_autoload_register(function ($class) {
    $path = str_replace('\\', '/', $class);
    // The idea: "App\Models\Student" → "/app/models/Student.php"
    require_once __DIR__ . '/../' . $path . '.php';
});