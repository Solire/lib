<?php

/**
 * Bloc
 *
 * @author  Thomas <thansen@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\Model;

use Solire\Lib\Format\DateTime;
use Solire\Lib\FrontController;
use Solire\Lib\Model\Gabarit\Field\GabaritField;
use Solire\Lib\Model\Gabarit\FieldSet\Defaut\DefautFieldSet;

/**
 * Bloc
 *
 * @author  Thomas <thansen@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class GabaritBloc
{

    /**
     * Est-ce que l'utilisateur est connecté
     *
     * @var bool
     */
    private $connected = false;

    /**
     *
     * @var gabarit
     */
    protected $gabarit;

    /**
     *
     * @var array
     */
    protected $values = [];

    /**
     * Constructeur
     */
    public function __construct()
    {

    }

    /**
     * Défini si l'utilisateur est connecté (utile en cas de middleoffice)
     *
     * @param bool $connected Etat de la connexion
     *
     * @return void
     */
    public function setConnected($connected)
    {
        $this->connected = $connected;
    }

    /**
     * Enregistre le gabarit
     *
     * @param gabarit $gabarit Gabarit
     *
     * @return void
     */
    public function setGabarit($gabarit)
    {
        $this->gabarit = $gabarit;
    }

    /**
     * Enregistre des valeurs
     *
     * @param array $values Values
     *
     * @return void
     */
    public function setValues($values)
    {
        $this->values = $values;
    }

    /**
     * Enregistre une valeur
     *
     * @param int                       $i     Indice auquel insérer la valeur
     * @param array|boolean|GabaritPage $value Valeur à enregistrer
     * @param string                    $key   Clé de la valeur
     *
     * @return boolean
     */
    public function setValue($i, $value, $key = null)
    {
        if ($i < 0 || $i >= count($this->values)) {
            return false;
        }

        if ($key == null) {
            return $this->values[$i] = $value;
        }

        return $this->values[$i][$key] = $value;
    }

    /**
     * Supprime une donnée
     *
     * @param int $i Position de la donnée à supprimer
     *
     * @return void
     */
    public function deleteValue($i)
    {
        unset($this->values[$i]);
        $this->values = array_values($this->values);
    }

    /**
     * Renvoie le gabarit
     *
     * @return Gabarit
     */
    public function getGabarit()
    {
        return $this->gabarit;
    }

    /**
     * Renvoie les valeurs
     *
     * @return mixed
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * Renvoie les attributs éditables
     *
     * @param string $key Key
     * @param int    $id  Id
     *
     * @return mixed
     */
    public function getEditableAttributes($key, $id)
    {
        if (!$this->connected) {
            return '';
        }

        $field = $this->getGabarit()->getChamp($key, true);
        if (!$field) {
            return '';
        }

        $type = '';
        switch ($field['type']) {
            case 'WYSIWYG':
                $type = 'full';
                break;

            case 'FILE':
                $type = 'image';
                break;

            case 'TEXT':
                $type = 'simple';
                break;

            case 'TEXTAREA':
                $type = 'textarea';
                break;
        }

        if ($type != '') {
            $string = ' data-mercury="' . $type . '" id="champ' . $field['id']
            . '-' . $id . '-' . $this->getGabarit()->getTable() . '" ';
            return $string;
        }

        return '';
    }

    /**
     * Renvoie une valeur
     *
     * @param int    $i   Compteur
     * @param string $key Key
     *
     * @return mixed
     */
    public function getValue($i, $key = null)
    {
        if ($i < 0 || $i >= count($this->values)) {
            return null;
        }

        $row = $this->values[$i];

        if ($key == null) {
            return $row;
        }

        if (!isset($row[$key])) {
            return null;
        }

        return $row[$key];
    }

    /**
     * @return string élément de formulaire en HTML
     */

    /**
     * Retourne l'élément d'un formulaire en HTML correspondant à ce bloc dynamique
     *
     * @param string $idGabPage Id de la page
     * @param int    $versionId Id de version
     *
     * @return string élément de formulaire en HTML
     */
    public function buildForm($idGabPage, $versionId)
    {
        $form = '';

        $champs = $this->gabarit->getChamps();

        $type = 'Defaut';

        /** Récupération du type.phtml bloc si présent * */
        $blocType = $this->gabarit->getData('type');
        if (!empty($blocType)) {
            $type = $blocType;
        }

        if (count($champs) == 1 && $champs[0]['type'] == 'JOIN' && $champs[0]['params']['VIEW'] == 'simple') {
            $type = strtolower('simple');
        }

        $className = 'Model\\Gabarit\\FieldSet\\' . ucfirst($type) . '\\' . ucfirst($type);
        $className = FrontController::searchClass($className);


        if ($className === false) {
            $className = '\\Solire\\Lib\\Model\\Gabarit\\FieldSet\\'
                . ucfirst($type) . '\\' . ucfirst($type) . 'FieldSet';
        }

        /** @var DefautFieldSet $fieldset */
        $fieldset = new $className($this, $idGabPage, $versionId);
        $fieldset->start();
        $form .= $fieldset->toString();

        return $form;
    }

    /**
     * Retourne l'élément d'un formulaire en HTML correspondant à un champ
     *
     * @param array  $champ     Données du champ (ligne en BDD dans la table 'gab_champ')
     * @param string $value     Valeur du champ
     * @param string $idpage    Chaîne à concatainer à l'attribut 'id' de l'élément du formulaire
     * @param int    $idGabPage Nom du dossier dans lequel sont les images
     * @param int    $idVersion Id version
     *
     * @return string élément de formulaire en HTML
     */
    protected function buildChamp($champ, $value, $idpage, $idGabPage, $idVersion = 1)
    {
        $form = '';

        if ($champ['visible'] == 0) {
            return $form;
        }

        $label = $champ['label'];
        $classes = 'form-controle form-' . $champ['oblig'] . ' form-' . strtolower($champ['typedonnee']);
        $id = 'champ' . $champ['id'] . '_' . $idpage;

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
            $classNameType = '\\Solire\\Lib\\Model\\Gabarit\\Field\\' . ucfirst($type) . '\\'
            . ucfirst($type) . 'Field';
        }

        /** @var GabaritField $field */
        $field = new $classNameType($champ, $label, $value, $id, $classes, $idGabPage, $idVersion);
        $field->start();
        $form .= $field;

        return $form;
    }
}
