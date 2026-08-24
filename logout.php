<?php
require_once __DIR__ . '/config.php';

if (Auth::isLoggedIn()) {
    Auth::logout();
}

header('Location: index.php');
exit;
?>
