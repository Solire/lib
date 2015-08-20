<?php

namespace Solire\Lib\Application\Filesystem;

use Solire\Lib\Path;
use Solire\Lib\Filesystem\AbstractFileLocator;

/**
 * Classe permettant de chercher un fichier dans les applications
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class FileLocator extends AbstractFileLocator
{
    const TYPE_ALL             = 1;
    const TYPE_APPLICATION     = 2;
    const TYPE_SUB_APPLICATION = 3;

    /**
     * Liste des répertoires des applications (ex: Project, vendor/Solire, ...)
     *
     * @var array
     */
    protected $applicationDirs = [];

    /**
     * Liste des répertoires de la sous application courante (ex: Front, Back, ...)
     *
     * @var array
     */
    protected $currentSubApplicationDirs = [];

    /**
     * Liste des applications
     *
     * @var array
     */
    protected $applications = [];

    /**
     * Nom de l'application courante
     *
     * @var array
     */
    protected $currentSubApplicationName = null;

    /**
     * Constructeur
     *
     * @param array $applications Liste des applications
     */
    public function __construct($applications = [])
    {
        $this->applications = $applications;
        $this->buildApplicationDirs();
    }


    /**
     * Paramètre la sous application courante
     *
     * @param string $currentSubApplicationName Nom de la sous application courante
     *
     * @return self
     */
    public function setCurrentSubApplicationName($currentSubApplicationName)
    {
        $this->currentSubApplicationName = $currentSubApplicationName;
        $this->buildCurrentSubApplicationDirs();

        return $this;
    }

    /**
     * Retourne la liste complète des répertoires de sources
     *
     * @param int $type Permet de retourner seulement les répertoires des applications ou de la sous application
     * courante
     *
     * @return array
     */
    public function getSrcDirs($type = self::TYPE_ALL)
    {
        $srcDirs = [];
        switch ($type) {
            case self::TYPE_ALL:
                $srcDirs = $this->currentSubApplicationDirs + $this->applicationDirs;
                break;
            case self::TYPE_APPLICATION:
                $srcDirs = $this->applicationDirs;
                break;
            case self::TYPE_SUB_APPLICATION:
                $srcDirs = $this->currentSubApplicationDirs;
                break;

        }

        return $srcDirs;
    }

    /**
     * Cherche un fichier dans les applications
     *
     * @param string $path Chemin du dossier / fichier à chercher dans les applications
     * @param int    $type Permet de choisir les répertoires de recherche (applications / sous application)
     *
     * @return string|boolean
     */
    public function locate($path, $type = self::TYPE_SUB_APPLICATION)
    {
        $dirs = $this->getSrcDirs($type);
        foreach ($dirs as $dir) {
            $testPath = new Path($dir . Path::DS . $path, Path::SILENT);
            if ($testPath->get() !== false) {
                return $testPath->get();
            }
        }

        return false;
    }

    /**
     * Construit la liste des répertoires pour la sous application courante
     *
     * @return void
     */
    protected function buildCurrentSubApplicationDirs()
    {
        $this->currentSubApplicationDirs = [];

        $appDirs = [
            Path::DS . $this->currentSubApplicationName,
            Path::DS . strtolower($this->currentSubApplicationName),
        ];

        foreach ($this->applications as $app) {
            foreach ($appDirs as $appDir) {
                $namespace = $app['namespace'] . '\\' . ucfirst(str_replace('/', '', $appDir));
                if (file_exists($app['dir'] . $appDir)) {
                    $this->currentSubApplicationDirs[$namespace] = $app['dir'] . $appDir;
                }
            }
        }
    }

    /**
     * Construit la liste des répertoires pour l'application courante
     *
     * @return void
     */
    protected function buildApplicationDirs()
    {
        $this->applicationDirs = [];

        foreach ($this->applications as $app) {
            $namespace = $app['namespace'];
            if (file_exists($app['dir'])) {
                $this->applicationDirs[$namespace] = $app['dir'];
            }
        }
    }
}
