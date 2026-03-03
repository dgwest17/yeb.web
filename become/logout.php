<?php
session_start();
session_destroy();
header('Location: /become/login.php');
exit;
