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
    public function render($templatingFilePath, $variables = [])
    {
        if ($templatingFilePath === false) {
            throw new Exception('Aucun fichier de vue', 500);
        }

        Twig_Autoloader::register();

        $loader = new Twig_Loader_Filesystem($this->fileLocator->getSrcDirs());
        foreach ($this->fileLocator->getSrcDirs() as $namespace => $pathDir) {
            $loader->setPaths($pathDir, str_replace('\\', '', $namespace));
        }

        $twig = new Twig_Environment($loader);
        echo $twig->render($templatingFilePath, $variables);
    }

    public function setMainPath($mainPath)
    {
        // Aucun traitement pour Twig
    }
}
