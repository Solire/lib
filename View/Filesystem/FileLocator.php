<?php

namespace Solire\Lib\View\Filesystem;

use Solire\Lib\Path;
use Solire\Lib\Application\Filesystem\FileLocator as ApplicationFileLocator;

/**
 * Classe permettant de chercher un fichier de templating dans les applications
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class FileLocator extends ApplicationFileLocator
{
    /**
     * Cherche un fichier de vues dans les applications
     *
     * @param string $path       Chemin du dossier / fichier à chercher
     * @param int    $type       Permet de choisir les répertoires de recherche (sources / application)
     * @param array  $extensions Liste des extensions des templates possibles
     *
     * @return bool|string
     */
    public function locate($path, $type = self::TYPE_APPLICATION, $extensions = ['twig', 'phtml', 'php'])
    {
        $dirs = $this->getDirs($type);
        foreach ($dirs as $dir) {
            foreach ($extensions as $extension) {
                $fooPathWithExt = $dir . Path::DS . $path . '.' . $extension;
                $testPath = new Path($fooPathWithExt, Path::SILENT);
                if ($testPath->get() !== false) {
                    return $testPath->get();
                }
            }
        }

        return false;
    }
}
