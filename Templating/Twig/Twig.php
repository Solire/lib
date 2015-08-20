<?php
/**
 * Classe de rendu des templates TWIG
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\Templating\Twig;

use Solire\Lib\Exception\Lib as Exception;
use Solire\Lib\Templating\Templating;
use Solire\Lib\Templating\Twig\Extensions\Extension\I18n;
use Solire\Lib\Path;
use Twig_Autoloader;
use Twig_Loader_Filesystem;
use Twig_Environment;

/**
 * Classe de rendu des templates TWIG
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Twig extends Templating
{
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

        Twig_Autoloader::register();

        /** @todo Améliorer ce petit hack pour ne pas spécifier "view/" dans les extends */
        /** @todo Et tester si le rep existe */
        $srcDirs = $this->fileLocator->getSrcDirs();
        $viewSrcDirs = [];
        foreach ($srcDirs as $namespace => $dir) {
            $dir = $dir . Path::DS . 'view';
            if (file_exists($dir)) {
                $viewSrcDirs[$namespace] = $dir;
            }
        }

        $loader = new Twig_Loader_Filesystem($viewSrcDirs);
        foreach ($viewSrcDirs as $namespace => &$pathDir) {
            if (file_exists($pathDir)) {
                $loader->setPaths($pathDir, str_replace('\\', '', $namespace));
            }
        }

        $twig = new Twig_Environment($loader);

        $twig->addExtension(new I18n());

        echo $twig->render($templatingFilePath, $variables);
    }

    /**
     * N'a aucun effet sur twig
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
