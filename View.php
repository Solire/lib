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
