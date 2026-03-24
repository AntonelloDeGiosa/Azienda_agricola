<?php
session_start();

// 1. CONTROLLI DI SICUREZZA
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['carrello']) || count($_SESSION['carrello']) === 0) {
    header('Location: catalogo.php');
    exit;
}

// Abilita le eccezioni per mysqli (fondamentale per far funzionare il blocco try-catch)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// 2. CONNESSIONE MYSQLI
$conn = new mysqli('db', 'myuser', 'mypassword', 'myapp_db');
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

$ordine_completato = false;
$id_nuova_vendita = 0;
$totale_da_pagare = 0.0;
$errore = '';

try {
    // Inizio transazione
    $conn->begin_transaction();

    // A. Recupero ID Cliente
    $stmt_c = $conn->prepare("SELECT id_cliente FROM CLIENTE WHERE nickname = ?");
    $stmt_c->bind_param("s", $_SESSION['nickname']); // 's' sta per stringa
    $stmt_c->execute();
    $row_c = $stmt_c->get_result()->fetch_row();
    $id_cliente = $row_c[0];
    $stmt_c->close();

    // B. Controllo disponibilità e calcolo totale
    foreach ($_SESSION['carrello'] as $id_p => $q_richiesta) {
        
        // Recuperiamo il NOME del prodotto e il PREZZO attuale
        $st_info = $conn->prepare("
            SELECT p.nome, s.prezzo 
            FROM PRODOTTO p 
            JOIN STORICO_PREZZI s ON p.id_prodotto = s.id_prodotto 
            WHERE p.id_prodotto = ? AND s.data_fine_validita IS NULL
        ");
        $st_info->bind_param("i", $id_p); // 'i' sta per integer
        $st_info->execute();
        $info_prodotto = $st_info->get_result()->fetch_assoc();
        $st_info->close();
        
        $nome_prodotto = $info_prodotto['nome'];
        $prezzo_attuale = $info_prodotto['prezzo'];

        // Controllo giacenza totale per questo prodotto
        $st_giac = $conn->prepare("SELECT SUM(giacenza_attuale) FROM PRODUZIONE_GIACENZA WHERE id_prodotto = ?");
        $st_giac->bind_param("i", $id_p);
        $st_giac->execute();
        $row_giac = $st_giac->get_result()->fetch_row();
        $totale_disponibile = ($row_giac && $row_giac[0] !== null) ? $row_giac[0] : 0;
        $st_giac->close();

        if ($totale_disponibile < $q_richiesta) {
            throw new Exception("Spiacenti, scorte insufficienti per il prodotto: <strong>$nome_prodotto</strong> (Disponibili: " . floatval($totale_disponibile) . ")");
        }

        $totale_da_pagare += ($prezzo_attuale * $q_richiesta);
    }

    // C. Creazione testata VENDITA (id_luogo = 2)
    $id_luogo = 2;
    $stmt_v = $conn->prepare("INSERT INTO VENDITA (id_cliente, id_luogo, totale_calcolato, totale_pagato) VALUES (?, ?, ?, ?)");
    // 'iidd' = intero, intero, double, double
    $stmt_v->bind_param("iidd", $id_cliente, $id_luogo, $totale_da_pagare, $totale_da_pagare);
    $stmt_v->execute();
    $id_nuova_vendita = $conn->insert_id; // in mysqli si usa insert_id
    $stmt_v->close();

    // D. Dettaglio Vendita e SCARICO MAGAZZINO (FIFO)
    foreach ($_SESSION['carrello'] as $id_p => $q_da_scaricare) {
        
        // Prendiamo il prezzo applicato
        $st_p = $conn->prepare("SELECT prezzo FROM STORICO_PREZZI WHERE id_prodotto = ? AND data_fine_validita IS NULL");
        $st_p->bind_param("i", $id_p);
        $st_p->execute();
        $row_p = $st_p->get_result()->fetch_row();
        $p_applicato = $row_p[0];
        $st_p->close();

        // Inserimento riga di dettaglio
        $st_det = $conn->prepare("INSERT INTO DETTAGLIO_VENDITA (id_vendita, id_prodotto, quantita, prezzo_applicato) VALUES (?, ?, ?, ?)");
        // 'iidd' = intero, intero, double, double
        $st_det->bind_param("iidd", $id_nuova_vendita, $id_p, $q_da_scaricare, $p_applicato);
        $st_det->execute();
        $st_det->close();

        // Scarico dai lotti
        $st_lotti = $conn->prepare("SELECT id_produzione, giacenza_attuale FROM PRODUZIONE_GIACENZA WHERE id_prodotto = ? AND giacenza_attuale > 0 ORDER BY data_lavorazione ASC");
        $st_lotti->bind_param("i", $id_p);
        $st_lotti->execute();
        $lotti = $st_lotti->get_result()->fetch_all(MYSQLI_ASSOC);
        $st_lotti->close();

        $rimanente = $q_da_scaricare;
        foreach ($lotti as $lotto) {
            if ($rimanente <= 0) break;

            if ($lotto['giacenza_attuale'] >= $rimanente) {
                $upd = $conn->prepare("UPDATE PRODUZIONE_GIACENZA SET giacenza_attuale = giacenza_attuale - ? WHERE id_produzione = ?");
                $upd->bind_param("di", $rimanente, $lotto['id_produzione']); // 'd'ouble, 'i'nteger
                $upd->execute();
                $upd->close();
                $rimanente = 0;
            } else {
                $rimanente -= $lotto['giacenza_attuale'];
                $upd = $conn->prepare("UPDATE PRODUZIONE_GIACENZA SET giacenza_attuale = 0 WHERE id_produzione = ?");
                $upd->bind_param("i", $lotto['id_produzione']);
                $upd->execute();
                $upd->close();
            }
        }
    }

    // Se tutto va bene, conferma
    $conn->commit();
    unset($_SESSION['carrello']);
    $ordine_completato = true;

} catch (Exception $e) {
    // In caso di errore, annulla tutte le query fatte finora
    $conn->rollback();
    $errore = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Esito Ordine</title>
    <style>
        body { font-family: sans-serif; background: #f4f7f6; text-align: center; padding-top: 100px; }
        .messaggio-box { background: white; max-width: 500px; margin: auto; padding: 40px; border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .successo { color: #4a7c59; }
        .errore { color: #d9534f; }
        .btn { display: inline-block; margin-top: 20px; padding: 10px 20px; text-decoration: none; border-radius: 5px; color: white; font-weight: bold; }
        .btn-successo { background: #4a7c59; }
        .btn-errore { background: #777; }
    </style>
</head>
<body>

<div class="messaggio-box">
    <?php if ($ordine_completato): ?>
        <h1 class="successo">✅ Ordine Confermato!</h1>
        <p>Grazie per il tuo acquisto. Il numero del tuo ordine è <strong>#<?php echo $id_nuova_vendita; ?></strong>.</p>
        <p>Il magazzino è stato aggiornato correttamente.</p>
        <a href="catalogo.php" class="btn btn-successo">Torna al Catalogo</a>
    <?php else: ?>
        <h1 class="errore">⚠️ Attenzione</h1>
        <p><?php echo $errore; ?></p>
        <p>Controlla le quantità nel carrello e riprova.</p>
        <a href="carrello.php" class="btn btn-errore">Torna al Carrello</a>
    <?php endif; ?>
</div>

</body>
</html>