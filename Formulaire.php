<?php
/**
 * Module de gestion de formulaires
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib;

/**
 * Contrôle des formulaires
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Formulaire
{
    /**
     * Force le retour de run() sous forme d'une liste
     */
    const FORMAT_LIST = 2;

    /**
     * Ordre dans lesquels les tableaux sont mergés
     *
     * p pour $POST
     * g pour $GET
     * c pour $COOKIE
     *
     * @var string
     */
    protected $ordre = 'cgp';

    /**
     * Liste des plugins
     *
     * @var array
     */
    protected $plugins;

    /**
     * tableau des paramètres du formulaire et de leurs options.
     *
     * @var array
     */
    protected $architecture;

    /**
     * valeur --config dans le fichier de configuration du formulaire
     *
     * @var array
     */
    protected $config;

    /**
     * Données du formulaire
     *
     * @var array
     */
    protected $data;

    /**
     * toutes les données
     *
     * @var array
     */
    protected $fullData;

    /**
     * Nom du champ en cours d'annalyse
     *
     * @var string
     */
    protected $target = '';

    /**
     * Liste des noms des champs du formulaire
     *
     * @var array
     */
    protected $inputNames = [];

    /**
     * Charge un nouveau formulaire
     *
     * Pour comprendre la configuration voici un exmple de .ini
     * ;; Configuration générale du formulaire
     * [__config]
     * ;; Option pour prendre en compte le préfixage de tous les champs du formulaire
     * ;; Chaque [nom] (ou designe) sera préfixé par cette chaine
     * prefix = C
     *
     * ;; chaine d'ordre d'utilisation des variables $GET $POST $COOKIE
     * ;; définie l'ordre dans lequel ces tableaux sont passés dans la fonction merge
     * ;; exemple : gpc mettera les cookie prioritaires sur les posts qui seront
     * ;; prioritaires sur les get
     * ordre = p
     *
     * ;; Exception utilisée, faute de précision au niveau du champ pour ce formulaire.
     * exception = UserException
     *
     * ;; Fonction appellée lors d'une erreur
     * appelFonction = "CompteController::erreurInscription"
     *
     * ;; Les champs sont à parametrer de cette façon :
     * ;; Nom de la variable
     * [_exemple]
     * ;; Nom des tests (voir param.php pour les connaitre) dans une chaine
     * ;; séparés par |
     * test = ""
     *
     * ;; Variable obligatoire ou non, si elle est obligatoire, en cas d'erreur ou
     * ;; d'oublie une exceptions era envoyée, sinon elle sera simplement ignorée du
     * ;; tableau de retour
     * ;; Valeurs Possible : boolean
     * obligatoire = true
     *
     * ;; Message d'erreur si la variable n'est pas présente ou mal renseignée
     * ;; Valeurs Possible : string
     * erreur = "Message d'erreur à renseigner"
     *
     * ;; Nom dans le tableau de sortie de la variable
     * ;; ([nom] sera utilisé par défaut si rien n'est précisé)
     * ;; Valeurs Possible : string
     * renomme = "valeur de retour"
     *
     * ;; Nom dans le tableau d'entrée de la variable
     * ;; ([nom] sera utilisé par défaut si rien n'est précisé)
     * ;; Valeurs Possible : string
     * designe = "Nom du champ dans le formulaire"
     *
     * ;; Exception envoyée si le champ ne répond pas aux critères
     * ;; Valeurs Possible : string (Nom des objets exception)
     * exception = "Exception"
     *
     * ;; Si le champ est validé, il passe le ou les champs désignées en obligatoire
     * ;; Les autres champs doivent obligatoirement être après dans la liste
     * ;; de contrôle.
     * ;; Valeurs Possible : string (nom du ou des champs séparés par |)
     * force = "code"
     *
     * ;; Nom du champ dans le tableau de sortie (soit [nom] ou renomme) auquel le
     * ;; champ doit être égal.
     * ;; Valeurs Possible : string (nom du champs)
     * egal = "code"
     *
     * @param array|string $iniPath  Array contenant l'architecture ou le chemin du .ini
     * @param boolean      $complete Si le chemin est absolu
     *
     * @config main [dirs] "formulaire" Chemin du dossier des .ini d'architecture
     */
    public function __construct($iniPath, $complete = false)
    {
        $config = Registry::get('mainconfig');
        if (!is_array($iniPath)) {
            if (!$complete) {
                $iniPath = $config->get('dirs', 'formulaire') . $iniPath;
            }
            $iniPath = new Path($iniPath);
            $architecture = new Config($iniPath->get());
            $this->architecture = $architecture->getAll();
            $this->config = $architecture->getConfig();
            unset($architecture);
        } else {
            $this->architecture = $iniPath;
        }

        $this->parseArchi();
    }

    /**
     * Parcour l'architecture pour y trouver la configuration générale
     * et sortir le cas d'exemple
     *
     * @return boolean
     */
    protected function parseArchi()
    {
        if (isset($this->architecture[\Solire\Lib\Config::KEY_CONF])) {
            $this->config = $this->architecture[\Solire\Lib\Config::KEY_CONF];
            unset($this->architecture[\Solire\Lib\Config::KEY_CONF]);
        }

        if (isset($this->config['ordre'])) {
            $this->ordre = $this->config['ordre'];
        }

        /** Récupération des plugin **/
        if (isset($this->config['plugins'])) {
            $this->plugins = explode('|', $this->config['plugins']);
        }

        /* = Suppression d'_exemple
        `------------------------------------------------- */
        if (isset($this->architecture['_exemple'])) {
            unset($this->architecture['_exemple']);
        }

        return true;
    }

    /**
     * Supprime une option de l'architecture
     *
     * Utile si l'on veut se servir que partiellement d'un .ini par exemple
     *
     * @param string $name Nom du champ à oublier
     *
     * @return boolean Vrai si l'élément était présent
     */
    public function archiUnset($name)
    {
        if (!isset($this->architecture[$name])) {
            return false;
        }

        unset($this->architecture[$name]);

        return true;
    }

    /**
     * Edition de la configuration du formulaire
     *
     * @param array   $newConfig Tableau associatif de la nouvelle configuration
     * @param boolean $replace   Si vrais, la nouvelle configuration remplace l'ancienne,
     * sinon il y a un merge des deux tableaux
     *
     * @return void
     */
    public function alterConfig(array $newConfig, $replace = false)
    {
        if ($replace) {
            $this->config = $newConfig;
        } else {
            $this->config = array_merge($this->config, $newConfig);
        }
    }

    /**
     * Traite le formulaire pour en renvoyer les données vérifiées
     *
     * @return array tableau des données du formulaire
     *
     * @throws Exception\Lib  En cas d'erreurs dans la configuration du formulaire
     * @throws Exception\User Si le formulaire est mal remplis
     *
     * @uses Formulaire::catchData()
     * @uses Formulaire::get()
     */
    public function run()
    {
        $this->fullData = $this->catchData();
        $configuration = $this->architecture;

        /* = On utilise cette formulation plutot que foreach parce que
         * $configuration peut évoluer dans la boucle. (et que dans un foreach
         * cela n'est pas pris en compte)
          ------------------------------- */
        reset($configuration);
        while (list($name, $regles) = each($configuration)) {
            /* = Gestion des prefix dans le formulaire
            `------------------------------------------- */
            $this->target = $name;
            if (isset($regles['designe'])) {
                $this->target = $regles['designe'];
            }


            if (isset($this->config['prefix'])) {
                $this->target = $this->config['prefix'] . $target;
            }

            $this->inputNames[] = $this->target;
            $temp = $this->get($this->target);

            /* = Si la variable n'est pas présente
            `------------------------------------ */
            if ($temp == null) {
                if ($regles['obligatoire'] == true) {
                    $this->throwError($regles);
                }

                continue;
            }

            $options = explode('|', $regles['test']);

            /* = Test si le fichier de configuration est au bon format
            `--------------------------------------------------------- */
            if (!is_array($options)) {
                throw new Exception\Lib("Config : Opt n'est pas un tableau");
            }

            /* = Si la variable ne passe pas les testes
            | on retourne un message d'erreur si celle-ci est
            | obligatoire, sinon, on l'ignore simplement.
            `---------------------------------------- */
            if (!$temp->tests($options)) {
                if ($regles['obligatoire'] == true) {
                    $this->throwError($regles);
                }

                continue;
            }

            if (isset($regles['renomme'])) {
                $name = $regles['renomme'];
            }

            $this->data[$name] = $temp->get();
            unset($temp);

            /* = Passage en obligatoire des champs liés
              ------------------------------- */
            if (isset($regles['force'])) {
                $champs = explode('|', $regles['force']);
                foreach ($champs as $champ) {
                    $configuration[$champ]['obligatoire'] = true;
                }
                unset($champs, $champ);
            }

            /* = Contrôle d'egalité du champ
              ------------------------------- */
            if (isset($regles['egal'])) {
                if ($this->data[$name] != $this->data[$regles['egal']]) {
                    $this->throwError($regles);
                }
            }
        }

        if (!empty($this->plugins)) {
            foreach ($this->plugins as $plugin) {
                if (in_array('Solire\Lib\Formulaire\PluginInterface', class_implements($plugin))) {
                    $plugin::form($this->data);
                } else {
                    $this->throwError(array('erreur' => 'plugin incompatible'));
                }
            }
        }

        $options = func_get_args();
        if (!empty($options)) {
            if ($options[0] == self::FORMAT_LIST) {
                return $this->getList();
            }
        }

        return $this->data;
    }

    /**
     * Renvois les données collectées par le formulaire sous la forme d'un tableau
     *
     * @return array Tableau non associatif des valeurs
     */
    public function getList()
    {
        $list = array();
        foreach ($this->data as $value) {
            $list[] = $value;
        }

        return $list;
    }

    /**
     * Génère une requête SQL pour que le contenu du formulaire puisse être inséré en base
     *
     * la table est à préciser pendant l'appel de la fonction ou dans le fichier
     * de configuration
     *
     * @param \PDO   $db    Connection à la bdd
     * @param string $table Nom de la table dans lequel faire l'insertion
     *
     * @return string
     *
     * @deprecated
     */
    public function makeQuery(\PDO $db, $table = null)
    {
        if (empty($table) && isset($this->config['table'])) {
            $table = $this->config['table'];
        }
        $query = 'DESC ' . $table;
        $archi = $db->query($query)->fetchAll(\PDO::FETCH_COLUMN, 0);

        $values = array();
        foreach ($archi as $col) {
            if (isset($this->data[$col])) {
                $values[] = $col . ' = ' . $db->quote($this->data[$col]);
            }
        }

        $query = 'INSERT INTO ' . $table . ' SET ' . implode(', ', $values);

        return $query;
    }

    /**
     * Envois l'exception de l'erreur
     *
     * Le type d'exception envoyé peut être paramétré à deux endroits, (voir le
     * fichier de configuration) au niveau du champ, ou au niveau du formulaire.
     * <br/>Par défaut une {@link Exception\User} est envoyée.
     *
     * @param array $regles Tableau associatif de règles pour la gestion d'erreurs
     *
     * @return void
     * @throws mixed
     * @throws Exception\User Si il y a une erreur dans le formulaire
     *
     * @todo faire un tutorial expliquant le paramétrage des champs d'un formulaire
     */
    protected function throwError($regles)
    {
        $error = null;

        if (!isset($regles['erreur'])) {
            $regles['erreur'] = '';
        }

        if (isset($regles['exception'])) {
            /* = Exception personnalisée au niveau du champ
            ------------------------------- */
            $error = new $regles['exception']($regles['erreur']);
        } elseif (isset($this->config['exception'])) {
            /* = Exception personnalisée au niveau du formulaire
            ------------------------------- */
            $error = new $this->config['exception']($regles['erreur']);
        } else {
            $error = new Exception\User($regles['erreur']);

            /* = Par défaut on redirige vers la page précédente
              ------------------------------- */
            if (isset($SERVER['HTTP_REFERER'])) {
                $error->link($SERVER['HTTP_REFERER'], 1);
            }
        }

        if (method_exists($error, 'setErrorInputName')) {
            $error->setErrorInputName($this->target);
        }

        if (isset($this->config['appelFonction'])) {
            if (is_callable($this->config['appelFonction'])) {
                $error = call_user_func(
                    $this->config['appelFonction'],
                    $this,
                    $error
                );
            }
        }
        throw $error;
    }

    /**
     * Récupère les données GET POST COOKIE
     *
     * @return array
     * @uses Formulaire::$ordre
     */
    protected function catchData()
    {
        $datas = array(
            'g' => $GET,
            'p' => $POST,
            'c' => $COOKIE,
        );

        $result = array();
        for ($i = 0; $i < strlen($this->ordre); $i++) {
            $lettre = $this->ordre[$i];
            if (isset($datas[$lettre]) && !empty($datas[$lettre])) {
                $result = array_merge($result, $datas[$lettre]);
            }
        }
        return $result;
    }

    /**
     * Renvois la liste des champs input du formulaire
     *
     * @return array
     */
    public function getInputNamesList()
    {
        return $this->inputNames;
    }

    /**
     * Renvois le paramètre du nom $key sous la forme d'un objet Param
     *
     * @param string $key Nom du paramètre
     *
     * @return Param|null
     */
    protected function get($key)
    {
        if (isset($this->fullData[$key])) {
            return new Param($this->fullData[$key]);
        } else {
            return null;
        }
    }


    /**
     * Renvois les données collectées par le formulaire sous la forme
     * d'un tableau associatif
     *
     * @return array
     */
    public function getArray()
    {
        return $this->data;
    }


    /**
     * __get() est sollicitée pour lire des données depuis des propriétés inaccessibles
     *
     * Cette focntion permet d'appeller les variables du formulaire directement par $obj->var
     *
     * @param string $name Nom de la variable
     *
     * @return null
     * @ignore
     */
    public function __get($name)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        return null;
    }

    /**
     * __isset() est sollicitée pour tester des données depuis des propriétés inaccessibles
     *
     * Cette fonction permet de tester (isset()) les variables
     *
     * @param string $name Nom de la variable
     *
     * @return boolean
     * @ignore
     */
    public function __isset($name)
    {
        if (isset($this->data[$name])) {
            return true;
        }

        return false;
    }
}
