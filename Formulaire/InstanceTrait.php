<?php
/**
 * Interface des plugins formulaire
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\Formulaire;

use Solire\Lib\Config;
use \Solire\Lib\FrontController;
use \Solire\Lib\Formulaire;

/**
 * Interface des plugins formulaire
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
trait InstanceTrait
{
    /**
     * Charge un formulaire
     *
     * Le formulaire est juste instancié à partir du fichier présent dans
     * <app>/config/form/
     *
     * @param string $name Nom du fichier de configuration du formulaire
     *
     * @return \Solire\Lib\Formulaire
     */
    protected function chargeForm($name)
    {
        $name = 'config/form/' . $name;
        $path = FrontController::search($name, false);
        $form = new Formulaire($path, true);

        return $form;
    }

    /**
     * Charge un fichier de config formulaire
     *
     * @param string $name Nom du fichier de configuration du formulaire
     *
     * @return \Solire\Lib\Config
     */
    protected function chargeFormConfig($name)
    {
        $name = 'config/form/' . $name;
        $path = FrontController::search($name, false);
        $conf = new Config($path);

        return $conf;
    }
}
