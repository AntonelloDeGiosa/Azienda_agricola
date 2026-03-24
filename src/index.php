<?php
session_start();
require 'vendor/autoload.php'; 

use OTPHP\TOTP;

$totp   = TOTP::create();
$secret = $totp->getSecret();

$messaggio = '';
$errore    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    
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

    $nominativo      = trim(isset($_POST['nome']) ? $_POST['nome'] : '');
    $nickname        = trim(isset($_POST['nickname'])  ? $_POST['nickname'] : '');
    $contatto        = trim(isset($_POST['contatto'])  ? $_POST['contatto'] : '');
    $password        = isset($_POST['password'])  ? $_POST['password'] : ''; 
    $secret_inviato  = trim(isset($_POST['secret'])    ? $_POST['secret'] : '');

    if (empty($nominativo) || empty($nickname) || empty($password) || empty($secret_inviato)) {
        $errore = 'Nome, nickname, password e chiave TOTP sono obbligatori.';
    } elseif (strlen($password) < 8) {
        $errore = 'La password deve contenere almeno 8 caratteri.';
    } elseif (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $nickname)) {
        $errore = 'Il nickname può contenere solo lettere, numeri, punto, trattino e underscore.';
    } else {
        
        try {
            $chk = $conn->prepare('SELECT id_cliente FROM CLIENTE WHERE nickname = ?');
            $chk->bind_param("s", $nickname);
            $chk->execute();
            $risultato = $chk->get_result();
            
            if ($risultato->num_rows > 0) {
                $errore = 'Questo nickname è già in uso. Scegline un altro.';
            } else {
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                $ruolo = 'cliente';

                $stmt = $conn->prepare('INSERT INTO CLIENTE (nominativo, nickname, dati_contatto, password_hash, totp_secret, ruolo) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->bind_param("ssssss", $nominativo, $nickname, $contatto, $password_hash, $secret_inviato, $ruolo);
                $stmt->execute();
                
                $messaggio = 'Registrazione completata con successo! Ora puoi effettuare il login.';
                
                $nominativo = $nickname = $contatto = '';
            }
            $chk->close();
            if(isset($stmt)) $stmt->close();
            
        } catch (Exception $e) {
            $errore = "Errore durante la registrazione: " . $e->getMessage();
        }
    }
    
    if(isset($conn)) $conn->close();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione - Azienda Agricola</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <div class="form-section">
        <h2>Crea un account</h2>
        <p>Unisciti a noi per acquistare i migliori prodotti agricoli.</p>
        
        <?php if ($errore !== ''): ?>
            <p style="color: red; font-weight: bold;"><?php echo htmlspecialchars($errore); ?></p>
        <?php endif; ?>
        <?php if ($messaggio !== ''): ?>
            <p style="color: green; font-weight: bold;"><?php echo htmlspecialchars($messaggio); ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="nome">Nome Completo</label>
                <input type="text" id="nome" name="nome" placeholder="es. Mario Rossi" required>
            </div>
            
            <div class="form-group">
                <label for="nickname">Nickname</label>
                <input type="text" id="nickname" name="nickname" placeholder="es. mario99" required>
            </div>

            <div class="form-group">
                <label for="contatto">Email o Telefono (Opzionale)</label>
                <input type="text" id="contatto" name="contatto" placeholder="es. mario@email.it">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group" style="background-color: #f9f9f9; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                <label for="totp" style="color: #d9534f; font-weight: bold;">La tua chiave segreta TOTP (SALVALA ORA!):</label>
                <input type="text" id="totp" name="secret" value="<?php echo htmlspecialchars($secret); ?>" readonly style="background-color: #eee; cursor: not-allowed; font-weight: bold;">
                <p style="font-size: 0.85em; color: #555; margin-top: 5px;">
                    Copia questa chiave e incollala nella tua app 2FAuth prima di premere Registrati!
                </p>
            </div>

            <button type="submit">Registrati</button>

            <p style="font-size: 0.9em; margin-top: 15px;">
                Hai già un account? <a href="login.php">Accedi qui</a>
            </p>    
        </form>
    </div>

    <div class="carousel-section">
        <button class="prev" onclick="moveSlide(-1)">&#10094;</button>
        <div class="carousel-track" id="track">
            <div class="carousel-slide">
                <img src="https://images.pexels.com/photos/33260/honey-sweet-syrup-organic.jpg" alt="Miele Biologico">
                <div class="carousel-caption">Miele Biologico</div>
            </div>
            <div class="carousel-slide">
                <img src="https://images.pexels.com/photos/1132047/pexels-photo-1132047.jpeg?auto=compress&cs=tinysrgb&w=800" alt="Frutta Fresca">
                <div class="carousel-caption">Frutta Fresca</div>
            </div>
            <div class="carousel-slide">