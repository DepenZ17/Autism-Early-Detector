<?php
include '../auth_check.php';
if ($_SESSION['role'] != 'admin') {
    header("Location: ../dashboard.php");
    exit;
}
