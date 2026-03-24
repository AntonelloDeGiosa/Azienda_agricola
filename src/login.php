<?php
session_start();
require 'vendor/autoload.php';

use OTPHP\TOTP;

$errore = '';

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
    
    $nickname = isset($_POST['nickname']) ? trim($_POST['nickname']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $totp_inserito = isset($_POST['totp']) ? trim($_POST['totp']) : '';

    if (empty($nickname) || empty($password) || empty($totp_inserito)) {
        $errore = "Inserisci tutti i campi.";
    } else {
        
        try {
           
            $stmt = $conn->prepare('SELECT id_cliente, password_hash, totp_secret, ruolo FROM CLIENTE WHERE nickname = ?');
            $stmt->bind_param("s", $nickname);
            $stmt->execute();
            $risultato = $stmt->get_result();
            $user = $risultato->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($password, $user['password_hash'])) {
                
                
                $totp = TOTP::create($user['totp_secret']);
                if ($totp->verify($totp_inserito)) {
                    
                    
                    $_SESSION['logged_in'] = true;
                    $_SESSION['id_utente'] = $user['id_cliente'];
                    $_SESSION['ruolo'] = $user['ruolo']; 
                    $_SESSION['nickname'] = $nickname;

                
                    $conn->close();

                   
                    if ($user['ruolo'] === 'admin') {
                        header('Location: dashboard_admin.php');
                    } else {
                        header('Location: catalogo.php');
                    }
                    exit;

                } else {
                    $errore = "Codice TOTP non valido o scaduto.";
                }
            } else {
                $errore = "Nickname o Password errati.";
            }
        } catch (Exception $e) {
            $errore = "Errore durante l'accesso: " . $e->getMessage();
        }
    }
   
    if(isset($conn) && $conn->ping()) {
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Azienda Agricola</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="form-section">
            <h2>Accedi al tuo account</h2>
            <p>Effettua il login per accedere al tuo account e gestire i tuoi ordini.</p>
            
            <?php if ($errore !== ''): ?>
                <p style="color: red; font-weight: bold;"><?php echo $errore; ?></p>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="nickname">Nickname</label>
                    <input type="text" id="nickname" name="nickname" placeholder="es. mario99" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="totp">Codice TOTP (dall'app):</label>
                    <input type="text" id="totp" name="totp" placeholder="es. 123456" required>
                </div>

                <button type="submit">Accedi</button>

                <p style="font-size: 0.9em; margin-top: 15px;">
                    Genera codice TOTP su: <a href="http://localhost:8082" target="_blank">2FAuth</a>
                </p>    
            </form>
            <p class="login-link">
                Non hai già un account? <a href="index.php">Registrati qui</a>
            </p>
        </div>
    </div>
</body>
</html>