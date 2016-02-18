<?php

namespace Solire\Lib\Filesystem;

use Solire\Lib\Path;

/**
 * Classe abstraite pour la recherche de fichier dans des répertoires.
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
abstract class AbstractFileLocator
{
    /**
     * Liste des répertoires dans lesquels chercher.
     *
     * @var array
     */
    protected $dirs = [];

    /**
     * Constructeur.
     *
     * @param array $dirs Liste des répertoires à utiliser
     */
    public function __construct($dirs = [])
    {
        $this->dirs = $dirs;
    }

    /**
     * Cherche un fichier dans les répertoires définis.
     *
     * @param string $path Chemin du dossier / fichier à chercher dans les répertoires
     *
     * @return string|bool
     */
    public function locate($path)
    {
        $dirs = $this->getDirs();
        foreach ($dirs as $dir) {
            $testPath = new Path($dir . Path::DS . $path, Path::SILENT);
            if ($testPath->get() !== false) {
                return $testPath->get();
            }
        }

        return false;
    }

    /**
     * Retourne la liste complète des répertoires.
     *
     * @return array
     */
    public function getDirs()
    {
        return $this->dirs;
    }
}
