<?php
session_start();

// 1. PROTEZIONE (Solo Admin)
if (!isset($_SESSION['logged_in']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: catalogo.php'); 
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

$messaggio = '';
$errore = '';

// 3. REGISTRAZIONE DI UN NUOVO LOTTO DI PRODUZIONE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aggiungi_produzione'])) {
    $id_prodotto = $_POST['id_prodotto'];
    $id_luogo = $_POST['id_luogo'];
    $data_lavorazione = $_POST['data_lavorazione'];
    $quantita = $_POST['quantita'];

    if (!empty($id_prodotto) && !empty($id_luogo) && !empty($data_lavorazione) && $quantita > 0) {
        try {
            // Quando creo un lotto, la giacenza attuale è uguale alla quantità iniziale prodotta
            $stmt = $conn->prepare("
                INSERT INTO PRODUZIONE_GIACENZA (id_prodotto, id_luogo, data_lavorazione, quantita_iniziale, giacenza_attuale) 
                VALUES (?, ?, ?, ?, ?)
            ");
            // 'iisdd' = integer, integer, string (date), double, double
            $stmt->bind_param("iisdd", $id_prodotto, $id_luogo, $data_lavorazione, $quantita, $quantita);
            $stmt->execute();
            $stmt->close();
            
            $messaggio = "Nuovo lotto di produzione registrato in magazzino!";
        } catch (Exception $e) {
            $errore = "Errore durante l'inserimento: " . $e->getMessage();
        }
    } else {
        $errore = "Compila tutti i campi correttamente.";
    }
}

// 4. RECUPERO DATI PER I FORM E LA TABELLA
try {
    // Liste a discesa
    $res_prodotti = $conn->query("SELECT id_prodotto, nome, unita_misura FROM PRODOTTO ORDER BY nome");
    $prodotti = $res_prodotti->fetch_all(MYSQLI_ASSOC);

    $res_luoghi = $conn->query("SELECT id_luogo, nome, tipo FROM LUOGO ORDER BY nome");
    $luoghi = $res_luoghi->fetch_all(MYSQLI_ASSOC);

    // Recupero lo storico delle produzioni e le giacenze attuali
    $res_lotti = $conn->query("
        SELECT pg.id_produzione, p.nome AS prodotto, p.unita_misura, l.nome AS luogo, pg.data_lavorazione, pg.quantita_iniziale, pg.giacenza_attuale
        FROM PRODUZIONE_GIACENZA pg
        JOIN PRODOTTO p ON pg.id_prodotto = p.id_prodotto
        JOIN LUOGO l ON pg.id_luogo = l.id_luogo
        ORDER BY pg.data_lavorazione DESC
    ");
    $lotti_magazzino = $res_lotti->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    die("Errore durante l'estrazione dei dati: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Magazzino - Area Admin</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-container { max-width: 1000px; margin: 20px auto; padding: 20px; background: white; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .form-box { background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 30px; border: 1px solid #ddd; }
        .form-box h3 { margin-top: 0; color: #4a7c59; border-bottom: 2px solid #4a7c59; padding-bottom: 5px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .btn-salva { background-color: #4a7c59; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; font-size: 1em; margin-top: 15px; }
        .tabella-magazzino { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .tabella-magazzino th, .tabella-magazzino td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        .tabella-magazzino th { background-color: #4a7c59; color: white; }
        .esaurito { color: #d9534f; font-weight: bold; }
        .disponibile { color: #5cb85c; font-weight: bold; }
    </style>
</head>
<body>

<div class="admin-container">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h1 style="color: #3e362e;">🚜 Gestione Magazzino & Produzione</h1>
        <div>
            <a href="dashboard_admin.php" style="text-decoration: none; background: #f39c12; color: white; padding: 10px 15px; border-radius: 5px; margin-right: 10px;">Indietro all'Area Admin</a>
        </div>
    </div>

    <?php if ($errore !== ''): ?>
        <p style="color: white; background: #d9534f; padding: 10px; border-radius: 5px;"><?php echo htmlspecialchars($errore); ?></p>
    <?php endif; ?>
    <?php if ($messaggio !== ''): ?>
        <p style="color: white; background: #5cb85c; padding: 10px; border-radius: 5px;"><?php echo htmlspecialchars($messaggio); ?></p>
    <?php endif; ?>

    <div class="form-box">
        <h3>Nuovo Ingresso in Magazzino (Raccolto/Produzione)</h3>
        <form method="POST" action="">
            <div class="grid-2">
                <div>
                    <label>Prodotto:</label><br>
                    <select name="id_prodotto" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                        <?php foreach ($prodotti as $p): ?>
                            <option value="<?php echo $p['id_prodotto']; ?>">
                                <?php echo htmlspecialchars($p['nome']); ?> (<?php echo $p['unita_misura']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Luogo di stoccaggio:</label><br>
                    <select name="id_luogo" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                        <?php foreach ($luoghi as $l): ?>
                            <option value="<?php echo $l['id_luogo']; ?>">
                                <?php echo htmlspecialchars($l['nome']); ?> [<?php echo $l['tipo']; ?>]
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Data di lavorazione/raccolto:</label><br>
                    <input type="date" name="data_lavorazione" required value="<?php echo date('Y-m-d'); ?>" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box;">
                </div>
                <div>
                    <label>Quantità Iniziale Prodotta:</label><br>
                    <input type="number" name="quantita" step="0.01" min="0.01" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box;">
                </div>
            </div>
            <button type="submit" name="aggiungi_produzione" class="btn-salva">Registra Produzione</button>
        </form>
    </div>

    <div class="form-box">
        <h3>Giacenze Attuali (Lotti di Produzione)</h3>
        <?php if (count($lotti_magazzino) > 0): ?>
            <table class="tabella-magazzino">
                <thead>
                    <tr>
                        <th>Lotto #</th>
                        <th>Data Lavorazione</th>
                        <th>Prodotto</th>
                        <th>Luogo</th>
                        <th>Q.tà Iniziale</th>
                        <th>Rimanenza (Giacenza)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lotti_magazzino as $lotto): ?>
                        <tr>
                            <td><?php echo $lotto['id_produzione']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($lotto['data_lavorazione'])); ?></td>
                            <td><strong><?php echo htmlspecialchars($lotto['prodotto']); ?></strong></td>
                            <td><?php echo htmlspecialchars($lotto['luogo']); ?></td>
                            <td><?php echo number_format($lotto['quantita_iniziale'], 2, ',', '.'); ?> <?php echo $lotto['unita_misura']; ?></td>
                            <td class="<?php echo ($lotto['giacenza_attuale'] == 0) ? 'esaurito' : 'disponibile'; ?>">
                                <?php echo number_format($lotto['giacenza_attuale'], 2, ',', '.'); ?> <?php echo $lotto['unita_misura']; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Il magazzino è completamente vuoto. Non ci sono lotti registrati.</p>
        <?php endif; ?>
    </div>

</div>

</body>
</html>