<?php
require_once __DIR__ . '/../includes/session.php';

header('Content-Type: application/json');

if (isAuthenticated()) {
    echo json_encode(['authenticated' => true, 'user' => getUser()]);
} else {
    echo json_encode(['authenticated' => false]);
}
