<?php
/**
 * Contrôle de variables.
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license MIT http://mit-license.org/
 */

namespace Solire\Lib\Form\Process;

use Solire\Form\ValidateInterface;
use ZxcvbnPhp\Zxcvbn;

/**
 * Contrôle de variables.
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license MIT http://mit-license.org/
 */
class VarPassword implements ValidateInterface
{
    /**
     * Test si le parametre n'est pas vide.
     *
     * @param mixed $data  Valeur à tester
     * @param mixed $param
     *
     * @return bool
     */
    public static function validate($data, $param = null)
    {
        $zxcvbn = new Zxcvbn();
        $strength = $zxcvbn->passwordStrength($data);

        if ($strength['score'] < 2) {
            return false;
        }

        return true;
    }
}
