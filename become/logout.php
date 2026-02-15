<?php
session_start();
$_SESSION['train_auth'] = false;
unset($_SESSION['train_auth']);
header('Location: login.php');
exit;
