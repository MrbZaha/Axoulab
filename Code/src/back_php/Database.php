<?php
// Database.php
session_start(); // Important : démarrer la session une seule fois par script

class Database
{
    private static $host = 'localhost';
    private static $port = 3306;
    private static $dbname = 'projet_site_web';
    private static $charset = 'utf8';

    /**
     * Connecte avec des identifiants passés en paramètres et les enregistre en session
     */
    public static function connect(string $user, string $pass): PDO
    {
        // Stocke dans la session pour réutilisation
        $_SESSION['db_user'] = $user;
        $_SESSION['db_pass'] = $pass;

        return self::createPDO($user, $pass);
    }

    /**
     * Connecte en utilisant les identifiants stockés en session
     */
    public static function connectFromSession(): PDO
    {
        if (!isset($_SESSION['db_user']) || !isset($_SESSION['db_pass'])) {
            throw new Exception("Les identifiants MySQL ne sont pas définis en session. Utilisez Database::connect(user, pass) d'abord.");
        }
        return self::createPDO($_SESSION['db_user'], $_SESSION['db_pass']);
    }

    /**
     * Méthode interne pour créer l'objet PDO
     */
    private static function createPDO(string $user, string $pass): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            self::$host,
            self::$port,
            self::$dbname,
            self::$charset
        );

        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
?>