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
     * @param \Solire\Lib\Model\GabaritBloc $bloc      Bloc pour lequel on désire contruire le formulaire
     * @param int                           $idGabPage Identifiant de la page contenant le bloc
     * @param int                           $versionId Identifiant de la version
     *
     * @return void
     */
    public function __construct($bloc, $idGabPage, $versionId)
    {
        $this->gabarit    = $bloc->getGabarit();
        $this->values     = $bloc->getValues();
        $this->champs     = $bloc->getGabarit()->getChamps();
        $this->idGabPage  = $idGabPage;
        $this->versionId  = $versionId;
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
        $rc = new \ReflectionClass(get_class($this));
        $fileName   = dirname($rc->getFileName()) . DIRECTORY_SEPARATOR
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
     * Contruit l'élément de formulaire correspondant à un champ
     *
     * @param array  $champ     Tableau d'info sur le champ
     * @param string $value     Valeur du champ
     * @param string $idpage    Identifiant à concatainer à l'attribut 'id' du champ
     * @param int    $idGabPage Nom du dossier dans lequel sont les images.
     * @param type   $gabarit   Gabarit
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

        $label = $champ['label'];
        $classes = 'form-controle ' . 'form-' . $champ['oblig'] . ' '
                 . 'form-' . strtolower($champ['typedonnee'])
        ;
        $id = 'champ' . $champ['id'] . '_' . $idpage . '_' . $this->versionId;

        if ($champ['typedonnee'] == 'DATE') {
            if ($value != '0000-00-00' && $value != '') {
                $value = DateTime::sqlTo($value);
            } else {
                $value = '';
            }
        }

        $type = strtolower($champ['type']);

        $classNameType = 'Model\\Gabarit\\Field\\' . ucfirst($type) . '\\'
                       . ucfirst($type) . 'Field';
        $classNameType = FrontController::searchClass($classNameType);

        if ($classNameType === false) {
            $classNameType  = '\Solire\Lib\Model\Gabarit\Field\\' . ucfirst($type) . '\\'
                            . ucfirst($type) . 'Field';
        }
        $field = new $classNameType(
            $champ,
            $label,
            $value,
            $id,
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
        $first = true;
        foreach ($this->champs as $champ) {
            if (isset($value[$champ['name']])) {
                $value_champ = $value[$champ['name']];
            } else {
                $value_champ = '';
            }

            $id_champ = '';
            if (isset($value['id_version'])) {
                $id_champ = $value['id_version'];
            }

            if (isset($value['id'])) {
                $id_champ .= $value['id'];
            } else {
                $id_champ .= 0;
            }

            $champArray = $this->buildChamp(
                $champ,
                $value_champ,
                $id_champ,
                $this->idGabPage
            );
            $champHTML .= $champArray['html'];

            if ($first) {
                $first = false;
                $this->valueLabel = $champArray['label'];
            }
        }

        $this->champsHTML = $champHTML;
    }
}
