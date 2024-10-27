<?php
session_start();
$isAdmin = isset($_SESSION['id_usuario']) && $_SESSION['id_usuario'] == 1;
header('Content-Type: application/json');
echo json_encode($isAdmin);
?>