<?php
/**
 * Interface pour les hooks
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib;

/**
 * Interface des classes de hook
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
interface HookInterface
{
    /**
    * Fonction exécutée lors du chargement du hook
    *
    * @param \Solire\Lib\Hook $env Objet contenant les variables d'environnement
    *
    * @return void
    */
    public function run($env);
}
