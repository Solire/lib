<?php
/**
 * Fieldset
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\Model\Gabarit\FieldSet;

use Solire\Lib\Format\DateTime;
use Solire\Lib\FrontController;
use Solire\Lib\Model\Gabarit;
use Solire\Lib\Model\GabaritBloc;

/**
 * Fieldset
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
abstract class GabaritFieldSet
{
    /**
     * Affichage oui / non du bloc
     *
     * @var boolean
     */
    protected $display = true;

    protected $view = 'default';

    protected $gabarit;
    protected $values;
    protected $valueLabel;
    protected $champsHTML;
    protected $idGabPage;
    protected $champs;
    protected $versionId;

    /**
     * Constructeur
     *
     * @param GabaritBloc $bloc      Bloc pour lequel on désire construire le formulaire
     * @param int         $idGabPage Identifiant de la page contenant le bloc
     * @param int         $versionId Identifiant de la version
     *
     */
    public function __construct($bloc, $idGabPage, $versionId)
    {
        $this->gabarit   = $bloc->getGabarit();
        $this->values    = $bloc->getValues();
        $this->champs    = $bloc->getGabarit()->getChamps();
        $this->idGabPage = $idGabPage;
        $this->versionId = $versionId;
    }

    /**
     * Initialisation
     *
     * @return void
     */
    public function start()
    {
        if (count($this->values) == 0) {
            $this->values[] = [];
        }
    }

    /**
     * Retourne le formulaire pour le champ
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Retourne le formulaire pour le champ
     *
     * @return string
     */
    public function toString()
    {
        $reflectionClass = new \ReflectionClass(get_class($this));
        $fileName        = dirname($reflectionClass->getFileName()) . DIRECTORY_SEPARATOR
            . 'view/' . $this->view . '.phtml';

        return $this->output($fileName);
    }

    /**
     * Renvoi le formulaire du bloc
     *
     * @param string $file Chemin de la vue à inclure
     *
     * @return string Rendu de la vue après traitement
     */
    public function output($file)
    {
        if ($this->display === false) {
            return null;
        }

        ob_start();
        include($file);
        $output = ob_get_clean();

        return $output;
    }

    /**
     * Construit l'élément de formulaire correspondant à un champ
     *
     * @param array   $champ     Tableau d'info sur le champ
     * @param string  $value     Valeur du champ
     * @param string  $idpage    Identifiant à concaténer à l'attribut 'id' du champ
     * @param int     $idGabPage Nom du dossier dans lequel sont les images.
     * @param Gabarit $gabarit   Gabarit
     *
     * @return string
     */
    protected function buildChamp(
        $champ,
        $value,
        $idpage,
        $idGabPage,
        $gabarit = null
    ) {

        $form = '';
        if ($champ['visible'] == 0) {
            return $form;
        }

        $label   = $champ['label'];
        $classes = 'form-controle ' . 'form-' . $champ['oblig'] . ' '
            . 'form-' . strtolower($champ['typedonnee']);
        $fieldId = 'champ' . $champ['id'] . '_' . $idpage . '_' . $this->versionId;

        if ($champ['typedonnee'] == 'DATE') {
            if ($value != '0000-00-00' && $value != '') {
                $value = DateTime::sqlTo($value);
            } else {
                $value = '';
            }
        }

        $type = strtolower($champ['type']);

        $classNameType = $this->getClassNameType($type);

        /** @var Gabarit\Field\GabaritField $field */
        $field = new $classNameType(
            $champ,
            $label,
            $value,
            $fieldId,
            $classes,
            $idGabPage,
            $this->versionId
        );

        /**
         * Cas pour les bloc dyn de champ join avec un seul champs et de type
         * simple
         */
        if ($gabarit != null) {
            $field->start($gabarit);
        } else {
            $field->start();
        }

        $form .= $field->toString();
        if (method_exists($field, 'getValueLabel')) {
            $valueLabel = $field->getValueLabel();
            if ($valueLabel == '') {
                $valueLabel = 'Bloc en cours de création';
            }
        } elseif ($value != '') {
            if (\mb_strlen($value, 'UTF-8') > 50) {
                $valueLabel = \mb_substr(strip_tags($value), 0, 50, 'UTF-8') . '&hellip;';
            } else {
                $valueLabel = $value;
            }
        } else {
            $valueLabel = 'Bloc en cours de création';
        }

        return [
            'html'  => $form,
            'label' => $valueLabel,
        ];
    }

    /**
     * Construits le formulaire des champs du bloc
     *
     * @param array $value Tableau associatif des valeurs des champs
     *
     * @return void
     */
    protected function buildChamps($value)
    {
        $champHTML = '';
        $first     = true;
        foreach ($this->champs as $champ) {
            if (isset($value[$champ['name']])) {
                $fieldValue = $value[$champ['name']];
            } else {
                $fieldValue = '';
            }

            $fieldId = '';
            if (isset($value['id_version'])) {
                $fieldId = $value['id_version'];
            }

            if (isset($value['id'])) {
                $fieldId .= $value['id'];
            } else {
                $fieldId .= 0;
            }

            $champArray = $this->buildChamp(
                $champ,
                $fieldValue,
                $fieldId,
                $this->idGabPage
            );
            $champHTML .= $champArray['html'];

            if ($first) {
                $first            = false;
                $this->valueLabel = $champArray['label'];
            }
        }

        $this->champsHTML = $champHTML;
    }

    /**
     * Retourne le nom d'une classe d'un champ en fonction du type donné
     *
     * @param string $type Type de champ
     *
     * @return bool|string
     */
    protected function getClassNameType($type)
    {
        $classNameType = 'Model\\Gabarit\\Field\\' . ucfirst($type) . '\\'
            . ucfirst($type) . 'Field';
        $classNameType = FrontController::searchClass($classNameType);

        if ($classNameType === false) {
            $classNameType = '\Solire\Lib\Model\Gabarit\Field\\' . ucfirst($type) . '\\'
                . ucfirst($type) . 'Field';

            return $classNameType;
        }

        return $classNameType;
    }
}
