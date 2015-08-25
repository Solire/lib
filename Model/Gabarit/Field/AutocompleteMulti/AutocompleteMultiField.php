<?php
/**
 * Champ AutocompleteMulti
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\Model\Gabarit\Field\Autocomplete_multi;

use Solire\Lib\Model\Gabarit\Field\GabaritField;

/**
 * Champ AutocompleteMulti
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class AutocompleteMultiField extends GabaritField
{

    protected $values;

    /**
     * Création du champ
     *
     * @return void
     */
    public function start()
    {
        if ($this->value != '') {
            /** on recupere les valeurs labels pour les afficher dans le champ */
            $idField = $this->params['TABLE.FIELD.ID'];
            $labelField = $this->params['TABLE.FIELD.LABEL'];
            $table = $this->params['TABLE.NAME'];

            $sql = 'SELECT `' . $idField . '`, `' . $idField . '` id, ' . $labelField . ' label'
                 . 'FROM `' . $table . '`'
                 . 'WHERE ' . $idField . ' IN (' . $this->value . ')'
                 . 'ORDER BY FIND_IN_SET(id, "' . $this->value . '")'
            ;

            $this->valuesUnique = $this->db->query($sql)->fetchAll(\PDO::FETCH_UNIQUE | \PDO::FETCH_ASSOC);
            $valuesArray = explode(',', $this->value);
            $this->values = [];
            foreach ($valuesArray as $v) {
                $this->values[] = $this->valuesUnique[$v];
            }
            $this->values = htmlentities(json_encode($this->values));

        }
    }
}
