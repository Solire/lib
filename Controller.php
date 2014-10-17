<?php
/**
 * Base controller
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib;

/**
 * Base controller
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Controller
{

    protected $request = null;

    /**
     * Url absolue du site
     * Elle sera enregistrée sous le nom $Url dans l'objet View
     * @var string
     * @uses View::$Url
     */
    protected $url = null;
    protected $root = null;

    /**
     *
     * @var Config
     */
    protected $mainConfig = null;

    /**
     *
     * @var Config
     */
    protected $appConfig = null;

    /**
     *
     * @var Config
     */
    protected $envConfig = null;

    /**
     *
     * @var View
     */
    public $view = null;

    /**
     *
     * @var MyPDO
     */
    public $db = null;

    /**
     *
     * @var bool
     */
    public $ajax = false;

    /**
     *
     * @var Seo
     */
    public $seo;

    /**
     *
     * @var Loader\Javascript
     */
    public $javascript;

    /**
     *
     * @var Loader\Css
     */
    public $css;

    /**
     *
     * @var TranslateMysql
     */
    protected $translate = null;

    /**
     *
     * @var Log
     */
    protected $log = null;

    /**
     * Informations de rewriting
     *
     * @var stdClass
     */
    protected $rew;

    /**
     * Accepte ou non les rewritings
     *
     * @var boolean
     */
    public $acceptRew = false;

    /**
     * Chargement du controller
     */
    public function __construct()
    {
        if (array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER)
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
        ) {
            $this->ajax = true;
        }

        $this->mainConfig = Registry::get('mainconfig');
        $this->appConfig = Registry::get('appconfig');
        $this->envConfig = Registry::get('envconfig');

        $this->request = $_REQUEST;
        $this->url = Registry::get('basehref');
        $this->root = Registry::get('baseroot');
        $this->db = Registry::get('db');
        $this->log = Registry::get('log');
    }

    /**
     * Fonction éxécutée avant l'execution de la fonction relative à la page en cours
     *
     * @return void
     */
    public function start()
    {
        $this->css = new Loader\Css();
        $this->javascript = new Loader\Javascript();

        $this->seo = new Seo();
        $this->view->mainConfig = Registry::get('mainconfig');
        $this->view->appConfig = Registry::get('appconfig');
        $this->view->envConfig = Registry::get('envconfig');
        $this->view->css = $this->css;
        $this->view->javascript = $this->javascript;
        $this->view->ajax = $this->ajax;

        $this->view->seo = $this->seo;

        if (isset($this->option['mobile.enable'])) {
            $mobile = new Mobile(
                Registry::get('base'),
                $_SERVER['HTTP_USER_AGENT'],
                'mobile',
                'mobile'
            );
            $this->version = $mobile->currentVersion();
            $this->view->version = $this->version;
            Registry::set('base', $mobile->baseHref());
        }
    }

    /**
     * Fonction éxécutée après l'execution de la fonction relative à la page en cours
     *
     * @return void
     */
    public function shutdown()
    {
        $this->view->url = $this->url;
    }

    /**
     * Lance les executions automatiques
     *
     * @param string $type Type d'execution (shutdown pour le moment)
     *
     * @return void
     * @throws Exception\lib Si le type n'est pas cohérent
     * @deprecated since version 3.0
     */
    final protected function loadExec($type)
    {
        if (!in_array($type, array('shutdown'))) {
            throw new Exception\lib('Type d\'execution incorrecte');
        }

        $dirs = FrontController::getAppDirs();
        $config = FrontController::$mainConfig;
        foreach ($dirs as $foo) {
            $dir = $foo['dir'] . DS . strtolower(FrontController::$appName)
                 . DS . $config->get('dirs', $type . 'Exec');
            $path = new Path($dir, Path::SILENT);
            if ($path->get() === false) {
                continue;
            }

            $dir = opendir($path->get());
            while ($file = readdir($dir)) {
                if ($file == '.' || $file == '..') {
                    continue;
                }

                if (is_dir($path->get() . $file)) {
                    continue;
                }

                $funcName = $foo['name'] . '\\' . FrontController::$appName . '\\'
                          . str_replace(DS, '\\', $config->get('dirs', $type . 'Exec'))
                          . pathinfo($file, PATHINFO_FILENAME);
                if (!function_exists($funcName)) {
                    include $path->get() . $file;
                }
                    $funcName($this);
            }
            closedir($dir);
        }
    }

    /**
     * Définit la vue
     *
     * @param View $view Vue à utiliser
     *
     * @return self
     */
    final public function setView($view)
    {
        $this->view = $view;

        return $this;
    }

    /**
     * Renvois la vue
     *
     * @return View
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * Chargement de la classe de traduction
     *
     * @param TranslateMysql $translate Outils de traduction
     *
     * @return self
     */
    final public function setTranslate($translate)
    {
        $this->translate = $translate;

        return $this;
    }

    /**
     * Définit l'objet de traduction
     *
     * @return TranslateMysql
     */
    public function getTranslate()
    {
        return $this->translate;
    }


    /**
     * Redirection vers une autre action d'un controller
     *
     * @param string  $controller Nom du controller
     * @param string  $action     Nom de l'action
     * @param array   $params     Paramètres à faire passer en plus
     * @param boolean $teardown   Supprimer les anciens paramètres oui / non
     *
     * @return void
     *
     */
    public function redirect($controller, $action, $params = null, $teardown = true)
    {
        if (!$params) {
            $params = array();
        }

        if (!$teardown) {
            $params = array_merge($this->request, $params);
        }

        $redirect = $controller . '/' . $action . '.html?'
                  . http_build_query($params);
        header('Location:' . $this->root . $redirect);
        exit();
    }

    /**
     * Redirection vers une url
     *
     * @param string $url      Url vers laquelle renvoyer l'utilisateur
     * @param bool   $relative Url relative ?
     *
     * @return void
     */
    public function simpleRedirect($url, $relative = false)
    {
        if ($relative) {
            $url = Registry::get('basehref') . $url;
        }

        header('Location: ' . $url);

        exit();
    }

    /**
     * Detect les redirection 301 et renvoi l'url si une existe
     *
     * @return boolean|string
     */
    public function check301()
    {
        $appUrl = \Slrfw\FrontController::$appUrl;
        if (!empty($appUrl)) {
            $appUrl .= '/';
        }

        $urlsToTest = array();

        $mask = '`'
              . '^/'
              . \Slrfw\FrontController::$envConfig->get('base', 'root')
              . $appUrl
              . '`';
        $url = preg_replace($mask, '', $_SERVER['REQUEST_URI']);
        $urlParts = explode('/', $url);

        if (substr($url, -1) == '/') {
            unset($urlParts[count($urlParts) - 1]);
            $urlParts[count($urlParts) - 1] .= '/';
        }

        $url = '';
        do {
            $urlPart = array_shift($urlParts);

            $url .= $urlPart;

            $urlFollowing = '';
            if (!empty($urlParts)) {
                $urlFollowing = implode('/', $urlParts);
                $url .= '/';
            }

            $urlsToTest[] = array(
                $url,
                $urlFollowing
            );
        } while (!empty($urlParts));

        // On ajoute aussi l'url entière à tester
        $urlsToTest[] = array(
            FrontController::getCurrentURL(),
            ''
        );

        $urlsToTest = array_reverse($urlsToTest);

        $urlPartRedirect = '';
        $redirection301 = false;
        foreach ($urlsToTest as $key => $row) {
            list($urlToTest, $urlFollowing) = $row;

            $query = 'SELECT new '
                    . 'FROM redirection '
                    . 'WHERE id_version = ' . ID_VERSION . ' '
                    . ' AND id_api = ' . \Slrfw\FrontController::$idApiRew . ' '
                    . ' AND old LIKE ' . $this->_db->quote($urlToTest) . ' '
                    . 'LIMIT 1';

            $redirection301  = $this->_db->query($query)->fetch(\PDO::FETCH_COLUMN);

            if ($redirection301 !== false) {
                $redirection301 .= $urlFollowing;
                break;
            }
        }

        if ($redirection301 !== false) {
            // Si l'url de redirection est une url absolue
            if (substr($redirection301, 0, 7) == 'http://'
                || substr($redirection301, 0, 8) == 'https://'
            ) {
                $redirection301 = $redirection301;
            } else {
                $redirection301 = $this->_url . $appUrl . $redirection301;
            }
        }

        return $redirection301;
    }

    /**
     * Transforme la page en cour en une erreur 301 ou 404
     *
     * @return void
     * @uses ActionController::redirectError() 301 / 404
     */
    final public function pageNotFound()
    {

        $urlRedirect301 = $this->check301();

        if ($urlRedirect301) {
            $this->redirectError(301, $urlRedirect301);
        } else {
            $this->redirectError(404);
        }
    }

    /**
     * Transforme la page en une erreur HTTP
     *
     * @param string $codeError Code erreur HTTP
     * @param string $url       Url vers laquelle rediriger l'utilisateur
     *
     * @return void
     * @uses Solire\Lib\Exception\HttpError marque l'erreur HTTP
     */
    final public function redirectError($codeError = null, $url = null)
    {
        $exc = new \Solire\Lib\Exception\HttpError('Erreur HTTP');
        if (!empty($codeError)) {
            $exc->http($codeError, $url);
        }

        throw $exc;
    }

    /**
     * La page est en ajax
     *
     * Désactive la vue et contrôle le fait que l'appel soit bien de l'ajax
     *
     * @return void
     */
    final protected function onlyAjax()
    {
        $this->view->enable(false);
        if (!$this->ajax) {
            $this->redirectError(405);
        }
    }

    /**
     * Enregistrement des paramètres de rewriting
     *
     * @param array $rew Rewriting contenu dans les "/"
     *
     * @return self
     *
     */
    final public function setRewriting(array $rew)
    {
        $this->rew = $rew;

        return $this;
    }

    /**
     * Renvois les informations de rewriting courante
     *
     * @return array
     */
    final public function getRewriting()
    {
        return $this->rew;
    }

    /**
     * Test si les valeurs du tableau sont dans les paramètres de la page
     *
     * @param array $inputs Liste des valeurs à contrôler
     *
     * @return bool
     * @deprecated
     */
    public function issetAndNotEmpty($inputs)
    {
        foreach ($inputs as $input) {
            if (!isset($this->request[$input]) || empty($this->request[$input])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Alias à l'utilisation de translate
     *
     * @param string $string Chaine à traduire
     * @param string $aide   Texte permettant de situer l'emplacement de la
     * chaine à traduire, exemple : 'Situé sur le bas de page'
     *
     * @return string
     * @uses TranslateMysql
     */
    public function tr($string, $aide = '')
    {
        return $this->translate->translate($string, $aide);
    }
}
