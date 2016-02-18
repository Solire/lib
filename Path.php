<?php
/**
 * Classe de contrôle des chemins de fichiers.
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib;

use Solire\Lib\Exception\Lib as Exception;

/**
 * Classe de contrôle des chemins de fichiers.
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Path
{
    const DS = DIRECTORY_SEPARATOR;
    const PS = PATH_SEPARATOR;

    /**
     * Chemin absolu vers le fichier.
     *
     * @var string
     */
    protected $path = '';

    /**
     * Mode silencieux
     * À mettre dans $option du construct pour annuler les envois d'exception.
     */
    const SILENT = 18;

    /**
     * Test le chemin relatif $filePath.
     *
     * @param string $filePath Chemin relatif à tester
     * @param mixed  $option   Constante à mettre pour changer le comportement (voir SILENT)
     *
     * @throws Exception Fichier introuvable.
     *
     * @uses Path::test()
     * @uses Path::$_slientMode
     */
    public function __construct($filePath, $option = 0)
    {
        $this->path = $this->test($filePath);

        if ($this->path == false) {
            if ($option != self::SILENT) {
                throw new Exception('Fichier introuvable : ' . $filePath);
            }
        }
    }

    /**
     * Donne le chemin absolue vers le fichier.
     *
     * @return string
     * @ignore
     */
    public function __toString()
    {
        return $this->get();
    }

    /**
     * Renvois le chemin du fichier ou du dossier.
     *
     * @return string
     */
    public function get()
    {
        if (!$this->path) {
            return false;
        }

        return $this->path;
    }

    /**
     * Permet d'ajouter des dossiers dans lesquelles chercher les fichiers.
     *
     * @param string $path Dossier à ajouter
     *
     * @return bool True si l'opération c'est bien déroulée.
     * @static
     */
    public static function addPath($path)
    {
        $path = new self($path);

        $usePaths = explode(self::PS, get_include_path());
        foreach ($usePaths as $usePath) {
            if ($usePath == $path->get()) {
                return true;
            }
        }

        set_include_path(
            get_include_path() . self::PS . $path->get()
        );

        return true;
    }

    /**
     * Renvoi le chemin absolu, si le fichier est un lien symbolique et.
     *
     * @param string $path    Le chemin du fichier
     * @param bool   $symLink Si le fichier est un lien si le paramètre
     *                        est à faux renvoi le chemin absolu du lien sinon renvoi le chemin absolu
     *                        de la cible du lien
     *
     * @return string
     *
     * @see \realpath()
     */
    public static function realPath($path, $symLink = false)
    {
        if (is_link($path) && !$symLink) {
            /*
             * Si c'est un lien symbolique et qu'on veut le chemin absolu du
             * lien et non de la cible du lien, on prend le realpath du parent
             * auquel on concatène le dossier final
             */
            $parent = pathinfo($path, PATHINFO_DIRNAME);

            return realpath($parent) . self::DS
                 . pathinfo($path, PATHINFO_BASENAME);
        }

        return realpath($path);
    }

    /**
     * Test le chemin.
     *
     * @param string $filePath Chemin vers le fichier
     *
     * @return mixed le chemin du fichier ou FALSE si il n'existe aucun fichier
     */
    private function test($filePath)
    {
        $usePaths = explode(self::PS, get_include_path());
        foreach ($usePaths as $usePath) {
            if ($usePath != '.') {
                $testFilePath = $usePath . self::DS . $filePath;
            } else {
                $testFilePath = $filePath;
            }
            if (file_exists($testFilePath)) {
                return self::realPath($testFilePath);
            }
        }

        return false;
    }
}
