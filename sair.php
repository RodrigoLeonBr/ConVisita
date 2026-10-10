<?php
require __DIR__ . '/inc/base.php';
$_SESSION = [];
session_destroy();
header('Location: login.php');
