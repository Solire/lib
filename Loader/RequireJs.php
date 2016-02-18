<?php

namespace Solire\Lib\Loader;

use Solire\Lib\Exception\Lib as LibException;

/**
 * Gestionnaire des scripts js pour requireJS.
 *
 * @author  smonnot <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class RequireJs extends Loader
{
    /**
     * Dossier contenant les modules.
     *
     * @var string
     */
    private $moduleDir;

    /**
     * Ajout de librairie.
     *
     * @param string $url     Chemin de la librairie
     * @param array  $options Options de la librairie
     *
     * @return void
     */
    public function addLibrary($url, array $options = [])
    {
        if (!isset($options['name'])
            || !is_string($options['name'])
            || empty($options['name'])
        ) {
            throw new LibException(
                'L\'option "name" est obligatoire.'
            );
        }

        parent::addLibrary($url, $options);
    }

    /**
     * Définir le dossier contenant les modules.
     *
     * @param string $dir Dossier contenant les modules
     *
     * @return void
     */
    public function setModuleDir($dir)
    {
        $this->moduleDir = $dir;
    }

    /**
     * Ajout d'un module.
     *
     * @param string $path Chemin du module
     *
     * @return void
     */
    public function addModule($path)
    {
        $this->addLibrary($this->moduleDir . '/' . $path . '.js',
            ['name' => $path]
        );
    }

    /**
     * Ajout de plusieurs modules.
     *
     * @param array $path Chemins des modules
     *
     * @return void
     */
    public function addModules($paths)
    {
        foreach ($paths as $path) {
            $this->addModule($path);
        }
    }

    /**
     * Template du code html pour un script js.
     *
     * @param string $url     Url de la librairie
     * @param string $realUrl Vraie url de la librairie
     * @param array  $options Options de la librairie, ici attribut html de la
     *                        baslie img
     *
     * @return string
     */
    protected function template($url, $realUrl, array $options = [])
    {
        $lib = [];
        if ($realUrl === null) {
            $lib['src'] = $url;
        } else {
            $lib['src'] = $realUrl;
        }

        $lib['name'] = $options['name'];

        return $lib;
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
        $requireJsLibs = [];
        $requireJsDeps = [];
        foreach ($this->librairies as $url => $options) {
            $requireJsLib = $this->output($url, $options, $force);
            $requireJsLibs[$requireJsLib['name']] = preg_replace(
                '#.js$#',
                '',
                $requireJsLib['src']
            );

            if (isset($options['deps'])) {
                $requireJsDeps[$requireJsLib['name']] = [
                    'deps' => $options['deps'],
                ];
            }
        }

        $output = '<script type="text/javascript">'
                . 'var requireJsConfig = '
                . '{'
                . ' paths : ' . json_encode($requireJsLibs, JSON_PRETTY_PRINT) . ','
                . ' shim : ' . json_encode($requireJsDeps, JSON_PRETTY_PRINT) . ''
                . '}'
                . '</script>';

        return $output;
    }
}
