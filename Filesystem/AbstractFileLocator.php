<?php

namespace Solire\Lib\Filesystem;

use Solire\Lib\Path;

/**
 * Classe abstraite pour la recherche de fichier dans les applications
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class AbstractFileLocator
{

    /**
     * Liste des répertoires app à utiliser
     *
     * @var array
     */
    protected $appDirs = [];

    /**
     * Liste des correspondances des répertoires d'application
     * Exemple: array('vel/catalogue' => 'vel/front')
     *
     * @var array
     */
    protected $appLibDir = [];

    /**
     * Nom de l'application courante
     *
     * @var array
     */
    protected $currentAppName = null;

    /**
     * Constructeur
     *
     * @param array $appDirs   Liste des répertoires app à utiliser
     * @param array $appLibDir Liste des correspondance des répertoires d'application
     */
    public function __construct(
        $appDirs = array(),
        $appLibDir = array()
    ) {
        $this->appDirs   = $appDirs;
        $this->appLibDir = $appLibDir;
    }

    /**
     * Paramètre l'application courante
     *
     * @param string $currentAppName Nom de l'application courante
     *
     * @return self
     */
    public function setCurrentAppName($currentAppName)
    {
        $this->currentAppName = $currentAppName;

        return $this;
    }

    /**
     * Retourne la liste complète des répertoires de sources
     *
     * @return array
     */
    public function getSrcDirs()
    {
        $appLibDir = $this->appLibDir;

        $appDirs = [
            Path::DS . $this->currentAppName,
            Path::DS . strtolower($this->currentAppName),
            '',
        ];

        $srcDirs = [];

        foreach ($appDirs as $appKey => $appDir) {
            $fooPaths = \array_map(function ($app) use ($appDir, $appLibDir) {
                $dir = $app['dir'] . $appDir;

                /*
                 * Permet de faire correspondre des répertoires d'application
                 */
                if (!empty($appLibDir)
                    && isset($appLibDir[$dir])
                ) {
                    $dir = $appLibDir[$dir];
                }

                return array($app['namespace'] . '\\' . ucfirst(str_replace('/', '', $appDir)), $dir);
            }, $this->appDirs);

            foreach ($fooPaths as $fooPath) {
                $testPath = new Path($fooPath[1], Path::SILENT);
                if ($testPath->get() !== false) {
                    $srcDirs[$fooPath[0]] = $testPath->get();
                }
            }
        }

        return $srcDirs;
    }
}
