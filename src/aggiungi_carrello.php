<?php
session_start();

// Controllo sicurezza
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Se ricevo i dati dal form del catalogo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_prodotto']) && isset($_POST['quantita'])) {
    
    $id_prodotto = (int)$_POST['id_prodotto'];
    $quantita = (float)$_POST['quantita'];

    if ($quantita > 0) {
        // Se il carrello non esiste ancora, lo creo come array vuoto
        if (!isset($_SESSION['carrello'])) {
            $_SESSION['carrello'] = [];
        }

        // Se il prodotto c'è già nel carrello, sommo la nuova quantità
        if (isset($_SESSION['carrello'][$id_prodotto])) {
            $_SESSION['carrello'][$id_prodotto] += $quantita;
        } else {
            // Altrimenti lo aggiungo per la prima volta
            $_SESSION['carrello'][$id_prodotto] = $quantita;
        }
    }
}

// Finito l'inserimento, rimando l'utente alla pagina del carrello
header('Location: carrello.php');
exit;