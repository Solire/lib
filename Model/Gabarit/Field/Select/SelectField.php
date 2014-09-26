<?php
/**
 * Champ Select
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\Model\Gabarit\Field\Select;

/**
 * Champ Select
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class SelectField extends \Solire\Lib\Model\Gabarit\Field\GabaritField
{
    /**
     * Création du champ
     *
     * @return void
     */
    public function start()
    {
        if ($this->params['VALUES'] != '') {
            $this->values = explode('|+|', $this->params['VALUES']);
        }
    }
}
