<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
$conn= new mysqli('db', 'myuser', 'mypassword', 'myapp_db');
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}


// Query aggiornata: calcola anche la giacenza totale disponibile per ogni prodotto!
$query = "SELECT p.*, s.prezzo, c.nome as categoria_nome,
          COALESCE((SELECT SUM(giacenza_attuale) FROM PRODUZIONE_GIACENZA WHERE id_prodotto = p.id_prodotto), 0) as giacenza_totale
          FROM PRODOTTO p 
          LEFT JOIN STORICO_PREZZI s ON p.id_prodotto = s.id_prodotto AND s.data_fine_validita IS NULL
          JOIN CATEGORIA c ON p.id_categoria = c.id_categoria
          ORDER BY c.nome, p.nome";
$prodotti = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Catalogo Prodotti</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7f6; margin: 0; }
        header { background: #4a7c59; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        nav a { color: white; text-decoration: none; margin-left: 20px; font-weight: bold; font-size: 0.9em; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; padding: 40px; }
        .card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center; transition: transform 0.2s; }
        .card:hover { transform: translateY(-5px); }
        .price { font-size: 1.4em; color: #4a7c59; font-weight: bold; margin: 15px 0; }
        .btn-compra { background: #4a7c59; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .admin-badge { background: #eee; color: #666; padding: 5px 10px; border-radius: 4px; font-size: 0.8em; display: inline-block; margin-top: 10px; }
        .esaurito-badge { background: #d9534f; color: white; padding: 10px 20px; border-radius: 6px; font-weight: bold; display: inline-block; }
        .disponibilita { font-size: 0.8em; color: #777; margin-bottom: 10px; }
    </style>
</head>
<body>
<header>
    <h1>🌿 Azienda Agricola Bio</h1>
    <nav>
        <?php if ($_SESSION['ruolo'] === 'admin'): ?>
            <a href="dashboard_admin.php" style="color: #ffeb3b;">⚙️ Dashboard</a>
            <a href="magazzino.php" style="color: #f39c12;">🚜 Magazzino</a>
            <a href="ordini_totali.php" style="color: #a2ffaf;">📋 Ordini</a>
        <?php else: ?>
            <a href="storico_ordini.php">📦 I Miei Ordini</a>
            <a href="carrello.php">🛒 Carrello</a>
        <?php endif; ?>
        <a href="logout.php" style="opacity: 0.7;">Esci</a>
    </nav>
</header>

<div class="grid">
    <?php foreach ($prodotti as $p): ?>
        <div class="card">
            <span style="font-size: 0.75em; text-transform: uppercase; color: #999; letter-spacing: 1px;"><?php echo htmlspecialchars($p['categoria_nome']); ?></span>
            <h3 style="margin: 10px 0; color: #333;"><?php echo htmlspecialchars($p['nome']); ?></h3>
            <div class="price">€ <?php echo number_format($p['prezzo'], 2, ',', '.'); ?> <small style="color: #777; font-weight: normal;">/ <?php echo $p['unita_misura']; ?></small></div>
            
            <div class="disponibilita">
                Disponibilità: <?php echo floatval($p['giacenza_totale']); ?> <?php echo $p['unita_misura']; ?>
            </div>

            <?php if ($_SESSION['ruolo'] === 'admin'): ?>
                <div> </div>
            <?php elseif ($p['giacenza_totale'] > 0): ?>
                <form method="POST" action="aggiungi_carrello.php">
                    <input type="hidden" name="id_prodotto" value="<?php echo $p['id_prodotto']; ?>">
                    <input type="number" name="quantita" step="0.01" min="0.01" max="<?php echo $p['giacenza_totale']; ?>" value="1" style="width: 60px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <button type="submit" class="btn-compra">Aggiungi 🛒</button>
                </form>
            <?php else: ?>
                <div class="esaurito-badge">🚫 Esaurito</div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>