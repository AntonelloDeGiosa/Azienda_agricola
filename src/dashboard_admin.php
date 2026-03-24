<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['ruolo'] !== 'admin') {
    header('Location: catalogo.php'); exit;
}

// Abilita le eccezioni per mysqli (serve per i blocchi try/catch)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$conn = new mysqli('db', 'myuser', 'mypassword', 'myapp_db');
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

$messaggio = ''; $errore = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. AGGIUNGI CATEGORIA
    if (isset($_POST['aggiungi_categoria'])) {
        $nome = trim($_POST['nome_categoria']);
        if($nome) {
            $stmt = $conn->prepare("INSERT INTO CATEGORIA (nome) VALUES (?)");
            $stmt->bind_param("s", $nome); // 's' = string
            $stmt->execute();
            $stmt->close();
            $messaggio = "Categoria '$nome' creata!";
        }
    }
    // 2. AGGIUNGI PRODOTTO
    if (isset($_POST['aggiungi_prodotto'])) {
        try {
            $conn->begin_transaction();
            
            // Inserisci prodotto
            $stmt1 = $conn->prepare("INSERT INTO PRODOTTO (nome, tipologia, unita_misura, id_categoria) VALUES (?, ?, ?, ?)");
            // 'sssi' = string, string, string, integer
            $stmt1->bind_param("sssi", $_POST['nome_prod'], $_POST['tipologia'], $_POST['unita'], $_POST['id_cat']);
            $stmt1->execute();
            $id_p = $conn->insert_id; // mysqli syntax per lastInsertId()
            $stmt1->close();
            
            // Inserisci prezzo nello storico
            $stmt2 = $conn->prepare("INSERT INTO STORICO_PREZZI (id_prodotto, prezzo) VALUES (?, ?)");
            // 'id' = integer, double
            $stmt2->bind_param("id", $id_p, $_POST['prezzo']);
            $stmt2->execute();
            $stmt2->close();
            
            $conn->commit();
            $messaggio = "Prodotto '".$_POST['nome_prod']."' aggiunto a listino!";
        } catch (Exception $e) { 
            $conn->rollback(); 
            $errore = "Errore: " . $e->getMessage(); 
        }
    }
    // 3. AGGIORNA PREZZO
    if (isset($_POST['aggiorna_prezzo'])) {
        try {
            $conn->begin_transaction();
            
            // Chiudi il vecchio prezzo
            $stmt_upd = $conn->prepare("UPDATE STORICO_PREZZI SET data_fine_validita = CURRENT_TIMESTAMP WHERE id_prodotto = ? AND data_fine_validita IS NULL");
            $stmt_upd->bind_param("i", $_POST['id_prod_mod']);
            $stmt_upd->execute();
            $stmt_upd->close();
            
            // Inserisci il nuovo prezzo
            $stmt_ins = $conn->prepare("INSERT INTO STORICO_PREZZI (id_prodotto, prezzo) VALUES (?, ?)");
            // 'id' = integer, double
            $stmt_ins->bind_param("id", $_POST['id_prod_mod'], $_POST['nuovo_prezzo']);
            $stmt_ins->execute();
            $stmt_ins->close();
            
            $conn->commit();
            $messaggio = "Prezzo aggiornato correttamente!";
        } catch (Exception $e) { 
            $conn->rollback(); 
            $errore = "Errore durante l'aggiornamento."; 
        }
    }
}

// Estrazione dati per i menu a tendina usando fetch_all(MYSQLI_ASSOC)
$res_cat = $conn->query("SELECT * FROM CATEGORIA ORDER BY nome");
$categorie = $res_cat->fetch_all(MYSQLI_ASSOC);

$res_prod = $conn->query("SELECT p.id_prodotto, p.nome, s.prezzo FROM PRODOTTO p LEFT JOIN STORICO_PREZZI s ON p.id_prodotto = s.id_prodotto AND s.data_fine_validita IS NULL ORDER BY p.nome");
$prodotti_attivi = $res_prod->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8"><title>Pannello Amministratore</title>
    <style>
        body { font-family: sans-serif; background: #f0f2f1; padding: 20px; }
        .container { max-width: 800px; margin: auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .nav-admin { margin-bottom: 30px; display: flex; gap: 10px; }
        .nav-admin a { padding: 12px 20px; border-radius: 6px; text-decoration: none; color: white; font-weight: bold; flex: 1; text-align: center; }
        section { background: #fdfdfd; padding: 20px; border-radius: 10px; margin-bottom: 25px; border: 1px solid #eee; }
        h3 { color: #4a7c59; margin-top: 0; border-bottom: 1px solid #4a7c59; padding-bottom: 5px; }
        input, select { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; }
        button { background: #4a7c59; color: white; border: none; padding: 12px; cursor: pointer; border-radius: 5px; width: 100%; font-weight: bold; }
        button:hover { background: #3b6347; }
    </style>
</head>
<body>
<div class="container">
    <h1>⚙️ Dashboard Admin</h1>
    <div class="nav-admin">
        <a href="magazzino.php" style="background: #f39c12;">🚜 Magazzino</a>
        <a href="ordini_totali.php" style="background: #27ae60;">📋 Ordini Ricevuti</a>
        <a href="catalogo.php" style="background: #777;">🏠 Catalogo</a>
    </div>

    <?php if ($messaggio) echo "<p style='color:green; font-weight:bold;'>✓ $messaggio</p>"; ?>
    <?php if ($errore) echo "<p style='color:red; font-weight:bold;'>✗ $errore</p>"; ?>

    <section>
        <h3>1. Nuova Categoria</h3>
        <form method="POST">
            <input type="text" name="nome_categoria" placeholder="Es. Confetture" required>
            <button type="submit" name="aggiungi_categoria">Crea Categoria</button>
        </form>
    </section>

    <section>
        <h3>2. Nuovo Prodotto a Listino</h3>
        <form method="POST">
            <input type="text" name="nome_prod" placeholder="Nome (es. Miele Millefiori)" required>
            <select name="id_cat">
                <?php foreach($categorie as $c): ?>
                    <option value="<?php echo $c['id_categoria']; ?>"><?php echo htmlspecialchars($c['nome']); ?></option>
                <?php endforeach; ?>
            </select>
            <div style="display:flex; gap:10px;">
                <input type="text" name="tipologia" placeholder="Tipologia (Fresco/Lavorato)">
                <input type="text" name="unita" placeholder="Unità (kg/litro/pezzo)">
            </div>
            <input type="number" name="prezzo" step="0.01" placeholder="Prezzo (€)" required>
            <button type="submit" name="aggiungi_prodotto">Salva Prodotto</button>
        </form>
    </section>

    <section>
        <h3>3. Aggiornamento Prezzo Esistente</h3>
        <form method="POST">
            <select name="id_prod_mod">
                <?php foreach($prodotti_attivi as $pa): ?>
                    <option value="<?php echo $pa['id_prodotto']; ?>">
                        <?php echo htmlspecialchars($pa['nome']); ?> (Ora: €<?php echo number_format($pa['prezzo'], 2, ',', '.'); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="nuovo_prezzo" step="0.01" placeholder="Nuovo Prezzo (€)" required>
            <button type="submit" name="aggiorna_prezzo">Applica Nuovo Prezzo</button>
        </form>
    </section>
</div>
</body>
</html>