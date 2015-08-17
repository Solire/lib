<?php

namespace Solire\Lib\View;

use Solire\Lib\Loader;
use Solire\Lib\Path;
use Solire\Lib\Templating\TemplatingInterface;
use Solire\Lib\TranslateMysql;
use Solire\Lib\View\Filesystem\FileLocator;
use Solire\Lib\Exception\Lib as Exception;

/**
 * Classe abstraite des vues
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
abstract class AbstractView
{

    /**
     * Moteur de rendu
     *
     * @var TemplatingInterface
     */
    public $templatingRender;

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
     * Définit si la vue incluse automatiquement après execution de l'action
     *
     * @var bool
     */
    protected $enable = true;

    /**
     * Objet de traduction
     *
     * @var TranslateMysql
     */
    protected $translate = false;

    /**
     * Chemin vers la vue pour le contenu
     *
     * @var boolean|Path
     */
    protected $contentPath = false;

    /**
     * Préfixe du chemin pour trouver les fichiers des vues
     *
     * @var string
     */
    protected $prefixPath = '';

    /**
     * Format des données renvoyées (xml/html/json/...)
     *
     * @var string
     */
    protected $responseFormat = 'html';

    /**
     * FileLocator
     *
     * @var FileLocator Résolveur de chemin de fichier
     */
    protected $fileLocator;

    /**
     * Chemin vers la vue "main"
     *
     * @var boolean|Path
     */
    private $mainPath = false;

    /**
     * Chargement d'une nouvelle vue
     *
     * @param FileLocator $fileLocator Résolveur de chemin de fichier
     */
    public function __construct(FileLocator $fileLocator)
    {
        $this->fileLocator = $fileLocator;
    }

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
     * @param string $string Chaîne à traduire
     * @param string $aide   Texte permettant de situer l'emplacement de la
     * chaîne à traduire, exemple : 'Situé sur le bas de page'
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
     * Affiche le contenu d'un fichier de template
     *
     * @param string  $path    Chemin du template à afficher
     * @param boolean $useMain Utilise ou non le template de base
     *
     * @return void
     *
     * @throws Exception
     */
    protected function render($path, $useMain = false)
    {
        // Première extension (ex: '.html' de '.html.twig')
        $responseFormatExtension = '.' . $this->responseFormat;

        // On cherche le fichier à charger
        $resolvedContentPath = $this->fileLocator->locate(
            $path . $responseFormatExtension,
            true
        );

        // Cas où le fichier n'a pas le format en première extension (compatibilité '.phtml')
        if (!$resolvedContentPath) {
            $responseFormatExtension = null;
            // On cherche le fichier à charger sans double extension
            $resolvedContentPath = $this->fileLocator->locate(
                $path . $responseFormatExtension,
                true
            );
        }

        // Aucun fichier trouvé, une exception est lancée
        if ($resolvedContentPath === false) {
            throw new Exception('Aucun fichier de vue', 500);
        }

        // Templating render
        $templatingRenderType = pathinfo($resolvedContentPath, PATHINFO_EXTENSION);
        $templatingRenderClassname = 'Solire\\Lib\\Templating\\'
            . ucfirst($templatingRenderType)
            . '\\'
            . ucfirst($templatingRenderType);

        if (!class_exists($templatingRenderClassname)) {
            $exceptionMessage = 'Moteur de rendu "'
                . ucfirst($templatingRenderType)
                . '" introuvable.';

            throw new Exception($exceptionMessage);
        }

        $variables = get_object_vars($this);

        // Création de notre object moteur de rendu de template
        $this->templatingRender = new $templatingRenderClassname($this->fileLocator);

        switch ($templatingRenderType) {
            case 'twig':
                // Dans le cas de Twig, un loader se charge de gérer le chargement, on lui passe donc le chemin de base
                $templatingPath = $path . $responseFormatExtension . '.' . $templatingRenderType;
                /** @todo Petit hack pour ne pas avoir "view/" dans le chemin à améliorer */
                $templatingPath = preg_replace('#^' . preg_quote($this->prefixPath) . '#', '', $templatingPath);
                break;
            default:
                if ($this->mainPath !== false && $useMain) {
                    $this->templatingRender->setMainPath($this->mainPath);
                }

                $templatingPath = $resolvedContentPath . $responseFormatExtension;

                break;
        }

        // Lancement du rendu
        $this->templatingRender->render(
            $templatingPath,
            $variables
        );
    }

    /**
     * Affiche la vue
     *
     * @return void
     */
    public function display()
    {
        $this->render($this->contentPath, true);
    }

    /**
     * Affiche le contenu d'un fichier de template
     *
     * @param string $path Chemin du fichier de template
     *
     * @return void
     */
    public function add($path)
    {
        $this->render($this->prefixPath . $path);
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
            $this->mainPath = $this->prefixPath . $strPath;
            return $this;
        }

        $this->mainPath = $this->fileLocator->locate($this->prefixPath . $strPath, true);

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
     * Enregistre le chemin vers le fichier de vue
     *
     * @param string $strPath Chemin vers le fichier de vue
     *
     * @return self
     * @uses Path pour contrôler le chemin
     */
    public function setViewPath($strPath)
    {
        $path = $this->prefixPath . $strPath;
        $this->contentPath = $path;

        return $this;
    }

    /**
     * Paramètre le préfixe des chemins pour les vues
     *
     * @param string $prefix Préfixe des chemins
     *
     * @return self
     */
    public function setPathPrefix($prefix)
    {
        $this->prefixPath = $prefix;

        return $this;
    }
}
