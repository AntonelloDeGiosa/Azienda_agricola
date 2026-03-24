<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['ruolo'] === 'admin') {
    header('Location: catalogo.php');
    exit;
}

// Inizializza il carrello se non esiste
if (!isset($_SESSION['carrello'])) {
    $_SESSION['carrello'] = [];
}

// LOGICA DI AGGIORNAMENTO O RIMOZIONE DAL CARRELLO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_prodotto_mod = $_POST['id_prodotto'];
    
    if (isset($_POST['rimuovi'])) {
        // Elimina l'elemento dal carrello
        unset($_SESSION['carrello'][$id_prodotto_mod]);
    } elseif (isset($_POST['aggiorna'])) {
        // Aggiorna la quantità
        $nuova_q = floatval($_POST['quantita']);
        if ($nuova_q > 0) {
            $_SESSION['carrello'][$id_prodotto_mod] = $nuova_q;
        } else {
            unset($_SESSION['carrello'][$id_prodotto_mod]);
        }
    }
    // Ricarica la pagina per evitare l'invio doppio del form
    header('Location: carrello.php');
    exit;
}

$host = 'db'; $dbname = 'myapp_db'; $username = 'myuser'; $db_password = 'mypassword';
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $db_password);
} catch (PDOException $e) { die("Errore: " . $e->getMessage()); }

$totale_carrello = 0.0;
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Il tuo Carrello</title>
    <style>
        body { font-family: sans-serif; background: #f0f2f1; padding: 30px; }
        .container { max-width: 800px; margin: auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .riga-carrello { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding: 15px 0; }
        .dettagli-prod { flex-grow: 1; }
        .azioni-carrello { display: flex; align-items: center; gap: 10px; }
        .btn-aggiorna { background: #f39c12; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; }
        .btn-rimuovi { background: #d9534f; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; }
        .btn-checkout { background: #4a7c59; color: white; text-decoration: none; padding: 15px 30px; border-radius: 6px; font-weight: bold; font-size: 1.1em; display: inline-block; margin-top: 20px;}
        .btn-indietro { background: #777; color: white; text-decoration: none; padding: 10px 15px; border-radius: 5px; display: inline-block; margin-bottom: 20px;}
    </style>
</head>
<body>

<div class="container">
    <a href="catalogo.php" class="btn-indietro">⬅️ Torna agli Acquisti</a>
    <h1 style="color: #333;">🛒 Il tuo Carrello</h1>

    <?php if (empty($_SESSION['carrello'])): ?>
        <p style="text-align: center; padding: 40px; color: #777;">Il tuo carrello è vuoto. Vai al catalogo per aggiungere prodotti!</p>
    <?php else: ?>
        
        <?php foreach ($_SESSION['carrello'] as $id_p => $quantita): 
            // Recupero i dati aggiornati del prodotto dal DB
            $st = $conn->prepare("SELECT p.nome, p.unita_misura, s.prezzo 
                                  FROM PRODOTTO p 
                                  JOIN STORICO_PREZZI s ON p.id_prodotto = s.id_prodotto 
                                  WHERE p.id_prodotto = ? AND s.data_fine_validita IS NULL");
            $st->execute([$id_p]);
            $prodotto = $st->fetch(PDO::FETCH_ASSOC);
            
            if ($prodotto) {
                $subtotale = $prodotto['prezzo'] * $quantita;
                $totale_carrello += $subtotale;
        ?>
            <div class="riga-carrello">
                <div class="dettagli-prod">
                    <h3 style="margin: 0; color: #4a7c59;"><?php echo htmlspecialchars($prodotto['nome']); ?></h3>
                    <span style="color: #777;">€ <?php echo number_format($prodotto['prezzo'], 2, ',', '.'); ?> / <?php echo $prodotto['unita_misura']; ?></span>
                </div>
                
                <div class="azioni-carrello">
                    <form method="POST" style="display: flex; gap: 5px;">
                        <input type="hidden" name="id_prodotto" value="<?php echo $id_p; ?>">
                        <input type="number" name="quantita" step="0.01" min="0.01" value="<?php echo floatval($quantita); ?>" style="width: 70px; padding: 5px; border: 1px solid #ccc; border-radius: 4px;">
                        <button type="submit" name="aggiorna" class="btn-aggiorna" title="Aggiorna quantità">🔄</button>
                    </form>
                    
                    <form method="POST">
                        <input type="hidden" name="id_prodotto" value="<?php echo $id_p; ?>">
                        <button type="submit" name="rimuovi" class="btn-rimuovi" title="Rimuovi dal carrello">🗑️</button>
                    </form>
                </div>
                
                <div style="width: 100px; text-align: right; font-weight: bold; font-size: 1.1em;">
                    € <?php echo number_format($subtotale, 2, ',', '.'); ?>
                </div>
            </div>
        <?php 
            } 
        endforeach; 
        ?>

        <div style="text-align: right; margin-top: 20px; border-top: 2px solid #333; padding-top: 15px;">
            <span style="font-size: 1.2em;">Totale da pagare:</span>
            <span style="font-size: 1.5em; font-weight: bold; color: #d9534f; margin-left: 10px;">
                € <?php echo number_format($totale_carrello, 2, ',', '.'); ?>
            </span>
        </div>

        <div style="text-align: right;">
            <form method="POST" action="checkout.php">
                <button type="submit" class="btn-checkout">Procedi all'Acquisto 💳</button>
            </form>
        </div>

    <?php endif; ?>
</div>

</body>
</html>