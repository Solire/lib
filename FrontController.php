<?php
/**
 * Front controller
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib;

use Solire\Conf\Loader;
use Solire\Lib\Exception\HttpError;
use Solire\Lib\Filesystem\FileLocator;
use Solire\Lib\Http\Request;
use Solire\Lib\Loader\Css;
use Solire\Lib\Loader\Javascript;
use Solire\Lib\Loader\Img;
use Solire\Lib\View\View;
use Solire\Lib\Application\Filesystem\FileLocator as ApplicationFileLocator;
use Solire\Lib\View\Filesystem\FileLocator as ViewFileLocator;

/**
 * Front controller
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class FrontController
{
    /**
     * Configuration principale du site
     *
     * @var Config
     */
    public static $mainConfig;

    /**
     * Configuration de l'environnement utilisé
     *
     * @var Config
     */
    public static $envConfig;

    /**
     * Nom de l'application en cours d'utilisation (exemple "Front",
     * "Catalogue")
     *
     * @var string
     */
    public static $appName;

    /**
     * Préfixe url pour l'application (exemple "catalogue")
     *
     * @var string
     */
    public static $appUrl = '';

    /**
     * Id api utilisé par page du front
     * @var int
     */
    public static $idApiRew = 1;

    /**
     * Liste des répertoires app à utiliser
     *
     * @var array
     */
    protected static $sourceDirectories = [];

    /**
     * Liste des répertoires app à utiliser
     *
     * @var array
     */
    public static $publicDirs = [];

    /**
     * Nom du controller utilisé
     *
     * @var string
     */
    public $controller = '';

    /**
     * Nom de l'application utilisée
     *
     * @var string
     */
    public $application = '';

    /**
     * Dossier de app utilisé. (source)
     *
     * @var string
     */
    public $source = '';

    /**
     * Nom de l'action utilisée
     *
     * @var string
     */
    public $action = '';

    /**
     * Tableau des éléments de rewriting présents dans l'url
     *
     * @var array
     */
    protected $rewriting = [];

    /**
     *
     *
     * @var self
     */
    private static $singleton = null;

    /**
     * Indicateur pour ne faire qu'une fois la configuration d'api
     *
     * @var boolean
     */
    private static $singleApi = false;

    private $dirs = null;
    private $format = null;
    private $debug = null;

    /**
     * Traduction des textes statiques
     *
     * @var TranslateMysql
     */
    private $translate = false;

    /**
     * Vue
     *
     * @var View
     */
    private $view = false;

    /**
     * Loader des librairies javascript
     *
     * @var Javascript
     */
    private $loaderJs = false;

    /**
     * Loader des librairies css
     *
     * @var Css
     */
    private $loaderCss = false;

    /**
     * Loader des librairies img
     *
     * @var Img
     */
    private $loaderImg = false;

    /**
     * FileLocator
     *
     * @var ApplicationFileLocator
     */
    private $fileLocator = null;

    /**
     * Représentation de la requête HTTP courante
     *
     * @var Request
     */
    private $request = null;

    /**
     * Instantiation du frontController
     */
    private function __construct()
    {
        $this->dirs = Registry::get('mainconfig')->get('dirs');
        $this->format = Registry::get('mainconfig')->get('format');
        $this->debug = Registry::get('mainconfig')->get('debug');

        /* Chargement du rep app par défaut */
        $count = count(self::$sourceDirectories);
        $this->source = self::$sourceDirectories[$count - 1];
        unset($count);

        /* Création de notre objet Request */
        $this->request = new Request();

        /* Création du FileLocator pour la recherche dans les applications */
        $this->fileLocator = new ApplicationFileLocator(self::$sourceDirectories);

        // On fournit au Registre le filelocator et la request
        Registry::set('request', $this->request);
        Registry::set('fileLocator', $this->fileLocator);
    }

    /**
     * Renvois une instance du FrontController
     *
     * @return self
     */
    public static function getInstance()
    {
        if (!self::$singleton) {
            self::$singleton = new self();
        }
        return self::$singleton;
    }

    /**
     * Renvois le tableau des répertoires app
     *
     * @return array
     */
    public static function getSourceDirectories()
    {
        return self::$sourceDirectories;
    }

    /**
     * Initialise les données nécessaires pour FrontController
     *
     * @throws Exception\Lib
     * @return void
     */
    public static function init()
    {
        /* Création du FileLocator pour les fichiers de configuration main et env */
        $configFileLocator = new FileLocator(['./']);

        /* Chargement de la configuration */
        $mainConfigFilePath = $configFileLocator->locate('config/main.yml');
        $envConfigFilePath  = $configFileLocator->locate('config/local.yml');

        $mainConfig = Loader::load($mainConfigFilePath);
        $envConfig  = Loader::load($envConfigFilePath);

        /* Fichiers de configuration */
        Registry::set('mainconfig', $mainConfig);
        Registry::set('envconfig', $envConfig);

        /* On paramètre les applications à utiliser */
        if (count($mainConfig['applications']) == 0) {
            throw new Exception\Lib('Aucune application n\'a été configurée.');
        }

        self::setSourceDirectories($mainConfig['applications']);

        // Hook post paramètrages des applications
        $hook = new Hook();
        $hook->setSubdirName('Lib');

        $hook->exec('PostSetSourceDirectories');

        /* Chargement de la configuration */
        self::$mainConfig = Registry::get('mainconfig');
        self::$envConfig  = Registry::get('envconfig');

        /* Base de données */
        try {
            $db = DB::factory(Registry::get('envconfig')->get('database'));
        } catch (\PDOException $exc) {
            throw new Exception\Lib($exc->getMessage());
        }
        Registry::set('db', $db);

        Registry::set('project-name', Registry::get('mainconfig')->get('project', 'name'));
        $emails = Registry::get('envconfig')->get('email');

        /* Ajout d'un prefix au mail */
        if (isset($emails['prefix']) && $emails['prefix'] != '') {
            $prefix = $emails['prefix'];
            unset($emails['prefix']);
            foreach ($emails as &$email) {
                $email = $prefix . $email;
            }
        }
        Registry::set('email', $emails);
    }

    /**
     * Ajoute une partie de rewriting
     *
     * @param string $rewriting Parte de rewriting à ajouter
     *
     * @return void
     *
     * @throws HttpError
     * @uses Solire\Lib\Controller->acceptRew Contrôle si le
     * rewriting est accepté
     */
    private function addRewriting($rewriting)
    {
        $className = $this->getClassName();
        $class = new $className();
        if ($class->acceptRew !== true) {
            $exc = new HttpError('Erreur HTTP');
            $exc->http(404, null);
            throw $exc;
        }
        $this->rewriting[] = $rewriting;
    }

    /**
     * Renvois le nom de la classe du controller
     *
     * @param string $controller Nom du controller
     * @param string $app        Code du repertoire App à utiliser
     *
     * @return string
     */
    protected function getClassName($controller = null, $app = null)
    {
        if (!empty($app)) {
            $app = ucfirst($app);
        } else {
            $app = $this->source['namespace'];
        }
        $class = $app . '\\' . $this->application . '\\Controller\\';
        if (empty($controller)) {
            $class .= $this->controller;
        } else {
            $class .= $controller;
        }

        return $class;
    }

    /**
     * Lecture de l'url pour en extraire les données
     *
     * @return void
     */
    public function parseUrl()
    {
        /* Nom de l'application par défaut */
        $this->application = Registry::get('mainconfig')->get('project', 'defaultApp');
        self::$appName = $this->application;
        $this->fileLocator->setCurrentApplicationName(self::$appName);

        self::loadAppConfig();

        /* On met la valeur par défaut pour pouvoir tester l'app par défaut */
        $this->controller = $this->getDefault('controller');

        $this->rewriting = [];

        $controller = false;
        /* Contrôle du controller */
        $rewritingMod = false;
        if (isset($_GET['controller']) && !empty($_GET['controller'])) {
            $url = strtolower($_GET['controller']);
            $arrSelect = explode('/', $url);
            unset($url);

            $application = false;
            $rewritingMod = false;
            foreach ($arrSelect as $ctrl) {
                /*
                 * Si on est en mode rewriting,
                 * tout ce qui reste de l'url est du rewriting
                 */
                if ($rewritingMod === true) {
                    $this->addRewriting($ctrl);
                    continue;
                }

                /*
                 * Si le contrôleur n'est pas en minuscule
                 *  on considère que c'est un rewriting
                 */
                if ($ctrl != strtolower($ctrl)) {
                    $this->addRewriting($ctrl);
                    $rewritingMod = true;
                    continue;
                }

                /* On test l'existence du dossier app répondant au nom $ctrl */
                if ($this->testApp($ctrl) !== false) {
                    /* Si un application est déjà définie */
                    if ($application === true) {
                        $this->addRewriting($ctrl);
                        $rewritingMod = true;
                        continue;
                    }

                    $conf = self::loadAppConfig($ctrl);
                    $idApi = $conf->get('fx', 'idApi');
                    if (!empty($idApi)) {
                        self::$idApiRew = $idApi;
                        self::$appUrl = $ctrl;
                        unset($idApi, $conf);
                        continue;
                    }

                    $this->application = ucfirst($ctrl);
                    self::$appName = $this->application;
                    $this->source = $this->testApp($ctrl);
                    $application = true;
                    continue;
                }

                /* Test existence d'un controller */
                if ($this->classExists($ctrl)) {
                    if ($controller === true) {
                        $this->addRewriting($ctrl);
                        $rewritingMod = true;
                        continue;
                    }
                    $this->controller = ucfirst($ctrl);
                    $controller = true;
                    continue;
                }

                $this->addRewriting($ctrl);
                $rewritingMod = true;
            }

            /* Si l'application à changé on charge sa configuration */
            if ($application === true) {
                $this->fileLocator->setCurrentApplicationName(self::$appName);
                self::loadAppConfig();
            }
        }


        if ($controller === false) {
            $this->controller = $this->getDefault('controller');
            $this->classExists($this->controller);
        }

        if (isset($_GET['action']) && !empty($_GET['action'])) {
            if ($rewritingMod === true) {
                $this->addRewriting($_GET['action']);
                $this->action = $this->getDefault('action');
            } else {
                $class = $this->getClassName();
                $method = sprintf(
                    $this->getFormat('controller-action'),
                    $_GET['action']
                );
                if (method_exists($class, $method)) {
                    $this->action = $_GET['action'];
                } else {
                    $this->addRewriting($_GET['action']);
                    $this->action = $this->getDefault('action');
                }
            }
        } else {
            $this->action = $this->getDefault('action');
        }
    }

    /**
     * Cherche un fichier dans les applications
     *
     * @param string         $path    Chemin Chemin du dossier / fichier à chercher dans
     * les applications
     * @param string|boolean $appName Soit le nom de l'application, soit true
     * pour Utiliser le nom de l'application courante soit false pour ne pas
     * chercher dans une application
     *
     * @return string|boolean
     */
    final public static function search($path, $appName = true)
    {
        $front = self::getInstance();

        $type = ApplicationFileLocator::TYPE_ALL;

        if (!empty($appName)) {
            if ($appName === true) {
                $appName = null;
            }

            $type = ApplicationFileLocator::TYPE_APPLICATION;
        }

        return $front->fileLocator->locate($path, $type, $appName);
    }

    /**
     * Cherche une classe
     *
     * @param string $className Nom de la classe, avec les namespace, qui sera
     * préfixé par le nom de l'app
     *
     * @return string|boolean
     */
    final public static function searchClass($className)
    {
        foreach (self::$sourceDirectories as $app) {
            $testClass = $app['namespace'] . '\\' . $className;

            if (class_exists($testClass)) {
                return $testClass;
            }
        }

        return false;
    }

    /**
     * Charge la configuration relative à l'application
     *
     * @param string $test ?
     *
     * @return \Solire\Lib\Config|null
     */
    final public static function loadAppConfig($test = null)
    {
        if (empty($test)) {
            $confPath = self::search('conf.ini');
        } else {
            $confPath = self::search($test . Path::DS . 'conf.ini', false);
        }
        if (!empty($confPath)) {
            $appConfig = new Config($confPath);
            if (empty($test)) {
                Registry::set('appconfig', $appConfig);
            }

            return $appConfig;
        }

        return null;
    }

    /**
     * Test si le morceau d'url est une application
     *
     * @param string $ctrl Morceau d'url
     *
     * @return boolean|string false si ce n'est pas une application, sinon
     * renvoi le dir App
     */
    private function testApp($ctrl)
    {
        foreach (self::$sourceDirectories as $source) {
            $testPath = new Path($source['dir'] . Path::DS . $ctrl, Path::SILENT);
            if ($testPath->get()) {
                return $source;
            }
        }

        return false;
    }

    /**
     * Contrôle l'existence d'une classe controller
     *
     * @param string $ctrl Nom de la classe
     *
     * @return boolean
     */
    protected function classExists($ctrl)
    {
        foreach (self::$sourceDirectories as $source) {
            $class = $this->getClassName(ucfirst($ctrl), $source['namespace']);
            if (class_exists($class)) {
                $this->source = $source;
                return true;
            }
        }
        return false;
    }

    /**
     * Lance l'affichage de la page
     *
     * @param string $controller Nom du controller à lancer
     * @param string $action     Nom de l'action à lancer
     * @return bool
     * @throws Exception\Lib
     * @throws HttpError
     * @throws \Exception
     */
    public static function run($controller = null, $action = null)
    {
        $front = self::getInstance();
        if (empty($controller) && empty($action)) {
            $front->parseUrl();
        } else {
            /* Chargement du controller */
            $front->classExists($controller);
            $front->controller = $controller;

            /* Chargement de l'action */
            $front->action = $action;

            if (isset($front->view) && !empty($front->view)) {
                $defaultViewPath = strtolower($front->controller) . Path::DS . $front->action;
                $front->view->setViewPath($defaultViewPath);
                unset($defaultViewPath);
            }

            self::loadAppConfig();
        }
        unset($controller, $action);

        /*
         * Pour éviter les conflits lors de l'envois d'une 404 on ne charge les
         * informations relative à l'api
         */
        if (self::$singleApi === false) {
            $front->setAppConfig();
        }
        self::$singleApi = true;

        $front->setVersion();

        $class = $front->getClassName();
        $method = sprintf($front->getFormat('controller-action'), $front->action);
        if (!class_exists($class)) {
            $message = sprintf(
                'La classe de contrôleur "%s" n\'existe pas.',
                $class
            );
            throw new Exception\Lib($message);
        }

        if (!method_exists($class, $method)) {
            $front->rewriting[] = $front->action;
            $method = $front->getDefault('action');
            $method = sprintf($front->getFormat('controller-action'), $method);
            if (!method_exists($class, $method)) {
                $message = sprintf(
                    'Impossible de trouver  l\'action "%s" pour le contrôleur "%s".',
                    $class,
                    $method
                );
                $error = new Exception\HttpError($message);
                $error->http(404);
                throw $error;
            }
        }

        /*
         * On créé le controller
         */
        /* @var Controller $instance */
        $instance = new $class();

        $instance
            ->setView($front->loadView())
            ->setTranslate($front->loadTranslate())
            ->setRewriting($front->rewriting)
        ;

        $instance->start();
        $instance->$method();
        if ($front->view->isEnabled()) {
            $instance->shutdown();
            $front->view->display();
        }
        return true;
    }

    /**
     * Chargement de la classe de traduction
     *
     * @return \Solire\Lib\TranslateMysql
     */
    public function loadTranslate()
    {
        if ($this->translate !== false) {
            return $this->translate;
        }

        $this->translate = new TranslateMysql(ID_VERSION, ID_API, Registry::get('db'));
        $this->translate->addTranslation();
        Registry::set('translator', $this->translate);

        return $this->translate;
    }

    /**
     * Chargement de la vue
     *
     * @return View
     *
     * @throws Exception\Lib
     */
    public function loadView()
    {
        if ($this->view !== false) {
            return $this->view;
        }

        /* Création du FileLocator pour le chargement des templates */
        $appLibDir = Registry::get('mainconfig')->get('appLibDir');
        $viewFileLocator = new ViewFileLocator(self::$sourceDirectories, $appLibDir);
        $viewFileLocator->setCurrentApplicationName(self::$appName);
        Registry::set('viewFileLocator', $viewFileLocator);

        $this->view = new View($viewFileLocator);

        $defaultViewPath = strtolower($this->controller) . Path::DS . $this->action;

        try {
            $this->view
                ->setPathPrefix(Registry::get('mainconfig')->get('dirs', 'views'))
                ->setTranslate($this->loadTranslate())

                ->setJsLoader($this->loadJsLoader())
                ->setCssLoader($this->loadCssLoader())
                ->setImgLoader($this->loadImgLoader())

                ->setMainPath('main')
                ->setViewPath($defaultViewPath)
            ;
        } catch (Exception\Lib $exc) {
            if ($exc->getCode() === 0 || $exc->getCode() > 400) {
                throw $exc;
            }
        }

        return $this->view;
    }

    /**
     * Chargement des librairies Javascript
     *
     * @return Javascript
     */
    public function loadJsLoader()
    {
        if ($this->loaderJs !== false) {
            return $this->loaderJs;
        }

        $this->loaderJs = new Javascript(self::$publicDirs);

        return $this->loaderJs;
    }

    /**
     * Chargement des librairies Css
     *
     * @return Css
     */
    public function loadCssLoader()
    {
        if ($this->loaderCss !== false) {
            return $this->loaderCss;
        }

        $this->loaderCss = new Css(self::$publicDirs);

        return $this->loaderCss;
    }

    /**
     * Chargement des librairies Img
     *
     * @return Img
     */
    public function loadImgLoader()
    {
        if ($this->loaderImg !== false) {
            return $this->loaderImg;
        }

        $this->loaderImg = new Img(self::$publicDirs);

        return $this->loaderImg;
    }

    /**
     * Charge la configuration de l'application utilisée
     *
     * Place le fichier de configuration dans le Registre, à 'appconfig'
     * Paramètre le basehref pour prendre en compte l'application si besoin
     *
     * @return boolean
     *
     * @uses Registry
     */
    public function setAppConfig()
    {
        /* Id api */
        $db = Registry::get('db');
        $query = 'SELECT id '
               . 'FROM gab_api '
               . 'WHERE name = ' . $db->quote($this->application);
        $apiId = $db->query($query)->fetchColumn();

        if (empty($apiId)) {
            /* On essaie de récuperer l'api par le domaine */
            $serverUrl = str_replace('www.', '', $_SERVER['SERVER_NAME']);
            $query = 'SELECT id_api '
                   . 'FROM version '
                   . 'WHERE domaine = ' . $db->quote($serverUrl);
            $apiId = $db->query($query)->fetchColumn();
            if (empty($apiId)) {
                $apiId = 1;
            }
        }
        if (!defined('ID_API')) {
            define('ID_API', $apiId);
        }
        $configPath = 'config/app_' . $this->application . '.ini';
        $configPath = strtolower($configPath);

        $path = new Path($configPath, Path::SILENT);
        if (!$path->get()) {
            return false;
        }

        return true;
    }

    /**
     * Défini la version en cours de l'application
     *
     * Défini la constante ID_VERSION et SUF_VERSION
     * Paramètre le basehref
     *
     * @return boolean
     *
     * @uses Registry
     */
    public function setVersion()
    {
        $db = Registry::get('db');

        /*
         * Permet de forcer une version (utile en dev ou recette)
         */
        if (isset($_GET['version-force'])) {
            $_SESSION['version-force'] = $_GET['version-force'];
        }
        if (isset($_SESSION['version-force'])) {
            $sufVersion = $_SESSION['version-force'];
        } else {
            if (isset($_GET['version'])) {
                $sufVersion = $_GET['version'];
            } else {
                $sufVersion = 'FR';
            }
        }

        /*
         * On vérifie en base si le nom de domaine courant correspond
         *  à une langue
         */
        $serverUrl = str_replace('www.', '', $_SERVER['SERVER_NAME']);

        $query = 'SELECT * '
               . 'FROM `version` '
               . 'WHERE  id_api = ' . intval(ID_API) . ' AND `domaine` = "' . $serverUrl . '"';
        $version = $db->query($query)->fetch(\PDO::FETCH_ASSOC);

        /*
         * Si aucune langue ne correspond
         *  on prend la version FR
         */
        if (!isset($version['id'])) {
            $query = 'SELECT * '
                   . 'FROM `version` '
                   . 'WHERE id_api = ' . intval(ID_API)
                   . ' AND `suf` LIKE ' . $db->quote($sufVersion);
            $version = $db->query($query)->fetch(\PDO::FETCH_ASSOC);

            /*
             * Dans le cas d'un changement d'api
             *  Si la langue en SESSION n'existe pas dans l'api
             *  On récupère la version FR DE la nouvelle api
             */
            if (!isset($version['id'])) {
                $sufVersion = 'FR';
                $query = 'SELECT * '
                   . 'FROM `version` '
                   . 'WHERE id_api = ' . intval(ID_API)
                   . ' AND `suf` LIKE ' . $db->quote($sufVersion);
                $version = $db->query($query)->fetch(\PDO::FETCH_ASSOC);
            }

            $serverUrl = Registry::get('envconfig')->get('base', 'url');
            Registry::set('url', $serverUrl);
            Registry::set('basehref', $serverUrl);

        } else {
            Registry::set('url', 'http://www.' . $serverUrl . '/');
            Registry::set('basehref', 'http://www.' . $serverUrl . '/');
        }


        Registry::set('analytics', $version['analytics']);

        if (!defined('ID_VERSION')) {
            define('ID_VERSION', $version['id']);
            define('SUF_VERSION', $version['suf']);
        }

        return true;
    }

    /**
     * Enregistre un nouveau répertoire de sources
     *
     * @param array $sourceDirectory Configuration du répertoire de sources
     *
     * @return void
     */
    public static function addSourceDirectory($sourceDirectory)
    {
        $name      = $sourceDirectory['name'];
        $namespace = $sourceDirectory['namespace'];
        $dir       = $sourceDirectory['dir'];
        $public    = $sourceDirectory['public'];

        self::$sourceDirectories[] = [
            'name'      => $name,
            'dir'       => $dir,
            'namespace' => $namespace,
        ];
        self::$publicDirs[] = $public;
    }

    /**
     * Définie les répertoires de sources
     *
     * @param array $sourceDirectories Configuration des répertoire de sources
     *
     * @return void
     */
    public static function setSourceDirectories($sourceDirectories)
    {
        self::$sourceDirectories = [];
        foreach ($sourceDirectories as $sourceDirectory) {
            self::addSourceDirectory($sourceDirectory);
        }
    }

    /**
     * Renvois les valeurs par défaut propre à l'application
     *
     * @param string $key Identifiant de la configuration demandé
     *
     * @return string
     */
    public function getDefault($key)
    {
        $conf = Registry::get('appconfig');
        return $conf->get('default', $key);
    }

    /**
     * Renvois les chemins vers les dossiers configurés
     *
     * @param string $key Identifiant du dossier
     *
     * @return string
     */
    public function getDir($key)
    {
        if (isset($this->dirs[$key])) {
            return $this->dirs[$key];
        }
        return '';
    }

    /**
     * Renvois les formats des noms
     *
     * @param string $key Nom du format
     *
     * @return string
     */
    public function getFormat($key)
    {
        if (isset($this->format[$key])) {
            return $this->format[$key];
        }
        return '';
    }

    /**
     * Renvoi l'url complète de la page courante
     *
     * @return string
     */
    public static function getCurrentUrl()
    {
        // On ajoute selon le cas http ou https
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $currentURL = 'https://';
        } else {
            $currentURL = 'http://';
        }

        // On ajoute le nom d'hote de l'url
        $currentURL .= $_SERVER['SERVER_NAME'];

        // Si le port est différent de 80 ou 443, on l'ajoute à l'url
        if ($_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443') {
            $currentURL .= ':' . $_SERVER['SERVER_PORT'];
        }

        // On ajoute enfin la fin de l'url
        $currentURL .= $_SERVER['REQUEST_URI'];
        return $currentURL;
    }
}
