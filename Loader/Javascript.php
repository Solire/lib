<?php
/**
 * Gestionnaire des fichiers js
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\Loader;

/**
 * Gestionnaire des fichiers js
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Javascript
{
    /**
     * Liste des librairies js à intégrer
     * @var array
     */
    private $libraries = array();

    /**
     * Chargement du gestionnaire de js
     */
    public function __construct()
    {

    }

    /**
     * Renvois la liste des librairies js
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
     * Affiche le code html pour l'intégration des librairies JS
     *
     * @return string
     */
    public function __toString()
    {
        $js = '';
        foreach ($this->libraries as $lib) {
            if (substr($lib['src'], 0, 7) != 'http://'
                && substr($lib['src'], 0, 8) != 'https://'
            ) {
                $path = $this->getPath($lib['src']);

                if (empty($path)) {
                    /**
                     * Le fichier n'existe pas, on ne traite pas le chemin
                     */

                    $path  = $lib['src'];

                } elseif ($lib['cacheControl']) {
                    /**
                     * Si on veut ajouter le timestamp de la date de modif
                     * du fichier pour contrecarer le cache du navigateur
                     * en cas de modification
                     */

                    $fileInfo  = pathinfo($path);

                    $filemtime = filemtime($path);

                    $path = $fileInfo['dirname'] . '/' . $fileInfo['filename']
                          . '.' . $filemtime . '.js';
                }
            } else {
                $path = $lib['src'];
            }

            $js .= '        <script src="' . $path
                 . '" type="text/javascript"></script>' . "\n";
        }

        return $js;
    }


    /**
     * Ajoute une librairie js à la page
     *
     * @param string  $path         Chemin absolu ou relatif vers le fichier
     * @param boolean $cacheControl Veut-on ajouter le timestamp du fichier ou
     * non? (bug avec le script de tinymce)
     *
     * @return void
     */
    public function addLibrary($path, $cacheControl = true)
    {
            $this->libraries[] = array(
                'src'           => $path,
                'cacheControl'  => $cacheControl,
            );
    }
}
