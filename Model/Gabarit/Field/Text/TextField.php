<?php
/**
 * Champ Text
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\Model\Gabarit\Field\Text;

/**
 * Champ Text
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class TextField extends \Solire\Lib\Model\Gabarit\Field\GabaritField
{

    /**
     * Création du champ
     *
     * @return void
     */
    public function start()
    {
        parent::start();

        // Prise en compte de la valeur par défaut paramétrée
        if (
            isset($this->params['VALUE.DEFAULT'])
            && $this->params['VALUE.DEFAULT'] && $this->idGabPage == 0
        ) {
            $this->value = $this->params['VALUE.DEFAULT'];
        }

        if (isset($this->params['LINK']) && $this->params['LINK']) {
            $this->classes .= ' autocomplete-link';
        }
    }
}
