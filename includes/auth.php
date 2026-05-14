<?php
/**
 * includes/auth.php
 * Middleware de autenticación – incluir al inicio de cada página protegida.
 */
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
requireLogin();
$user = currentUser();
