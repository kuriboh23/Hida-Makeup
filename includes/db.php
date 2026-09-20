<?php
/**
 * HIDA.MAKEUP - Database Connection (PDO)
 * 
 * Provides a secure, persistent PDO database connection using settings from config.php.
 * Includes prepared-statement protection and graceful error handling for XAMPP.
 */

require_once __DIR__ . '/config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch (PDOException $e) {
    // If the database has not been created yet or MySQL is stopped
    $error_message = $e->getMessage();
    
    // Friendly error screen for development in XAMPP
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Configuration Base de Données - <?= htmlspecialchars(SITE_NAME) ?></title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                background: #fbf5f3;
                color: #242020;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                margin: 0;
                padding: 20px;
            }
            .card {
                background: #ffffff;
                border: 1px solid #eee5e1;
                border-radius: 12px;
                padding: 35px;
                max-width: 580px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            }
            h1 {
                font-size: 22px;
                color: #7d3638;
                margin-top: 0;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            p {
                font-size: 14px;
                line-height: 1.6;
                color: #514b49;
            }
            .error-box {
                background: #fff3f3;
                border-left: 4px solid #d9534f;
                padding: 12px 16px;
                font-family: monospace;
                font-size: 13px;
                color: #a94442;
                margin: 20px 0;
                overflow-x: auto;
            }
            ol {
                font-size: 13px;
                color: #514b49;
                line-height: 1.8;
                padding-left: 20px;
            }
            code {
                background: #f5e7e4;
                color: #7d3638;
                padding: 2px 6px;
                border-radius: 4px;
                font-size: 12px;
            }
        </style>
    </head>
    <body>
        <div class="card">
            <h1>Connexion Base de Données</h1>
            <p>Impossible de se connecter à la base de données MySQL avec les paramètres actuels.</p>
            <div class="error-box"><?= htmlspecialchars($error_message) ?></div>
            <p><strong>Instructions pour XAMPP :</strong></p>
            <ol>
                <li>Ouvrez le <strong>Panneau de Contrôle XAMPP</strong> et démarrez le service <strong>MySQL</strong>.</li>
                <li>Ouvrez <a href="http://localhost/phpmyadmin" target="_blank">phpMyAdmin</a>.</li>
                <li>Créez une base de données nommée <code><?= htmlspecialchars(DB_NAME) ?></code> avec l'interclassement <code>utf8mb4_unicode_ci</code>.</li>
                <li>Importez le fichier SQL prêt à l'emploi : <code>database/hida.sql</code>.</li>
                <li>Actualisez cette page.</li>
            </ol>
        </div>
    </body>
    </html>
    <?php
    exit;
}
