<?php

namespace Solire\Lib\Loader;

use Solire\Lib\Path;
use Solire\Lib\Exception\Lib as LibException;

/**
 * Gestionnaire des librairies css, js, css et génération d'un code.
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 *
 * @todo ajouter filemtime pour forcer le rechargement des librairies (cache).
 */
abstract class Loader
{
    /**
     * Préfixe des chemins, cette partie ne sera pas renvoyé dans la méthode
     * getPath().
     *
     * @var string
     */
    protected $root = '';

    /**
     * Liste des chemins de dossiers à inspecter pour librairies.
     *
     * @var array
     */
    protected $dirs = [];

    /**
     * Liste des librairies.
     *
     * @var array
     */
    protected $librairies = [];

    /**
     * Constructeur.
     *
     * @param array  $dirs Liste des dossiers à inspecter dans l'ordre de
     *                     préférence.
     * @param string $root Préfixe des dossiers
     *
     * @throws LibException
     */
    public function __construct(array $dirs, $root = '')
    {
        if (empty($dirs)) {
            throw new LibException(
                'Le loader ne doit pas être instancié avec une liste de dossier'
                . ' vide'
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
     * Renvois la liste des librairies css.
     *
     * @return array
     */
    final public function getLibrairies()
    {
        return $this->librairies;
    }

    /**
     * Ajout de librairie.
     *
     * @param string $url     Chemin de la librairie
     * @param array  $options Options de la librairie
     *
     * @return void
     *
     * @throws LibException
     */
    public function addLibrary($url, array $options = [])
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
     * inspecter $dirs).
     *
     * @param string $filePath Chemin relatif de la librairie
     *
     * @return string
     */
    public function getPath($filePath)
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
     * Template du code pour une librairie donnée.
     *
     * @param string $url     Url de la librairie
     * @param string $realUrl Vraie url de la librairie
     * @param array  $options Options de la librairie
     *
     * @return string
     */
    abstract protected function template($url, $realUrl, array $options = []);

    /**
     * Recherche la librairie et renvoi un code pour une librairie.
     *
     * @param string $url     Url de la librairie
     * @param array  $options Options de la librairie
     * @param bool   $force   Si la librairie n'est pas trouvé, qu'on veut l'url
     *                        donné sans traitement on met ce paramètre à vrai sinon la méthode
     *                        errorNotFound() sera utilisé
     *
     * @return string
     *
     * @throws LibException si la librairie n'est pas trouvée
     */
    final public function output($url, array $options = [], $force = false)
    {
        $realUrl = null;

        if (strpos($url, 'http:') === 0
            || strpos($url, 'https:') === 0
        ) {
            $force = true;
        } else {
            $realUrl = $this->getPath($url);
        }

        if ($realUrl === null && !$force) {
            throw new LibException(
                'La librairie "' . $url . '" n\'a pas été trouvée dans [' . print_r($this->dirs, true) . ']'
            );
        }

        return $this->template($url, $realUrl, $options);
    }

    /**
     * Recherche chaque librairie ajouté et renvoi la concaténation de tous les
     * codes correspondants.
     *
     * @param bool $force Si une librairie n'est pas trouvé, qu'on veut l'url
     *                    donné sans traitement on met ce paramètre à vrai sinon la méthode
     *                    errorNotFound() sera utilisé
     *
     * @return string
     */
    public function outputAll($force = false)
    {
        $output = '';
        foreach ($this->librairies as $url => $options) {
            $output .= $this->output($url, $options, $force);
        }

        return $output;
    }

    /**
     * Supprime les librairies
     *
     * @return void
     */
    public function resetLibraries()
    {
        $this->libraries = [];
    }
}
