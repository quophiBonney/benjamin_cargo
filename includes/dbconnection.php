<?php
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

// Load .env file from project root
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

try {
    $dsn = "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8";
    $dbh = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"
    ]);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    throw $e;
}