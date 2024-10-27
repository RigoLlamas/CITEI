<?php
// Verificar si es el día y el mes
$mes_actual = date('n');

if (in_array($mes_actual, [1, 4, 7, 10])) {
    
    //header('Location: ../scripts/asignar_oferta.php');
    include '../scripts/asignar_oferta.php';  
    
} 

if (date('j') == 1) {
    
    //header('Location: ../scripts/generar_oferta.php');
    include '../scripts/generar_oferta.php'; 
    
} 
?>
