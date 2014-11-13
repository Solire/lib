<?php
/**
 * Gestionnaire de connexion à la base de données
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib;

/**
 * Gestionnaire de connexion à la base de données
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class DB
{

    /**
     * Contient les objets PDO de connection
     *
     * @var array
     */
    static private $present;

    /**
     * Parametrage de base
     *
     * @var array
     */
    static private $config = array(
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    );

    /**
     * Inutilisé
     *
     * @ignore
     */
    private function __construct()
    {

    }

    /**
     * Crée une connection à la base de données.
     *
     * @param array  $ini         Doit être sous la forme :<ul>
     * <li>dsn => ''        // chaine de connexion propre à pdo, par exemple :
     * "mysql:dbname=%s;host=%s" ou "mysql:dbname=%s;host=%s;port=%s"</li>
     * <li>host => ''       // host de la connexion à la bdd</li>
     * <li>dbname => ''     // Nom de la base de données</li>
     * <li>user => ''       // utilisateur mysql</li>
     * <li>password => ''   // mot de passe</li>
     * <li>port => ''       // [facultatif], port de la connexion</li>
     * <li>utf8 => true     // [facultatif], activer encodage buffer sortie</li>
     * <li>error => true    // [facultatif], activer les erreurs pdo</li>
     * <li>profil => false  // [facultatif], activer le profiling</li>
     * <li>nocache => false // [facultatif], désactiver le cache</li>
     * </ul>
     * @param string $otherDbName Nom de la base de données dans le cas où l'on
     * veut se connecter à une difference de celle présente dans $ini
     *
     * @return MyPDO
     */
    public static function factory($ini, $otherDbName = null)
    {
        if ($otherDbName) {
            $ini['dbname'] = $otherDbName;
        }

        if (isset(self::$present[$ini['name']])) {
            return self::$present[$ini['name']];
        }


        $dsn = sprintf(
            $ini['dsn'],
            $ini['dbname'],
            $ini['host'],
            $ini['port']
        );

        self::$present[$ini['name']] = new MyPDO(
            $dsn,
            $ini['user'],
            $ini['password'],
            self::$config
        );


        /**
         * Option d'affichage des erreurs
         * Parametrable dans le config.ini de la bdd
         */
        if (isset($ini['error']) && $ini['error'] == true) {
            self::$present[$ini['name']]->setAttribute(
                \PDO::ATTR_ERRMODE,
                \PDO::ERRMODE_EXCEPTION
            );
        }

        /** Profiling */
        if (isset($ini['profil']) && $ini['profil'] == true) {
            self::$present[$ini['name']]->exec('SET profiling = 1;');
        }

        /**
         * Spécifique à mysql
         * Modifie l'encodage du buffer de sortie de la base qui est par
         * defaut en ISO pour être en accord avec l'encodage de la base.
         */
//        if (isset($ini['utf8']) && $ini['utf8'] == true) {
            self::$present[$ini['name']]->exec('SET NAMES UTF8');
//        }

        /** Cache */
        if (isset($ini['nocache']) && $ini['nocache'] == true) {
            self::$present[$ini['name']]->exec('SET SESSION query_cache_type = OFF;');
        }

        return self::$present[$ini['name']];
    }

    /**
     * Renvois la connexion à la base déjà paramétré
     *
     * @param string $dbName Nom de la base de données
     *
     * @return \PDO
     * @throws LibExeception Si il n'y a pas de bdd répondant au nom $dbName
     */
    final public static function get($dbName)
    {
        if (isset(self::$present[$dbName])) {
            return self::$present[$dbName];
        }

        throw new Exception\Lib('Aucune connexion sous le nom ' . $dbName);
    }
}
