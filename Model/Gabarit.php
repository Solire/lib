<?php
/**
 * Gabarit
 *
 * @author  Thomas <thansen@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\Model;

/**
 * Gabarit
 *
 * @author  Thomas <thansen@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Gabarit
{

    private $data = [];
    private $table;
    private $api = [];
    private $champs = [];
    private $joins = [];
    private $gabaritParent = [];
    private $parents = [];

    /**
     * Initialisation d'un gabarit
     *
     * @param type $row Row
     */
    public function __construct($row)
    {
        $this->data = $row;
    }

    /**
     * Enregistrement de l'id parent
     *
     * @param type $id_parent Id_parent
     *
     * @return void
     */
    public function setIdParent($id_parent)
    {
        $this->data['id_parent'] = $id_parent;
    }

    /**
     * Enregistrement de l'id Api
     *
     * @param type $api Api
     *
     * @return void
     */
    public function setApi($api)
    {
        $this->api = $api;
    }

    /**
     * Enregistrement du nom
     *
     * @param type $name Nom
     *
     * @return void
     */
    public function setName($name)
    {
        $this->data['name'] = $name;
    }

    /**
     * Enregistrement du label
     *
     * @param type $label Label
     *
     * @return void
     */
    public function setLabel($label)
    {
        $this->data['label'] = $label;
    }

    /**
     * Enregistrement de la table
     *
     * @param type $table Table
     *
     * @return void
     */
    public function setTable($table)
    {
        $this->table = $table;
    }

    /**
     * Enregistrement des champs
     *
     * @param type $champs Champs
     *
     * @return void
     */
    public function setChamps($champs)
    {
        $this->champs = $champs;
    }

    /**
     * Enregistrement des jointures
     *
     * @param type $joins Joins
     *
     * @return void
     */
    public function setJoins($joins)
    {
        $this->joins = $joins;
    }

    /**
     * Enregistrement du gabarit parent
     *
     * @param type $dbRow Ligne
     *
     * @return void
     */
    public function setGabaritParent($dbRow)
    {
        $this->gabaritParent = $dbRow;
    }

    /**
     * Enregistrement des parents
     *
     * @param type $parents Parents
     *
     * @return void
     */
    public function setParents($parents)
    {
        $this->parents = $parents;
    }

    /**
     * Enregistrement de vue
     *
     * @param type $view Vue
     *
     * @return void
     */
    public function setView($view)
    {
        $this->data['view'] = $view;
    }

    /**
     * Renvoie l'id
     *
     * @return int
     */
    public function getId()
    {
        return $this->data['id'];
    }

    /**
     * Renvoie l'id parent
     *
     * @return int
     */
    public function getIdParent()
    {
        return $this->data['id_parent'];
    }

    /**
     * Renvoie le nom
     *
     * @return type
     */
    public function getName()
    {
        return $this->data['name'];
    }

    /**
     * Renvoie le champ main
     *
     * @return type
     */
    public function getMain()
    {
        return $this->data['main'];
    }

    /**
     * Renvoie le champ creable
     *
     * @return type
     */
    public function getCreable()
    {
        return $this->data['creable'];
    }

    /**
     * Renvoie le champ deletable
     *
     * @return type
     */
    public function getDeletable()
    {
        return $this->data['deletable'];
    }

    /**
     * Renvois une variable de $data
     *
     * @param string $name Nom de la variable à renvoyer
     *
     * @return mixed
     */
    public function getData($name)
    {
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }

        return null;
    }

    /**
     * Renvoie le champ sortable
     *
     * @return type
     */
    public function getSortable()
    {
        return $this->data['sortable'];
    }

    /**
     * Renvoie le champ make_hidden
     *
     * @return type
     */
    public function getMakeHidden()
    {
        return $this->data['make_hidden'];
    }

    /**
     * Renvoie le champ editable
     *
     * @return type
     */
    public function getEditable()
    {
        return $this->data['editable'];
    }

    /**
     * Renvoie le champ editable_middle_office
     *
     * @return type
     */
    public function getEditableMiddleOffice()
    {
        return $this->data['editable_middle_office'];
    }

    /**
     * Renvoie le champ meta
     *
     * @return type
     */
    public function getMeta()
    {
        return $this->data['meta'];
    }

    /**
     * Renvoie le champ extension
     *
     * @return type
     */
    public function getExtension()
    {
        return $this->data['extension'];
    }

    /**
     * Renvoie le champ view
     *
     * @return type
     */
    public function getView()
    {
        return $this->data['view'];
    }

    /**
     * Renvoie le champ 301_editable
     *
     * @return type
     */
    public function get301Editable()
    {
        return $this->data['301_editable'];
    }

    /**
     * Renvoie le champ meta_titre
     *
     * @return type
     */
    public function getMetaTitre()
    {
        return $this->data['meta_titre'];
    }

    /**
     * Renvoie le champ label
     *
     * @return type
     */
    public function getLabel()
    {
        return $this->data['label'];
    }

    /**
     * Renvoie le champ Table
     *
     * @return type
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * renvoie api
     *
     * @return type
     */
    public function getApi()
    {
        return $this->api;
    }

    /**
     * Renvoie les champs
     *
     * @return type
     */
    public function getChamps()
    {
        return $this->champs;
    }

    /**
     * Renvoie le champ demandé
     *
     * @param string $name Nom
     * @param bool   $bloc Bloc
     *
     * @return mixed
     */
    public function getChamp($name, $bloc = false)
    {
        foreach ($this->champs as $champsGroup) {
            if ($bloc) {
                $champsGroup = [$champsGroup];
            }
            foreach ($champsGroup as $champ) {
                if ($champ['name'] == $name) {
                    return $champ;
                }
            }
        }
        return false;
    }

    /**
     * Renvoie les jointures
     *
     * @return type
     */
    public function getJoins()
    {
        return $this->joins;
    }

    /**
     * Renvoie les parents
     *
     * @return type
     */
    public function getParents()
    {
        return $this->parents;
    }

    /**
     * Renvoie le gabarit parent
     *
     * @param type $key Key
     *
     * @return type
     */
    public function getGabaritParent($key = null)
    {
        if ($key == null) {
            return $this->gabaritParent;
        }

        if (isset($this->gabaritParent[$key])) {
            return $this->gabaritParent[$key];
        }

        return null;
    }
}
