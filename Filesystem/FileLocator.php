<?php

namespace Solire\Lib\Filesystem;

use Solire\Lib\Path;

/**
 * Classe permettant de chercher un fichier dans les applications
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class FileLocator extends AbstractFileLocator
{
    /**
     * Cherche un fichier dans les applications
     *
     * @param string  $path    Chemin Chemin du dossier / fichier à chercher dans
     * les applications
     * @param boolean $current Utiliser le nom de l'application courante
     *
     * @return string|boolean
     */
    public function locate($path, $current = true)
    {
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
                $testPath = new Path($fooPath, Path::SILENT);
                if ($testPath->get() !== false) {
                    return $testPath->get();
                }
            }
        }

        return false;
    }
}
