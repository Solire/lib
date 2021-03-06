<?php
/**
 * Gabarit Page
 *
 * @author  Thomas <thansen@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\Model;

use Solire\Lib\FrontController;
use Solire\Lib\Registry;

/**
 * Gabarit Page
 *
 * @author  Thomas <thansen@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class GabaritPage extends GabaritBloc
{
    /**
     * Tableau des données utilisées dans la génération HTML du formulaire
     *
     * @todo Passer par un objet view pour pouvoir générer via twig (et bcp plus propre)
     *
     * @var array
     */
    private $view;

    /**
     * Est-ce que l'utilisateur est connecté
     *
     * @var bool
     */
    private $connected = false;

    /**
     * Tableau des données meta de la page
     *
     * @var array
     */
    protected $meta = [];

    /**
     * Tableau des données de la version de la page
     *
     * @var array
     */
    protected $version = [];

    /**
     * Tableau des blocs dynamiques de la page
     *
     * @var GabaritBloc[]
     */
    protected $blocs = [];

    /**
     * Tableau des pages parentes
     *
     * @var array
     */
    protected $parents = [];

    /**
     * Tableau des pages enfants
     *
     * @var array
     */
    protected $children = [];

    /**
     * Première page enfant
     *
     * @var gabaritPage
     */
    protected $firstChild = null;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->values = [];
    }

    /**
     * Définit si l'utilisateur est connecté (utile en cas de middleoffice)
     *
     * @param bool $connected Etat de connexion utilisateur
     *
     * @return void
     */
    public function setConnected($connected)
    {
        $this->connected = $connected;
        foreach ($this->blocs as $bloc) {
            $bloc->setConnected($connected);
        }
    }

    /**
     * Setter des métas
     *
     * @param array $meta Meta
     *
     * @return void
     */
    public function setMeta($meta)
    {
        $this->meta = $meta;
    }

    /**
     * Setter d'un attribut des meta
     *
     * @param string     $key   Clé de l'attribut
     * @param string|int $value Valeur
     *
     * @return void
     */
    public function setMetaValue($key, $value)
    {
        $this->meta[$key] = $value;
    }

    /**
     * Setter de la version
     *
     * @param array $data Data
     *
     * @return void
     */
    public function setVersion($data)
    {
        $this->version = $data;
    }

    /**
     * Setter des valeurs
     *
     * @param array $values Valeurs
     *
     * @return void
     */
    public function setValues($values)
    {
        $this->values = $values;
    }

    /**
     * Setter d'une valeur
     *
     * @param string                    $key   Key
     * @param array|boolean|GabaritPage $value Valeur
     *
     * @return void
     */
    public function setValue($key, $value)
    {
        $this->values[$key] = $value;
    }

    /**
     * Setter des blocs de la page
     *
     * @param GabaritBloc[] $blocs Tableau de page
     *
     * @return void
     */
    public function setBlocs($blocs)
    {
        $this->blocs = $blocs;
        foreach ($this->blocs as $bloc) {
            $bloc->setConnected($this->connected);
        }
    }

    /**
     * Setter des pages parentes
     *
     * @param gabaritPage[] $parents Parents
     *
     * @return void
     */
    public function setParents($parents)
    {
        $this->parents = $parents;
    }

    /**
     * Setter des pages enfants
     *
     * @param gabaritPage[] $children Enfants
     *
     * @return void
     */
    public function setChildren($children)
    {
        if (count($children) > 0) {
            $this->firstChild = $children[0];
        }
        $this->children = $children;
    }

    /**
     * Getter des pages enfants
     *
     * @return gabaritPage[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Setter de la premiere page enfant
     *
     * @param gabaritPage $firstChild Premier enfant
     *
     * @return void
     */
    public function setFirstChild($firstChild)
    {
        $this->firstChild = $firstChild;
    }

    /**
     * Renvoie la meta
     *
     * @param string $key Key
     *
     * @return mixed
     */
    public function getMeta($key = null)
    {
        if ($key != null) {
            if (is_array($this->meta)
                && isset($this->meta[$key])
            ) {
                return $this->meta[$key];
            }

            return null;
        }

        return $this->meta;
    }

    /**
     * Renvoie la version
     *
     * @param string $key Key
     *
     * @return mixed
     */
    public function getVersion($key = null)
    {
        if ($key != null) {
            if (is_array($this->version)
                && isset($this->version[$key])
            ) {
                return $this->version[$key];
            }

            return null;
        }

        return $this->version;
    }

    /**
     * Renvoie les valeurs
     *
     * @param string $key Key
     *
     * @return mixed
     */
    public function getValues($key = null)
    {
        if ($key != null) {
            if (is_array($this->values)
                && isset($this->values[$key])
            ) {
                return $this->values[$key];
            }

            return '';
        }

        return $this->values;
    }

    /**
     * Renvoie les attributs éditables
     *
     * @param string $key Key
     *
     * @return string
     */
    public function getEditableAttributes($key)
    {
        if (!$this->connected) {
            return '';
        }

        $field = $this->getGabarit()->getChamp($key);
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
            return ' data-mercury="' . $type . '" id="champ' . $field['id'] . '" ';
        }

        return '';
    }

    /**
     * Renvoie les blocs
     *
     * @param mixed $name Nom
     *
     * @return GabaritBloc[]|GabaritBloc
     */
    public function getBlocs($name = null)
    {
        if ($name == null) {
            return $this->blocs;
        }

        if (!isset($this->blocs[$name])) {
            return false;
        }

        return $this->blocs[$name];
    }

    /**
     * Renvoie un parent
     *
     * @param int $i I
     *
     * @return GabaritPage
     */
    public function getParent($index)
    {
        if (array_key_exists($index, $this->parents)) {
            return $this->parents[$index];
        }

        return false;
    }

    /**
     * Renvoie les parents
     *
     * @return GabaritPage[]
     */
    public function getParents()
    {
        return $this->parents;
    }

    /**
     * Retourne la première page enfant
     *
     * @return gabaritPage
     */
    public function getFirstChild()
    {
        return $this->firstChild;
    }

    /**
     * Retourne le formulaire de création/d'édition de la page
     *
     * @param string $action       Adresse de l'action du formulaire
     * @param string $retour       Adresse de retour
     * @param array  $redirections Tableau des redirections
     * @param array  $authors      Tableau des auteurs
     *
     * @return string formulaire au format HTML
     */
    public function getForm(
        $action,
        $retour,
        $redirections = [],
        $authors = []
    ) {
        $this->view = [];

        $this->view['action'] = $action;
        $this->view['retour'] = $retour;
        $this->view['authors'] = $authors;

        if (count($redirections) == 0) {
            $this->view['redirections'] = [''];
        } else {
            $this->view['redirections'] = $redirections;
        }

        $this->view['versionId'] = $this->version['id'];
        $this->view['metaId'] = isset($this->meta['id']) ? $this->meta['id'] : 0;
        $this->view['metaLang'] = isset($this->meta['id_version']) ? $this->meta['id_version'] : BACK_ID_VERSION;
        $this->view['noMeta'] = !$this->gabarit->getMeta() || !$this->view['metaId'] ? ' style="display: none;" ' : '';
        $this->view['noMetaTitre'] = !$this->gabarit->getMetaTitre() ? ' style="display: none;" ' : '';
        $this->view['noRedirections301'] = !$this->gabarit->get301Editable() ? ';display: none' : '';
        $this->view['parentSelect'] = '';
        $this->view['allchamps'] = $this->gabarit->getChamps();
        $this->view['api'] = $this->gabarit->getApi();

        /*
         * Tri commun entre les blocs dyn et blocs statiques
         * @todo revoir gabaritManager pour prendre en compte l'ordre et ne pas faire la requete dans le model lui-même
         */
        $groups = Registry::get('db')->query(
            '(SELECT label, ordre, "group" as type FROM gab_champ_group'
            . ' WHERE id_gabarit = ' . $this->getGabarit()->getId() . ')'
            . ' UNION'
            . '(SELECT name, ordre, "bloc" as type FROM gab_bloc'
            . ' WHERE id_gabarit = ' . $this->getGabarit()->getId() . ')'
            . ' ORDER BY ordre'
        )->fetchAll(\PDO::FETCH_UNIQUE);

        $groups = array_merge(['general' => ['ordre' => 0, 'type' => 'group']], $groups);

        $allFieldsets = [];
        foreach ($groups as $groupName => $group) {
            if ($group['type'] == 'group') {
                // Si aucun champ en vrac
                if (!isset($this->gabarit->getChamps()[$groupName])) {
                    continue;
                }
                $allFieldsets[] = [
                    'name'   => $groupName,
                    'fields' => $this->gabarit->getChamps()[$groupName]
                ];
            } else {
                $allFieldsets[] = $this->getBlocs($groupName);
            }
        }

        $this->view['fieldsets'] = $allFieldsets;

        ob_start();
        $customForm = FrontController::search('Model/Gabarit/form/default/default.phtml', false);

        if ($customForm !== false) {
            include $customForm;
        } else {
            include __DIR__ . '/Gabarit/form/default/default.phtml';
        }

        $form = ob_get_clean();

        return $form;
    }

    /**
     * Inclut le sélecteur des parents
     *
     * @return void
     */
    public function selectParents()
    {
        $path = '/Gabarit/form/default/selectparents.phtml';

        $customForm = FrontController::search('Model' . $path, false);

        if ($customForm !== false) {
            include $customForm;
        } else {
            include __DIR__ . $path;
        }
    }

    /**
     * Création du formulaire
     *
     * @return string
     */
    public function buildForm()
    {
        $form = '<input type="hidden" name="id_' . $this->gabarit->getTable()
        . '" value="' . (isset($this->values['id']) ? $this->values['id'] : '')
        . '" />';

        $allchamps = $this->gabarit->getChamps();

        $id_gab_page = isset($this->meta['id']) ? $this->meta['id'] : 0;

        foreach ($allchamps as $name_group => $champs) {
            $form .= '<fieldset><legend>' . $name_group . '</legend>'
            . '<div ' . ($id_gab_page ? 'style="display:none;"' : '') . '>';
            foreach ($champs as $champ) {
                $value = isset($this->values[$champ['name']]) ? $this->values[$champ['name']] : '';
                $id = isset($this->meta['id_version']) ? $this->meta['id_version'] : '';
                $form .= $this->buildChamp($champ, $value, $id, $id_gab_page, $id);
            }
            $form .= '</div></fieldset>';
        }

        foreach ($this->blocs as $blocName => $bloc) {
            $form .= $bloc->buildForm($id_gab_page, $this->version['id']);
        }

        return $form;
    }

    /**
     * Convertit les attributs de l'objet en tableau
     *
     * @return array
     */
    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();
        $attributes['meta']  = $this->meta;
        $attributes['blocs'] = [];

        foreach ($this->getBlocs() as $name => $bloc) {
            $attributes['blocs'][$name] = $bloc->jsonSerialize();
        }

        return $attributes;
    }
}
