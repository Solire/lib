<?php
/**
 * Gestionnaire des sessions.
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib;

/**
 * Gestionnaire des sessions.
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Session
{
    /**
     * Informations sur le compte lié à la session.
     *
     * @var array
     */
    protected $user;

    /**
     * Nom du cookie.
     *
     * @var string
     */
    protected $cookieName;

    /**
     * Etat de la connexion.
     *
     * @var bool
     */
    protected $connected = false;

    /**
     * Configuration de la session.
     *
     * @var array
     */
    protected $config;

    /**
     * Initialise une session de type $sessionCode.
     *
     * @param string $sessionCode Code d'identification du type de sessions
     * @param string $appName     Nom de l'application ayant le fichier de
     *                            configuration de la sessions, laisser vide pour prendre l'application
     *                            courante
     *
     * @throws Exception\Lib
     * @config main [format] session Format du bloc session dans la config main
     *
     * @uses Session->regen()
     */
    public function __construct($sessionCode, $appName = true)
    {
        $config = Registry::get('mainconfig');
        $format = $config->get('format', 'session');

        if (empty($format)) {
            throw new Exception\Lib('Aucune configuration format des sessions');
        }

        $sessionCode = sprintf($format, $sessionCode);

        $dir = $config->get('dirs', 'config') . $sessionCode;
        $path = FrontController::search($dir, $appName);
        unset($dir, $format);

        if (empty($path)) {
            throw new Exception\Lib('Aucune configuration pour la session [' . $sessionCode . ']');
        }

        $conf = new Config($path);

        $this->config = $conf->get('core');
        $this->cookieName = $this->config['cookie'];
        unset($conf);

        if (isset($_COOKIE[$this->cookieName]) && !empty($_COOKIE[$this->cookieName])) {
            $foo = explode('_', $_COOKIE[$this->cookieName]);

            if (count($foo) == 2) {
                /** @var MyPDO $db */
                $db = Registry::get('db');
                $query = $db->prepare($this->config['query']);
                $query->bindValue(':id', $foo[1], \PDO::PARAM_INT);
                $query->execute();
                $user = $query->fetch(\PDO::FETCH_ASSOC);

                $token = $this->makeToken($user['login'], date('m-d'), $user['id']);

                if ($token == $foo[0]) {
                    $this->connected = true;
                    $this->user = $this->presentVars($user);
                    $this->oldData = $user;
                    $this->regen();

                    return;
                }
            }
        }

        $this->disconnect();
    }

    /**
     * Renvois les informations sur la session en cours.
     *
     * @param string $key Nom de la variable à renvoyer
     *
     * @return mixed
     */
    public function getUser($key = null)
    {
        if ($key != null) {
            return $this->user[$key];
        }

        return $this->user;
    }

    /**
     * Test si il y a une connection en cours
     * Renvois vrai si c'est le cas.
     *
     * @return bool
     */
    public function isConnected()
    {
        return $this->connected;
    }

    /**
     * Génère le token de la session à partir des paramètres.
     *
     * @return string
     */
    protected function makeToken()
    {
        $foo = func_get_args();
        $token = implode(', ', $foo);
        $token = hash('md5', $token);

        return $token;
    }

    /**
     * Relance le temps de garde de la session.
     *
     * @return void
     */
    protected function regen()
    {
        if (isset($_COOKIE[$this->cookieName]) && !empty($_COOKIE[$this->cookieName])) {
            $life = time() + $this->config['duration'];
            setcookie(
                $this->cookieName,
                $_COOKIE[$this->cookieName],
                $life,
                '/'
            );
        }
    }

    /**
     * Génère un mot de passe.
     *
     * @param int $longueur Longueur du mot de passe
     *
     * @return string
     */
    public static function makePass($longueur = 9)
    {
        $caractere = '0123456789abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJK'
                   . 'LMNOPQRSTUVWXYZ0123456789';
        $long = strlen($caractere);
        $mdp = '';
        for ($i = 1; $i < $longueur; $i++) {
            $mdp .= substr($caractere, rand(0, $long), 1);
        }

        return $mdp;
    }

    /**
     * "Sale" et fait un sha256 du mot de passe.
     *
     * @param string $mdp Mot de passe du client
     *
     * @return string
     */
    final public static function prepareMdp($mdp)
    {
        return password_hash($mdp, PASSWORD_BCRYPT);
    }

    /**
     * Format le nom des variable pour qu'il soit compatible avec la notation camel.
     *
     * @param array $array Tableau de valeurs sortie de la bdd
     *
     * @return array
     */
    final private function presentVars(array $array)
    {
        $result = [];
        foreach ($array as $key => $value) {
            $key = strtolower($key);
            if (strpos($key, '_') === false) {
                $result[$key] = $value;
                continue;
            }
            preg_match_all('#_([a-z])#', $key, $match);

            if (empty($match)) {
                $result[$key] = $value;
                continue;
            }

            for ($i = 0; $i < count($match[0]); $i++) {
                $replace = strtoupper($match[1][$i]);
                $key = str_replace($match[0][$i], $replace, $key);
            }

            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Crée une session à partir du couple Courriel / Mot de passe.
     *
     * @param string $login    Courriel de la session
     * @param string $password Mot de passe de la session
     *
     * @return bool
     *
     * @throws Exception\Lib
     * @throws Exception\User
     */
    public function connect($login, $password)
    {
        if ($this->connected) {
            return $this->connected;
        }

        if (!is_string($login) || !is_string($password)) {
            throw new Exception\Lib('Format du Courriel / Mot de passe incorrect');
        }

        /** @var MyPDO $db */
        $db = Registry::get('db');
        $query = $db->prepare($this->config['queryLogin']);
        $query->bindValue(':login', $login, \PDO::PARAM_STR);
        $query->execute();
        $user = $query->fetch(\PDO::FETCH_ASSOC);

        if (password_verify($password, $user['pass']) !== true) {
            throw new Exception\User('Couple Courriel / Mot de passe incorrect');
        }

        $token = $this->makeToken($user['login'], date('m-d'), $user['id']);
        $cookie = $token . '_' . $user['id'];

        $life = time() + $this->config['duration'];
        if (setcookie($this->cookieName, $cookie, $life, '/')) {
            $this->user = $user;
            $this->connected = true;
        } else {
            throw new Exception\Lib('Impossible d\'enregistrer un cookie');
        }

        return $this->connected;
    }

    /**
     * Désactive la session.
     *
     * @return void
     */
    public function disconnect()
    {
        $this->connected = false;
        setcookie($this->cookieName, '', time() - 42, '/');
    }

    /**
     * Contrôle d'existence d'une variable de la session.
     *
     * @param string $name Nom de la variable
     *
     * @return bool
     */
    public function __isset($name)
    {
        if (isset($this->user[$name]) && !empty($this->user[$name])) {
            return true;
        }

        return false;
    }

    /**
     * Récupération des variables de la session
     * Fonction présente pour rétro compatibilité, ne pas s'en servir.
     *
     * @param string $name Nom de la variable
     *
     * @return mixed
     *
     * @deprecated
     */
    public function get($name)
    {
        if (is_array($this->oldData) && array_key_exists($name, $this->oldData)) {
            return $this->oldData[$name];
        }

        return null;
    }

    /**
     * Récupération des variables de la session.
     *
     * @param string $name Nom de la variable
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->user[$name];
    }

    /**
     * Met à jour une clé de sécurité en BDD et la renvoi.
     *
     * @param string $login Identifiant de l'utilisateur
     *
     * @return bool|string false si l'identifiant n'existe pas en BDD.
     */
    public function genKey($login)
    {
        /** @var MyPDO $db */
        $db = Registry::get('db');

        $query = $db->prepare($this->config['queryLogin']);
        $query->bindValue(':login', $login, \PDO::PARAM_STR);
        $query->execute();
        $user = $query->fetch(\PDO::FETCH_ASSOC);

        if ($user) {
            $cle = self::makePass(32);
            $cleBdd = openssl_digest($cle, 'sha512');

            $query = $db->prepare($this->config['queryNewKey']);
            $query->bindValue(':key', $cleBdd, \PDO::PARAM_STR);
            $query->bindValue(':id', $user['id'], \PDO::PARAM_INT);
            $query->execute();

            return $cle;
        }

        return false;
    }

    /**
     * Vérifie la clé de sécurité et génère un nouveau mot de passe qui est renvoyé.
     *
     * @param string $cle   Clé de vérification
     * @param string $login Identifiant de l'utilisateur
     *
     * @return bool|string false si le couple clé / identifiant ne fonctionne pas
     *                     sinon le nouveau mot de passe
     */
    public function newPassword($cle, $login)
    {
        /** @var MyPDO $db */
        $db = Registry::get('db');

        $query = $db->prepare($this->config['queryKey']);
        $query->bindValue(':login', $login, \PDO::PARAM_STR);
        $query->bindValue(':key', openssl_digest($cle, 'sha512'), \PDO::PARAM_STR);
        $query->execute();
        $user = $query->fetch(\PDO::FETCH_ASSOC);

        if ($user) {
            $mdp = self::makePass(8);
            $mdpBdd = self::prepareMdp($mdp);

            $query = $db->prepare($this->config['queryNewPass']);
            $query->bindValue(':pass', $mdpBdd, \PDO::PARAM_STR);
            $query->bindValue(':id', $user['id'], \PDO::PARAM_INT);
            $query->execute();

            return $mdp;
        }

        return false;
    }

    /**
     * Vérifie la clé de sécurité.
     *
     * @param string $cle   Clé de vérification
     * @param string $login Identifiant de l'utilisateur
     *
     * @return bool false si le couple clé / identifiant ne fonctionne pas
     *              sinon true
     */
    public function checkKey($cle, $login)
    {
        /** @var MyPDO $db */
        $db = Registry::get('db');

        $query = $db->prepare($this->config['queryKey']);
        $query->bindValue(':login', $login, \PDO::PARAM_STR);
        $query->bindValue(':key', openssl_digest($cle, 'sha512'), \PDO::PARAM_STR);
        $query->execute();
        $user = $query->fetch(\PDO::FETCH_ASSOC);

        if ($user) {
            return true;
        }

        return false;
    }
}
