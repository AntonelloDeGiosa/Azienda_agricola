<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: catalogo.php');
    exit;
}


mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = 'db'; $dbname = 'myapp_db'; $username = 'myuser'; $db_password = 'mypassword';

try {
    $conn = new mysqli($host, $username, $db_password, $dbname);
    $conn->set_charset("utf8mb4");
} catch (Exception $e) { 
    die("Errore di connessione: " . $e->getMessage()); 
}

try {
    
    $query = "SELECT v.id_vendita, v.data_acquisto, v.totale_pagato, c.nickname 
              FROM VENDITA v JOIN CLIENTE c ON v.id_cliente = c.id_cliente 
              ORDER BY v.data_acquisto DESC";
    $res_ordini = $conn->query($query);
    $ordini = $res_ordini->fetch_all(MYSQLI_ASSOC);

    $stmt_d = $conn->prepare("SELECT d.quantita, d.prezzo_applicato, p.nome, p.unita_misura 
                              FROM DETTAGLIO_VENDITA d 
                              JOIN PRODOTTO p ON d.id_prodotto = p.id_prodotto 
                              WHERE d.id_vendita = ?");
} catch (Exception $e) {
    die("Errore durante il recupero dei dati: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Ordini Ricevuti</title>
    <style>
        body { font-family: sans-serif; background: #f0f2f1; padding: 30px; }
        .container { max-width: 900px; margin: auto; }
        .ordine-card { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; border-left: 6px solid #4a7c59; box-shadow: 0 3px 10px rgba(0,0,0,0.05); }
        .header-o { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 10px; }
        .lista-prod { list-style: none; padding: 0; margin: 10px 0; }
        .lista-prod li { padding: 5px 0; border-bottom: 1px dashed #eee; display: flex; justify-content: space-between; }
        .incasso { text-align: right; font-weight: bold; color: #d9534f; font-size: 1.2em; margin-top: 10px; }
        .btn-back { display: inline-block; background: #777; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin-bottom: 20px; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard_admin.php" class="btn-back">⬅️ Torna alla Dashboard</a>
        <h1 style="color: #333;">📋 Elenco Ordini Ricevuti</h1>

        <?php if (count($ordini) > 0): ?>
            <?php foreach ($ordini as $o): ?>
                <div class="ordine-card">
                    <div class="header-o">
                        <span><strong>Ordine #<?php echo $o['id_vendita']; ?></strong></span>
                        <span>👤 Cliente: <strong><?php echo htmlspecialchars($o['nickname']); ?></strong></span>
                        <span style="color: #888; font-size: 0.9em;">📅 <?php echo date('d/m/Y H:i', strtotime($o['data_acquisto'])); ?></span>
                    </div>
                    
                    <ul class="lista-prod">
                        <?php 
                        $stmt_d->bind_param("i", $o['id_vendita']);
                        $stmt_d->execute();
                        $res_d = $stmt_d->get_result();
                        $dettagli = $res_d->fetch_all(MYSQLI_ASSOC);
                        
                        foreach($dettagli as $item): ?>
                            <li>
                                <span><?php echo htmlspecialchars($item['nome']); ?></span>
                                <span><?php echo floatval($item['quantita']); ?>x <?php echo $item['unita_misura']; ?> — € <?php echo number_format($item['prezzo_applicato'], 2, ',', '.'); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="incasso">Totale Incassato: € <?php echo number_format($o['totale_pagato'], 2, ',', '.'); ?></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align:center; background: white; padding: 40px; border-radius: 10px;">Nessun ordine presente nel sistema.</div>
        <?php endif; ?>
        
        <?php 
        if (isset($stmt_d)) $stmt_d->close(); 
        ?>
    </div>
</body>
</html>