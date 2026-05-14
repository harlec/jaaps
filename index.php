<?php
require_once __DIR__ . '/config/config.php';
redirect(isLoggedIn() ? APP_URL . '/dashboard.php' : APP_URL . '/login.php');
