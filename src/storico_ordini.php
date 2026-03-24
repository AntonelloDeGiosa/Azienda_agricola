<?php
session_start();

// 1. PROTEZIONE DELLA PAGINA
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Abilitazione eccezioni per mysqli
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// 2. CONNESSIONE AL DATABASE (MYSQLI)
$host = 'db'; 
$dbname = 'myapp_db';
$username = 'myuser';
$db_password = 'mypassword';

try {
    $conn = new mysqli($host, $username, $db_password, $dbname);
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Errore di connessione al database: " . $e->getMessage());
}

$ordini = [];

// 3. RECUPERO ID CLIENTE E I SUOI ORDINI
try {
    // Prima troviamo l'ID del cliente loggato
    $stmt_cliente = $conn->prepare("SELECT id_cliente FROM CLIENTE WHERE nickname = ?");
    $stmt_cliente->bind_param("s", $_SESSION['nickname']);
    $stmt_cliente->execute();
    $res_cliente = $stmt_cliente->get_result();
    $cliente = $res_cliente->fetch_assoc();
    $stmt_cliente->close();

    if ($cliente) {
        $id_cliente = $cliente['id_cliente'];

        // Recuperiamo tutti gli scontrini di questo cliente, dal più recente al più vecchio
        $stmt_vendite = $conn->prepare("
            SELECT id_vendita, data_acquisto, totale_pagato 
            FROM VENDITA 
            WHERE id_cliente = ? 
            ORDER BY data_acquisto DESC
        ");
        $stmt_vendite->bind_param("i", $id_cliente);
        $stmt_vendite->execute();
        $res_vendite = $stmt_vendite->get_result();
        $scontrini = $res_vendite->fetch_all(MYSQLI_ASSOC);
        $stmt_vendite->close();

        // Per ogni scontrino, andiamo a cercare i dettagli (i prodotti acquistati)
        $stmt_dettagli = $conn->prepare("
            SELECT d.quantita, d.prezzo_applicato, p.nome, p.unita_misura 
            FROM DETTAGLIO_VENDITA d
            JOIN PRODOTTO p ON d.id_prodotto = p.id_prodotto
            WHERE d.id_vendita = ?
        ");

        foreach ($scontrini as $scontrino) {
            $stmt_dettagli->bind_param("i", $scontrino['id_vendita']);
            $stmt_dettagli->execute();
            $res_dettagli = $stmt_dettagli->get_result();
            $dettagli = $res_dettagli->fetch_all(MYSQLI_ASSOC);
            
            // Salviamo tutto in un array strutturato
            $ordini[] = [
                'info' => $scontrino,
                'prodotti' => $dettagli
            ];
        }
        $stmt_dettagli->close();
    }
} catch (Exception $e) {
    die("Errore nel caricamento dello storico: " . $e->getMessage());
} 


?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>I Miei Ordini</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .storico-container { max-width: 900px; margin: 30px auto; width: 95%; }
        .card-ordine { background: white; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); margin-bottom: 25px; overflow: hidden; }
        .header-ordine { background: #4a7c59; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .header-ordine h3 { margin: 0; font-size: 1.2em; }
        .body-ordine { padding: 20px; }
        .tabella-dettagli { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .tabella-dettagli th, .tabella-dettagli td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
        .tabella-dettagli th { color: #777; font-size: 0.9em; text-transform: uppercase; }
        .totale-ordine { text-align: right; font-size: 1.2em; font-weight: bold; color: #d9534f; margin-top: 15px; }
        .btn-torna { display: inline-block; background: #777; color: white; text-decoration: none; padding: 10px 15px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="storico-container">
    
    <a href="catalogo.php" class="btn-torna">⬅️ Torna al Catalogo</a>
    
    <h1 style="color: #3e362e;">📦 Lo Storico dei tuoi Ordini</h1>

    <?php if (count($ordini) > 0): ?>
        <?php foreach ($ordini as $ordine): ?>
            <div class="card-ordine">
                
                <div class="header-ordine">
                    <h3>Ordine #<?php echo $ordine['info']['id_vendita']; ?></h3>
                    <span>📅 <?php echo date('d/m/Y H:i', strtotime($ordine['info']['data_acquisto'])); ?></span>
                </div>
                
                <div class="body-ordine">
                    <table class="tabella-dettagli">
                        <thead>
                            <tr>
                                <th>Prodotto</th>
                                <th>Prezzo Pagato</th>
                                <th>Quantità</th>
                                <th>Subtotale</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ordine['prodotti'] as $item): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($item['nome']); ?></strong></td>
                                    <td>€ <?php echo number_format($item['prezzo_applicato'], 2, ',', '.'); ?></td>
                                    <td><?php echo number_format($item['quantita'], 2, ',', '.'); ?> <?php echo htmlspecialchars($item['unita_misura']); ?></td>
                                    <td>€ <?php echo number_format($item['prezzo_applicato'] * $item['quantita'], 2, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="totale-ordine">
                        Totale Pagato: € <?php echo number_format($ordine['info']['totale_pagato'], 2, ',', '.'); ?>
                    </div>
                </div>

            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="background: white; padding: 30px; text-align: center; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
            <p style="font-size: 1.2em; color: #777;">Non hai ancora effettuato nessun ordine.</p>
            <a href="catalogo.php" style="color: #4a7c59; font-weight: bold; text-decoration: none;">Inizia subito il tuo primo acquisto!</a>
        </div>
    <?php endif; ?>

</div>

</body>
</html>