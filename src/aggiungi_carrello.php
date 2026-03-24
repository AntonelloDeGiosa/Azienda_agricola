<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_prodotto']) && isset($_POST['quantita'])) {
    
    $id_prodotto = (int)$_POST['id_prodotto'];
    $quantita = (float)$_POST['quantita'];

    if ($quantita > 0) {
        if (!isset($_SESSION['carrello'])) {
            $_SESSION['carrello'] = [];
        }

        if (isset($_SESSION['carrello'][$id_prodotto])) {
            $_SESSION['carrello'][$id_prodotto] += $quantita;
        } else {
            $_SESSION['carrello'][$id_prodotto] = $quantita;
        }
    }
}

header('Location: carrello.php');
exit;