<?php
include '../php/conexion.php';
include '../php/solo_admins.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $stmt = $conexion->prepare("DELETE FROM formulario WHERE PK_Form = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            header("Location: ver_formularios.php?status=success");
            exit();
        } else {
            header("Location: ver_formularios.php?status=error");
            exit();
        }
    } catch (Exception $e) {
        header("Location: ver_formularios.php?status=error");
        exit();
    }
} else {
    header("Location: ver_formularios.php?status=error");
    exit();
}
