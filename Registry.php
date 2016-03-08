<?php
/**
 * Registre.
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib;

/**
 * Registre.
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Registry
{
    /**
     * Contenu du registre.
     *
     * @var array
     */
    private static $maps;

    /**
     * Instancie le registre (jamais utilisé).
     *
     * @ignore
     */
    private function __construct()
    {
    }

    /**
     * Enregistre une variable dans le registre.
     *
     * @param string $key   Nom/Code de l'élement à stocker
     * @param mixed  $value Valeur de l'élément à stocker
     *
     * @return void
     */
    public static function set($key, $value)
    {
        self::$maps[$key] = $value;
    }

    /**
     * Récupère une valeur du registre.
     *
     * @param string $key Nom/Code de l'élement stocké
     *
     * @return mixed
     */
    public static function get($key)
    {
        if (isset(self::$maps[$key])) {
            return self::$maps[$key];
        }

        return null;
    }
}
