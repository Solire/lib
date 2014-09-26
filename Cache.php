<?php
/**
 * Gestion du cache scripté
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib;

/**
 * Gestion du cache scripté
 * Ce cache se fait en enregistrant le contenu du chache dans un fichier
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Cache
{
    /**
     * Chemin vers le dossier de cache
     *
     * @var string cache directory
     */
    private $dir = null;

    /**
     * Instantie le cache
     *
     * @param array $ini Contenu du fichier de config pour le cache
     */
    public function __construct($ini)
    {
        $this->dir = $ini['dir'];
    }

    /**
     * Mise en cache
     *
     * @param string $key   Nom / code de le l'élément à mettre en cache
     * @param mixed  $value Valeur à mettre en cache
     *
     * @return void
     */
    public function set($key, $value)
    {
        file_put_contents($this->dir . $key, serialize($value));
    }

    /**
     * Récupère le cache
     *
     * @param string $key Nom / code de l'élement en cache
     *
     * @return mixed
     */
    public function get($key)
    {
        $file = $this->dir . $key;

        if (file_exists($file) && date('Ymd') <= date('Ymd', filemtime($file))) {
            return unserialize(file_get_contents($file));
        }

        return false;
    }
}
