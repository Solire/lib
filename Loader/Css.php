<?php
/**
 * Gestionnaire des fichiers css
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\Loader;

/**
 * Gestionnaire des fichiers css
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Css
{
    /**
     * Liste des librairies css à intégrer
     * @var array
     */
    private $libraries = array();

    /**
     * Chargement du gestionnaire de css
     */
    public function __construct()
    {

    }

    /**
     * Renvois la liste des librairies css
     *
     * @return array
     */
    public function getLibraries()
    {
        return $this->libraries;
    }

    /**
     * Renvois les clé de la liste des librairies
     *
     * @return array
     * @deprecated ???
     */
    public function loadedLibraries()
    {
        return array_keys($this->libraries);
    }

    /**
     * Renvois le chemin absolu vers la librairie en fonction des AppDirs
     *
     * @param string $filePath Chemin relatif de la librairie
     *
     * @return string
     */
    protected function getPath($filePath)
    {
        $dirs = \Solire\Lib\FrontController::getAppDirs();

        foreach ($dirs as $dir) {
            $path = new \Solire\Lib\Path($dir['dir'] . DS . $filePath, \Solire\Lib\Path::SILENT);
            if ($path->get()) {
                return $dir['dir'] . DS . $filePath;
            }
        }

        return null;
    }

    /**
     * Affiche le code html pour l'intégration des librairies css
     *
     * @return string
     */
    public function __toString()
    {
        $css = '';
        foreach ($this->libraries as $lib) {
            if (substr($lib['src'], 0, 7) != 'http://'
                && substr($lib['src'], 0, 8) != 'https://'
            ) {
                $path = $this->getPath($lib['src']);
                if (empty($path)) {
                    $path  = $lib['src'];
                } else {
                    $fileInfo  = pathinfo($path);

                    $filemtime = filemtime($path);

                    $path = $fileInfo['dirname'] . '/' . $fileInfo['filename']
                          . '.' . $filemtime . '.css';
                }
            } else {
                $path = $lib['src'];
            }

            $css   .= '        <link rel="stylesheet" href="' . $path
                    . '" type="text/css" media="' . $lib['media']
                    . '" />' . "\n";
        }

        return $css;

    }

    /**
     * Ajoute une librarie css
     *
     * @param string $path  Chemin absolu ou relatif du fichier
     * @param string $media Media de la librarie
     *
     * @return void
     */
    public function addLibrary($path, $media = 'screen')
    {
        $this->libraries[] = array(
            'src' => $path,
            'media' => $media,
        );
    }
}
