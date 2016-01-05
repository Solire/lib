<?php
/**
 * Interface des plugins formulaire
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\Formulaire;

/**
 * Interface des plugins formulaire
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
interface PluginInterface
{
    /**
     * Contrôle des données
     *
     * @param array $data Données du formulaire
     *
     * @return void
     * @throws \Exception Pour marquer une erreur dans le formulaire
     */
    public static function form(array $data);
}
