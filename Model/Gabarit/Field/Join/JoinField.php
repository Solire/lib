<?php

/**
 * Champ Join
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\Model\Gabarit\Field\Join;

/**
 * Champ Join
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class JoinField extends \Solire\Lib\Model\Gabarit\Field\GabaritField
{

    /**
     *
     * @var type
     */
    protected $valueLabel;

    /**
     *
     * @var type
     */
    protected $gabarit = null;

    /**
     * Création du champ
     *
     * @param \Solire\Lib\Model\gabarit $gabarit Gabarit à afficher
     *
     * @return void
     */
    public function start($gabarit = null)
    {
        $this->gabarit = $gabarit;

        switch ($this->params['VIEW']) {
            case 'autocomplete':
                $this->view = 'autocomplete';
                $this->autocomplete();
                break;
            case 'simple':
                $this->view = 'simple';
                $this->simple();
                break;
        }
    }

    /**
     * Création du champ autocomplete
     *
     * @return void
     */
    private function autocomplete()
    {
        //Valeur par défaut
        if ($this->value == '' && $this->params['VALUE.DEFAULT'] != '') {
            $this->value = $this->params['VALUE.DEFAULT'];
        }

        if ($this->value > 0) {
            /**
             * on recupere la valeur label pour lafficher dans le champ
             */
            $idField = $this->params['TABLE.FIELD.ID'];
            $labelField = $this->params['TABLE.FIELD.LABEL'];
            $table = $this->params['TABLE.NAME'];
            $typeGabPage = $this->params['TYPE.GAB.PAGE'];

            $filterVersion = '`' . $table . '`.id_version = ' . $this->versionId;
            if (isset($_REQUEST['no_version']) && $_REQUEST['no_version'] == 1 || !$typeGabPage) {
                $filterVersion = 1;
            }

            $gabPageSelect = '';
            $additionnalFields = '';
            if (substr($labelField, 0, 9) == 'gab_page.') {
                $additionnalFields = ', gab_page.visible';
                $gabPageSelect = ', (gab_page.visible AND gab_page.visible_parent) visible';
                $gabPageJoin = ' INNER JOIN gab_page'
                . ' ON gab_page.suppr = 0'
                . ' AND gab_page.id = `' . $table . '`.' . $idField
                . ' ' . ($filterVersion != 1 ? 'AND gab_page.id_version = ' . $this->versionId : '');
                $labelField = $this->params['TABLE.FIELD.LABEL'];
            } else {
                $gabPageJoin = '';
                $labelField = '`' . $table . '`.`' . $this->params['TABLE.FIELD.LABEL'] . '`';
            }

            $sql = 'SELECT ' . $labelField . ' label' . $additionnalFields
            . $gabPageSelect
            . ' FROM `' . $table . '`'
            . $gabPageJoin
            . ' WHERE ' . $filterVersion
            . ' AND `' . $table . '`.`' . $idField . '` = ' . $this->value;
            $values = $this->db->query($sql)->fetch(\PDO::FETCH_ASSOC);
            $this->valueLabel = $values['label'];

            if (isset($values['visible']) && $values['visible'] == 0) {
                $this->classes .= ' translucide';
            }
        }
    }

    /**
     * Création du champ Simple
     *
     * @return void
     */
    private function simple()
    {
        $values = array();
        foreach ($this->value as $value) {
            if (isset($value[$this->champ['name']])) {
                $values[] = $value[$this->champ['name']];
            }
        }

        // on recupere les valeurs possibles
        $idField = $this->params['TABLE.FIELD.ID'];
        $labelField = $this->params['TABLE.FIELD.LABEL'];
        $table = $this->params['TABLE.NAME'];
        $gabPageJoin = '';

        $filterVersion = '`' . $table . '`.id_version = ' . $this->versionId;
        if (isset($_REQUEST['no_version']) && $_REQUEST['no_version'] == 1) {
            $filterVersion = 1;
        }

        if (substr($labelField, 0, 9) == 'gab_page.') {
            $gabPageJoin = ' INNER JOIN gab_page'
            . ' ON gab_page.visible = 1 AND gab_page.visible_parent = 1 AND gab_page.suppr = 0'
            . ' AND gab_page.id = `' . $table . '`.' . $idField
            . ' ' . ($filterVersion != 1 ? 'AND gab_page.id_version = ' . $this->versionId : '');
            $labelField = $this->params['TABLE.FIELD.LABEL'];
        } else {
            $labelField = '`$table`.`' . $this->params['TABLE.FIELD.LABEL'] . '`';
        }

        $sql = 'SELECT ' . $idField . ' id, ' . $labelField . ' label'
        . ' FROM `' . $table . '`'
        . $gabPageJoin
        . ' WHERE ' . $filterVersion;

        $this->values = $values;
        $this->allValues = $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Renvoie la valeur du champ
     *
     * @return mixed
     */
    public function getValueLabel()
    {
        return $this->valueLabel;
    }
}
