<?php

namespace Solire\Lib\View\Filesystem;

use Solire\Lib\Path;
use Solire\Lib\Filesystem\AbstractFileLocator;

/**
 * Classe permettant de chercher un fichier de templating dans les applications
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class FileLocator extends AbstractFileLocator
{
    const RELATIVE_PATH = 1;
    const ABSOLUTE_PATH = 2;
    const EXTENSION_PATH = 3;

    /**
     * Cherche un fichier de vues dans les applications
     *
     * @param string  $path           Chemin du dossier / fichier à chercher dans
     * les applications
     * @param boolean $current        Utiliser le nom de l'application courante
     * @param int     $returnPathType Format du chemin retourné
     * @param array   $extensions     Liste des extensions des templates possibles
     *
     * @return bool|string
     */
    public function locate(
        $path,
        $current = true,
        $returnPathType = self::ABSOLUTE_PATH,
        $extensions = ['twig', 'phtml', 'php']
    ) {
        $appLibDir = $this->appLibDir;
        /*
         * @todo Définir les répertoires dans un fichier de config (voir SoEolia\Framework)
         */
        if ($current === true) {
            $appDirs = [
                Path::DS . $this->currentAppName,
                Path::DS . strtolower($this->currentAppName),
            ];
        } else {
            $appDirs = [
                '',
            ];
        }

        foreach ($this->appDirs as $app) {
            $fooPaths = \array_map(function ($appDir) use ($path, $app, $appLibDir) {
                $dir = $app['dir'] . $appDir;

                /*
                 * Permet de faire correspondre des répertoires d'application
                 */
                if (!empty($appLibDir)
                    && isset($appLibDir[$dir])
                ) {
                    $dir = $appLibDir[$dir];
                }

                return $dir . Path::DS . $path;
            }, $appDirs);

            foreach ($fooPaths as $fooPath) {
                foreach ($extensions as $extension) {
                    $fooPathWithExt = $fooPath . '.' . $extension;

                    $testPath = new Path($fooPathWithExt, Path::SILENT);
                    if ($testPath->get() !== false) {
                        switch ($returnPathType) {
                            case 1:
                                return $testPath->get();
                                break;
                            case 2:
                                return $fooPathWithExt;
                                break;
                            case 3:
                                return '.' . $extension;
                                break;
                        }

                    }
                }
            }
        }

        return false;
    }
}
