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
    protected $currentApplicationDirs = [];

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
        $this->buildCurrentApplicationDirs();

        return $this;
    }

    /**
     * Retourne la liste complète des répertoires
     *
     * @param int $type Permet de retourner seulement les répertoires de sources ou de l'application
     * courante
     *
     * @return array
     */
    public function getDirs($type = self::TYPE_ALL)
    {
        $dirs = [];
        switch ($type) {
            case self::TYPE_ALL:
                $dirs = $this->currentApplicationDirs + $this->sourceDirectoriesDirs;
                break;
            case self::TYPE_SOURCE_DIRECTORY:
                $dirs = $this->sourceDirectoriesDirs;
                break;
            case self::TYPE_APPLICATION:
                $dirs = $this->currentApplicationDirs;
                break;

        }

        return $dirs;
    }

    /**
     * Cherche un fichier
     *
     * @param string $path Chemin du dossier / fichier à chercher
     * @param int    $type Permet de choisir les répertoires de recherche (sources / application)
     *
     * @return string|boolean
     */
    public function locate($path, $type = self::TYPE_APPLICATION)
    {
        $dirs = $this->getDirs($type);
        foreach ($dirs as $dir) {
            $testPath = new Path($dir . Path::DS . $path, Path::SILENT);
            if ($testPath->get() !== false) {
                return $testPath->get();
            }
        }

        return false;
    }

    /**
     * Construit la liste des répertoires pour l'application courante
     *
     * @return void
     */
    protected function buildCurrentApplicationDirs()
    {
        $this->currentApplicationDirs = [];

        $appDirs = [
            Path::DS . $this->currentApplicationName,
            Path::DS . strtolower($this->currentApplicationName),
        ];

        foreach ($this->sourceDirectories as $sourceDirectory) {
            foreach ($appDirs as $appDir) {
                $namespace = $sourceDirectory['namespace'] . '\\' . ucfirst(str_replace('/', '', $appDir));
                if (file_exists($sourceDirectory['dir'] . $appDir)) {
                    $this->currentApplicationDirs[$namespace] = $sourceDirectory['dir'] . $appDir;
                }
            }
        }
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
