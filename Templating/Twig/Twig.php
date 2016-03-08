<?php

namespace Solire\Lib\Templating\Twig;

use Solire\Lib\Exception\Lib as Exception;
use Solire\Lib\Path;
use Solire\Lib\Templating\Templating;
use Solire\Lib\Templating\Twig\Extensions\Extension\I18n;
use Twig_Environment;
use Twig_Extension_Debug;
use Twig_Loader_Filesystem;

/**
 * Classe de rendu des templates TWIG.
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Twig extends Templating
{
    /**
     * Charge les fichiers templates.
     *
     * @var Twig_Loader_Filesystem
     */
    private $loader;

    /**
     * Dossier de cache pour twig.
     *
     * @var string
     */
    private $cacheDir = false;

    /**
     * Mode debug de twig.
     *
     * @var bool
     */
    private $debug = false;

    /**
     * Template de symfony form à utiliser.
     *
     * @var string
     */
    private $formTemplate = false;

    /**
     * Définit le dossier de cache pour twig.
     *
     * @param string $cacheDir Dossier de cache pour twig
     *
     * @return void
     */
    public function setCacheDir($cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    /**
     * Définit le mode debug de twig.
     *
     * @param bool $debug Mode debug de twig
     *
     * @return void
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    /**
     * Définit le template de symfony form à utiliser.
     *
     * @param string $formTemplate Mode debug de twig
     *
     * @return void
     */
    public function setFormTemplate($formTemplate)
    {
        $this->formTemplate = $formTemplate;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $templatingFilePath Chemin du template
     * @param array  $variables          Variables à inclure dans le scope du template
     *
     * @return void
     *
     * @throws Exception
     */
    public function render($templatingFilePath, $variables = [])
    {
        if ($templatingFilePath === false) {
            throw new Exception('Aucun fichier de vue', 500);
        }

        /* @todo Améliorer ce petit hack pour ne pas spécifier "view/" dans les extends */
        /* @todo Et tester si le rep existe */
        $srcDirs = $this->fileLocator->getDirs();
        $viewSrcDirs = [];
        foreach ($srcDirs as $namespace => $dir) {
            $dir = $dir . Path::DS . 'view';
            if (file_exists($dir)) {
                $viewSrcDirs[$namespace] = $dir;
            }
        }

        $this->loader = new Twig_Loader_Filesystem($viewSrcDirs);
        foreach ($viewSrcDirs as $namespace => &$pathDir) {
            if (file_exists($pathDir)) {
                $this->loader->setPaths($pathDir, str_replace('\\', '', $namespace));
            }
        }

        $twig = new Twig_Environment(
            $this->loader,
            [
                'autoescape' => false,
                'cache' => $this->cacheDir,
                'auto_reload' => true,
                'debug' => $this->debug,
            ]
        );

        if ($this->formTemplate) {
            new FormBridge($twig, array_values((array) $this->formTemplate));
        }

        $twig->getExtension('core')->setDateFormat('d/m/Y');

        $twig->addExtension(new I18n());
        $twig->addExtension(new Twig_Extension_Debug());

        return $twig->render($templatingFilePath, $variables);
    }

    /**
     * Retourne le chargeur de template de twig.
     *
     * @return Twig_Loader_Filesystem
     */
    public function getLoader()
    {
        return $this->loader;
    }

    /**
     * Affiche la vue.
     *
     * @param string $templatingFilePath Chemin d'une vue twig
     * @param array  $variables          Variables à passer à twig
     *
     * @return void
     */
    public function display($templatingFilePath, $variables = [])
    {
        echo $this->render($templatingFilePath, $variables);
    }

    /**
     * N'a aucun effet sur twig.
     *
     * {@inheritdoc}
     *
     * @param string $mainPath Chemin du template de base à utiliser
     *
     * @return mixed
     */
    public function setMainPath($mainPath)
    {
        // Aucun traitement pour Twig
    }
}
