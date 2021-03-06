<?php
/**
 * Gestionnaire des hooks.
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib;

use Solire\Lib\Exception\Lib as LibException;

/**
 * Gestionnaire des hooks.
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 *
 * @see     http://solire-02/wiki/index.php/Hook Documentation
 */
class Hook
{
    /**
     * Données d'environnement.
     *
     * @var array
     */
    private $data = [];

    /**
     * Répertoires dans lesquels se trouve les hooks.
     *
     * @var array
     */
    private $dirs = [];

    /**
     * Sous dossier dans lequel est rangé les hooks.
     *
     * @var string
     */
    private $subDir = '';

    /**
     * Nom du hook.
     *
     * @var string
     */
    protected $codeName;

    /**
     * Chargement du gestionnaire de hook.
     */
    public function __construct()
    {
        $this->dirs = array_reverse(FrontController::getSourceDirectories(), true);
    }

    /**
     * Chargement de la liste des répertoires dans lesqueslles se trouve les hooks.
     *
     * Utilisé principalement dans le cadre des tests, les répertoires des App
     * sont chargés par défaut lors de la construction de l'objet.
     *
     * @param array $dirs Liste des répertoires avec le plus bas niveau en premier
     *
     * @return void
     *
     * @link http://solire-02/wiki/index.php/Hook#Organisation
     */
    public function setDirs(array $dirs)
    {
        $this->dirs = $dirs;
    }

    /**
     * Enregistre le nom du sous dossier.
     *
     * @param string $subDir Chemin du sous dossier
     *
     * @return void
     */
    public function setSubdirName($subDir)
    {
        $this->subDir = $subDir;
    }

    /**
     * Execution d'un hook.
     *
     * @param string $codeName Identifiant du hook
     *
     * @return void
     *
     * @uses Path Contrôle du chemin du fichier
     *
     * @throws Exception\lib En cas de problème de configuration
     */
    public function exec($codeName)
    {
        if (empty($this->dirs)) {
            throw new Exception\lib('Problème de configuration appDirs');
        }

        $this->codeName = $codeName;
        unset($codeName);

        if (!empty($this->subDir)) {
            $baseDir = $this->subDir . Path::DS;
        } else {
            $baseDir = '';
        }

        /* Chargement des hooks dispo **/
        $baseDir .= $this->codeName;
        $hooks = [];
        foreach ($this->dirs as $dirInfo) {
            $dir = $dirInfo['dir'] . Path::DS . 'Hook' . Path::DS . $baseDir;
            $path = new Path($dir, Path::SILENT);
            if ($path->get() === false) {
                continue;
            }
            $dir = opendir($path->get());
            while ($file = readdir($dir)) {
                if ($file == '.' || $file == '..'
                    || is_dir($path->get() . Path::DS . $file)
                ) {
                    continue;
                }

                $funcName = $dirInfo['name'] . '\\Hook\\';
                if (!empty($this->subDir)) {
                    $funcName .= ucfirst($this->subDir) . '\\';
                }
                $funcName .= ucfirst($this->codeName)
                          . '\\' . pathinfo($file, PATHINFO_FILENAME);

                $foo = [];
                $foo['className'] = $funcName;
                $foo['path'] = $path->get() . Path::DS . $file;
                $hooks[$file] = $foo;
                unset($foo, $funcName, $file);
            }
            closedir($dir);
            unset($dir, $path);
        }

        // Trie les hooks par nom de fichier
        ksort($hooks);

        /* Lancement des hooks **/
        foreach ($hooks as $hook) {
            if (!class_exists($hook['className'])) {
                include $hook['path'];
            }

            $interfaces = class_implements($hook['className']);

            if (empty($interfaces)
                || !in_array('Solire\Lib\HookInterface', $interfaces)
            ) {
                throw new LibException('Hook au mauvais format');
            }

            /** @var HookInterface $foo */
            $foo = new $hook['className']();
            $foo->run($this);
        }
    }

    /**
     * Enregistrement des variables d'environnement.
     *
     * @param string $name  Nom de la variable
     * @param mixed  $value Contenu de la variable
     *
     * @return void
     */
    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * Renvois la valeur de la variable de l'environnement.
     *
     * @param string $name Nom de la variable
     *
     * @return mixed
     * @ignore
     */
    public function __get($name)
    {
        return $this->data[$name];
    }

    /**
     * Test l'existence d'une variable de l'environnement.
     *
     * @param string $name Nom de la variable
     *
     * @return bool
     * @ignore
     */
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }
}
