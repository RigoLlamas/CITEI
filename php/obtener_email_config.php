<?php
$config = include '../config.php';

echo json_encode([
    'service_id' => $config['email_service']['service_id'],
    'template_code' => $config['email_service']['template_code'],
    'template_message' => $config['email_service']['template_message'],
    'user_id' => $config['email_service']['user_id']
]);
?>
