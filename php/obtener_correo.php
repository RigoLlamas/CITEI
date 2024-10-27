<?php
session_start();

// Verificar si el correo está en la sesión
if (isset($_SESSION['correo'])) {
    echo json_encode(['correo' => $_SESSION['correo']]);
} else {
    echo json_encode(['error' => 'Correo no encontrado en la sesión']);
}
?>
