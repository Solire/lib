<?php
namespace Solire\Lib\Loader;

use Solire\Lib\Path;
use Solire\Lib\Exception\Lib as LibException;

/**
 * Gestionnaire des librairies css, js, css et génération d'un code
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 *
 * @todo ajouter filemtime pour forcer le rechargement des librairies (cache).
 */
abstract class Loader
{
    protected $root = '';

    /**
     *
     *
     * @var array
     */
    protected $dirs = [];

    /**
     *
     *
     * @var array
     */
    protected $librairies = [];

    /**
     * Constructeur
     *
     * @param array  $dirs Liste des dossiers à inspecter dans l'ordre de
     * préférence.
     * @param string $root Préfixe des dossiers
     *
     * @throws LibException
     */
    public function __construct(array $dirs, $root = '')
    {
        if (empty($dirs)) {
            throw new LibException(
                'The loader should be instanciate with a non empty array'
            );
        }

        $this->dirs = $dirs;

        if ($root != '') {
            if (substr($root, -1) == Path::DS) {
                $this->root = $root;
            } else {
                $this->root = $root . Path::DS;
            }
        }
    }

    /**
     * Renvois la liste des librairies css
     *
     * @return array
     */
    final public function getLibrairies()
    {
        return $this->librairies;
    }

    /**
     * Ajout de librairie
     *
     * @param string $url     Chemin de la librairie
     * @param array  $options Options de la librairie
     *
     * @return void
     */
    final public function addLibrary($url, array $options = [])
    {
        if (!is_string($url)
            || $url === ''
        ) {
            throw new LibException(
                'L\'url de la librairie ajouté doit être une chaîne non vide'
            );
        }

        $this->librairies[$url] = $options;
    }

    /**
     * Renvois le chemin vers la librairie en fonction des dossiers à
     * inspecter $dirs)
     *
     * @param string $filePath Chemin relatif de la librairie
     *
     * @return string
     */
    protected function getPath($filePath)
    {
        foreach ($this->dirs as $dir) {
            $path = new Path(
                $this->root . $dir . Path::DS . $filePath,
                Path::SILENT
            );
            if ($path->get()) {
                return $dir . Path::DS . $filePath;
            }
        }

        return null;
    }

    /**
     * Template du code pour une librairie donnée
     *
     * @param string $url     Url de la librairie
     * @param string $realUrl Vraie url de la librairie
     * @param array  $options Options de la librairie
     *
     * @return string
     */
    abstract protected function template($url, $realUrl, array $options = []);

    /**
     * Comportement au cas où la librairie n'est pas trouvé
     *
     * @param string $url     Url de la librairie
     * @param string $realUrl Vraie url de la librairie
     * @param array  $options Options de la librairie
     *
     * @return string
     */
    protected function errorNotFound($url, $realUrl, array $options = [])
    {
        return '';
    }


    /**
     * Recherche la librairie et renvoi un code pour une librairie
     *
     * @param string $url     Url de la librairie
     * @param array  $options Options de la librairie
     * @param array  $force   Si la librairie n'est pas trouvé, qu'on veut l'url
     * donné sans traitement on met ce paramètre à vrai sinon la méthode
     * errorNotFound() sera utilisé
     *
     * @return string
     */
    final public function output($url, array $options = [], $force = false)
    {
        $realUrl = $this->getPath($url);

        if ($realUrl === null && !$force) {
            return $this->errorNotFound($url, $realUrl, $options);
        }

        return $this->template($url, $realUrl, $options);
    }

    /**
     * Recherche chaque librairie ajouté et renvoi la concaténation de tous les
     * codes correspondants
     *
     * @param array $force Si une librairie n'est pas trouvé, qu'on veut l'url
     * donné sans traitement on met ce paramètre à vrai sinon la méthode
     * errorNotFound() sera utilisé
     *
     * @return string
     */
    final public function outputAll($force = false)
    {
        $output = '';
        foreach ($this->librairies as $url => $options) {
            $output .= $this->output($url, $options, $force);
        }
        return $output;
    }

    /**
     * Alias de outputAll()
     *
     * @return string
     */
    final public function __toString()
    {
        return $this->outputAll();
    }
}
