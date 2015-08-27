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
     * @param array $row Row
     */
    public function __construct($row)
    {
        $this->data = $row;
    }

    /**
     * Enregistrement de l'id parent
     *
     * @param int $id_parent Id_parent
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
     * @param int $api Api
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
     * @param string $name Nom
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
     * @param string $label Label
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
     * @param string $table Table
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
     * @param array $champs Champs
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
     * @param array $joins Joins
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
     * @param array $dbRow Ligne
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
     * @param array $parents Parents
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
     * @param bool $view Vue
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
     * @return string
     */
    public function getName()
    {
        return $this->data['name'];
    }

    /**
     * Renvoie le champ main
     *
     * @return bool
     */
    public function getMain()
    {
        return $this->data['main'];
    }

    /**
     * Renvoie le champ creable
     *
     * @return bool
     */
    public function getCreable()
    {
        return $this->data['creable'];
    }

    /**
     * Renvoie le champ deletable
     *
     * @return bool
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
     * @return bool
     */
    public function getSortable()
    {
        return $this->data['sortable'];
    }

    /**
     * Renvoie le champ make_hidden
     *
     * @return bool
     */
    public function getMakeHidden()
    {
        return $this->data['make_hidden'];
    }

    /**
     * Renvoie le champ editable
     *
     * @return bool
     */
    public function getEditable()
    {
        return $this->data['editable'];
    }

    /**
     * Renvoie le champ editable_middle_office
     *
     * @return bool
     */
    public function getEditableMiddleOffice()
    {
        return $this->data['editable_middle_office'];
    }

    /**
     * Renvoie le champ meta
     *
     * @return bool
     */
    public function getMeta()
    {
        return $this->data['meta'];
    }

    /**
     * Renvoie le champ extension
     *
     * @return string
     */
    public function getExtension()
    {
        return $this->data['extension'];
    }

    /**
     * Renvoie le champ view
     *
     * @return bool
     */
    public function getView()
    {
        return $this->data['view'];
    }

    /**
     * Renvoie le champ 301_editable
     *
     * @return bool
     */
    public function get301Editable()
    {
        return $this->data['301_editable'];
    }

    /**
     * Renvoie le champ meta_titre
     *
     * @return bool
     */
    public function getMetaTitre()
    {
        return $this->data['meta_titre'];
    }

    /**
     * Renvoie le champ label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->data['label'];
    }

    /**
     * Renvoie le champ Table
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * renvoie api
     *
     * @return int
     */
    public function getApi()
    {
        return $this->api;
    }

    /**
     * Renvoie les champs
     *
     * @return array
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
     * @return array
     */
    public function getJoins()
    {
        return $this->joins;
    }

    /**
     * Renvoie les parents
     *
     * @return array
     */
    public function getParents()
    {
        return $this->parents;
    }

    /**
     * Renvoie le gabarit parent
     *
     * @param string $key Key
     *
     * @return mixed
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
