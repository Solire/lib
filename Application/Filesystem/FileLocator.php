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
    const TYPE_ALL              = 1;
    const TYPE_SOURCE_DIRECTORY = 2;
    const TYPE_APPLICATION      = 3;

    /**
     * Liste des répertoires de sources
     *
     * @var array
     */
    protected $sourceDirectoriesDirs = [];

    /**
     * Liste des répertoires de l'application courante
     *
     * @var array
     */
    protected $applicationDirs = [];

    /**
     * Liste des applications
     *
     * @var array
     */
    protected $sourceDirectories = [];

    /**
     * Nom de l'application courante
     *
     * @var array
     */
    protected $currentApplicationName = null;

    /**
     * Constructeur
     *
     * @param array $sourceDirectories Tableau de répertoires de sources
     */
    public function __construct($sourceDirectories = [])
    {
        $this->sourceDirectories = $sourceDirectories;
        $this->buildSourceDirectoriesDirs();
    }

    /**
     * Paramètre la sous application courante
     *
     * @param string $currentApplicationName Nom de l'application courante
     *
     * @return self
     */
    public function setCurrentApplicationName($currentApplicationName)
    {
        $this->currentApplicationName = $currentApplicationName;
        $this->buildApplicationDirs($this->currentApplicationName);

        return $this;
    }

    /**
     * Retourne la liste complète des répertoires
     *
     * @param int    $type            Permet de retourner seulement les répertoires de sources ou de l'application
     * courante
     * @param string $applicationName Nom de l'application
     *
     * @return array
     */
    public function getDirs($type = self::TYPE_ALL, $applicationName = null)
    {
        if ($applicationName === null) {
            $applicationName = $this->currentApplicationName;
        }

        $appDirs = $this->getApplicationDirs($applicationName);

        $dirs = [];
        switch ($type) {
            case self::TYPE_ALL:
                $dirs = $appDirs + $this->sourceDirectoriesDirs;
                break;
            case self::TYPE_SOURCE_DIRECTORY:
                $dirs = $this->sourceDirectoriesDirs;
                break;
            case self::TYPE_APPLICATION:
                $dirs = $appDirs;
                break;

        }

        return $dirs;
    }

    /**
     * Cherche un fichier
     *
     * @param string $path            Chemin du dossier / fichier à chercher
     * @param int    $type            Permet de choisir les répertoires de recherche (sources / application)
     * @param string $applicationName Nom de l'application
     *
     * @return string|boolean
     */
    public function locate($path, $type = self::TYPE_APPLICATION, $applicationName = null)
    {
        $dirs = $this->getDirs($type, $applicationName);
        foreach ($dirs as $dir) {
            $testPath = new Path($dir . Path::DS . $path, Path::SILENT);
            if ($testPath->get() !== false) {
                return $testPath->get();
            }
        }

        return false;
    }

    /**
     * Retourne la liste des répertoires pour une applocation
     *
     * @param string $applicationName Nom de l'application
     *
     * @return array
     */
    protected function getApplicationDirs($applicationName)
    {
        if (!isset($this->applicationDirs[$applicationName])) {
            $this->buildApplicationDirs($applicationName);
        }

        return $this->applicationDirs[$applicationName];
    }

    /**
     * Construit la liste des répertoires pour une applocation
     *
     * @param string $applicationName Nom de l'application
     *
     * @return array
     */
    protected function buildApplicationDirs($applicationName)
    {
        $applicationDirs = [];

        $appDirs = [
            Path::DS . $applicationName,
            Path::DS . strtolower($applicationName),
        ];

        foreach ($this->sourceDirectories as $sourceDirectory) {
            foreach ($appDirs as $appDir) {
                $namespace = $sourceDirectory['namespace'] . '\\' . ucfirst(str_replace('/', '', $appDir));
                if (file_exists($sourceDirectory['dir'] . $appDir)) {
                    $applicationDirs[$namespace] = $sourceDirectory['dir'] . $appDir;
                }
            }
        }

        $this->applicationDirs[$applicationName] = $applicationDirs;
    }

    /**
     * Construit la liste des répertoires pour l'application courante
     *
     * @return void
     */
    protected function buildSourceDirectoriesDirs()
    {
        $this->sourceDirectoriesDirs = [];

        foreach ($this->sourceDirectories as $sourceDirectory) {
            $namespace = $sourceDirectory['namespace'];
            if (file_exists($sourceDirectory['dir'])) {
                $this->sourceDirectoriesDirs[$namespace] = $sourceDirectory['dir'];
            }
        }
    }
}
