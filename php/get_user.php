<?php
session_start();
header('Content-Type: application/json');
if (isset($_SESSION['username'])) {
    echo json_encode(['success'=>true, 'username'=>$_SESSION['username']]);
} else {
    echo json_encode(['success'=>false]);
}