<?php
session_start();

$_SESSION['success_message'] = 'Logout efetuado com sucesso.'; // Define a mensagem de sucesso
header('Location: login.php'); // Redireciona para a página de login
exit;
?>
