<?php
/**
 * Gestionnaire de vue
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib;

use \Solire\Lib\Exception\Lib as Exception;

/**
 * Gestionnaire de vue
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class View
{
    /**
     * Définit si la vue incluse automatiquement après execution de l'action
     *
     * @var bool
     */
    private $enable = true;

    /**
     * Objet de traduction
     *
     * @var TranslateMysql
     */
    private $translate = false;

    /**
     * Chemin vers la vue pour le contenu
     *
     * @var boolean|Path
     */
    private $contentPath = false;

    /**
     * Chemin vers la vue "main"
     *
     * @var boolean|Path
     */
    private $mainPath = false;

    /**
     * Préfix du chemin pour trouver les fichiers des vues
     *
     * @var string
     */
    private $prefixPath = '';

    /**
     * Template du chemin pour trouver les fichiers des vues
     *
     * @var string
     */
    private $formatPath = '%s';

    /**
     * Loader des librairies javascript
     *
     * @var Loader\Javascript
     */
    public $javascript;

    /**
     * Loader des librairies css
     *
     * @var Loader\Css
     */
    public $css;

    /**
     * Loader des images
     *
     * @var Loader\Img
     */
    public $img;

    /**
     * Chargement d'une nouvelle vue
     */
    public function __construct()
    {
    }

    // TRADUCTION

    /**
     * Chargement de la classe de traduction
     *
     * @param TranslateMysql $translate Classe de traduction
     *
     * @return self
     */
    public function setTranslate($translate)
    {
        $this->translate = $translate;

        return $this;
    }


    /**
     * Défini le loader de librairies javascript
     *
     * @param Loader\Javascript $javascript Loader de librairies javascript
     *
     * @return self
     */
    public function setJsLoader(Loader\Javascript $javascript)
    {
        $this->javascript = $javascript;

        return $this;
    }

    /**
     * Renvoi le loader de librairies javascript
     *
     * @return Loader\Javascript
     */
    public function getJsLoader()
    {
        return $this->javascript;
    }

    /**
     * Défini le loader de librairies css
     *
     * @param Loader\Css $css Loader de librairies css
     *
     * @return self
     */
    public function setCssLoader(Loader\Css $css)
    {
        $this->css = $css;

        return $this;
    }

    /**
     * Renvoi le loader de librairies css
     *
     * @return Loader\Css
     */
    public function getCssLoader()
    {
        return $this->css;
    }

    /**
     * Défini le loader d'image
     *
     * @param Loader\Img $img Loader de librairies css
     *
     * @return self
     */
    public function setImgLoader(Loader\Img $img)
    {
        $this->img = $img;

        return $this;
    }

    /**
     * Renvoi le loader d'image
     *
     * @return Loader\Img
     */
    public function getImgLoader()
    {
        return $this->img;
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
        if ($this->translate !== false) {
            return $this->translate->translate($string, $aide);
        }

        return $string;
    }

    /**
     * Activer ou désactiver la vue
     *
     * @param boolean $enable Vrai pour activer
     *
     * @return void
     */
    public function enable($enable)
    {
        $this->enable = (boolean) $enable;
    }

    /**
     * Test si la vue est active
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enable;
    }

    /**
     * Affiche le contenu
     *
     * @return void
     */
    public function content()
    {
        if ($this->contentPath === false) {
            throw new Exception('Aucun fichier de vue', 500);
        }

        include $this->contentPath;
    }

    /**
     * Affiche la vue
     *
     * @return void
     */
    public function display()
    {
        if ($this->mainPath !== false) {
            include $this->mainPath;
        } else {
            $this->content();
        }
    }

    /**
     * Enregistre le fichier "main"
     *
     * @param string $strPath Chemin vers le fichier de vue
     *
     * @return self
     * @uses Path pour contrôler le chemin
     */
    public function setViewPath($strPath)
    {
        $this->contentPath = $this->searchFile($strPath);

        return $this;
    }

    /**
     * Enregistre le fichier de vue pour le contenu
     *
     * @param string $strPath  Chemin vers le fichier de vue
     * @param bool   $noSearch Désactiver la recherche du fichier
     *
     * @return self
     * @uses Path pour contrôler le chemin
     */
    public function setMainPath($strPath, $noSearch = false)
    {
        if ($noSearch === true) {
            $this->mainPath = $strPath;
            return $this;
        }

        $this->mainPath = $this->searchFile($strPath);

        return $this;
    }

    /**
     * Paramètre le préfix des chemins pour les vues
     *
     * @param string $prefix Préfix des chemins
     *
     * @return \Solire\Lib\View
     */
    public function setPathPrefix($prefix)
    {
        $this->prefixPath = $prefix;

        return $this;
    }

    /**
     * Paramètre le prefix des chemins pour les vues
     *
     * @param string $format Préfix du chemin
     *
     * @return \Solire\Lib\View
     */
    public function setPathFormat($format)
    {
        $this->formatPath = (string) $format;

        return $this;
    }

    /**
     * Annule l'utilisation du fichier "main"
     *
     * @return self
     */
    public function unsetMain()
    {
        $this->mainPath = false;

        return $this;
    }

    /**
     * Localise le fichier de vue demandé
     *
     * @param string $path Nom du fichier de vue
     *
     * @return string
     * @throws Exception si aucun fichier n'est trouvé
     */
    protected function searchFile($path)
    {
        $path = $this->prefixPath . sprintf($this->formatPath, $path);
        $file = FrontController::search($path);
        if ($file === false) {
            $file = FrontController::search($path, false);
        }

        if ($file === false) {
            throw new Exception('La vue ' . (string) $path . ' ne peut être trouvée', 300);
        }

        return $file;
    }

    /**
     * Ajoute un fichier
     *
     * @param string $filePath Nom du fichier avec l'extension
     *
     * @return self
     */
    public function add($filePath)
    {
        include $this->searchFile($filePath);

        return $this;
    }
}
