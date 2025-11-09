<?php
session_start();

if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

// function hasRole($roles) {
//     return in_array($_SESSION['role'], (array) $roles);
// }
