<?php
/**
 * Datatable Class
 *
 * Créer un tableau avancé
 *
 * @package Datatable
 * @author shin
 */

namespace Solire\Lib\Datatable;

use Solire\Lib\Loader;
use Solire\Lib\FrontController;
use Solire\Lib\Tools;
use Solire\Lib\Model\fileManager;

/**
 * Datatable Class
 *
 * Créer un tableau avancé
 *
 * @package Datatable
 * @author shin
 */
class Datatable
{

    /**
     * Nom de la vue à utiliser
     *
     * @var string
     * @access protected
     */
    protected $view = "datatable";

    /**
     * Réponse JSON des données qui sera renvoyé
     *
     * @var string
     * @access protected
     */
    protected $response = "";

    /**
     * Chemin du répertoire contenant les vues
     *
     * @var string
     * @access protected
     */
    protected $viewPath = "view/";

    /**
     * Si vrai, le chemin des vues sera relatif
     * à l'objet instancié
     *
     * @var bool
     * @access protected
     */
    protected $viewPathRelative = false;

    /**
     * Chemin du répertoire contenant les feuilles de style
     *
     * @var string
     * @access protected
     */
    protected $cssPath = "./datatable/";

    /**
     * Chemin du répertoire contenant les scripts javascript
     *
     * @var string
     * @access protected
     */
    protected $jsPath = "./datatable/";

    /**
     * Chemin du répertoire contenant les images
     *
     * @var string
     * @access protected
     */
    protected $imgPath = "img/datatable/";

    /**
     * Action executé
     *
     * @var string
     * @access protected
     */
    protected $action = "datatable";

    /**
     * Chemin du répertoire contenant les fichiers de configurations
     *
     * @var string
     * @access protected
     */
    protected $configPath = "config/datatable/";

    /**
     * Nom du fichier de configuration qui sera utilisé
     *
     * @var string
     * @access protected
     */
    protected $configName = "";

    /**
     * Connexion à la base de données qui sera utilisé
     *
     * @var \Solire\Lib\MyPDO
     * @access protected
     */
    protected $db;

    /**
     * Paramètres GET de l'url
     *
     * @var array
     * @access protected
     */
    protected $get;

    /**
     * Chargeur de script Javascript
     *
     * @var Loader\Javascript
     * @access protected
     */
    protected $javascript;

    /**
     * Chargeur de feuilles de styles
     *
     * @var Loader\Css
     * @access protected
     */
    protected $css;

    /**
     * Clause where de la requête
     *
     * @var string
     * @access protected
     */
    protected $where;

    /**
     *
     *
     * @var string
     * @access protected
     */
    protected $beforeHTML;

    /**
     *
     *
     * @var string
     * @access protected
     */
    protected $beforeTableHTML;

    /**
     *
     *
     * @var string
     * @access protected
     */
    protected $afterTableHTML;

    /**
     *
     *
     * @var string
     * @access protected
     */
    protected $additionalWhereQuery;

    /**
     *
     *
     * @var string
     * @access protected
     */
    protected $additionalJoinQueryCount;

    /**
     *
     *
     * @var string
     * @access protected
     */
    protected $pluginsOutput = "";

    /**
     *
     *
     * @var string
     * @access protected
     */
    protected $additionalForm;

    /**
     *
     *
     * @var string
     * @access protected
     */
    protected $columnActionButtons;

    /**
     *
     *
     * @var \Solire\Lib\Log
     * @access protected
     */
    protected $log;

    /**
     * Constructeur
     *
     * Défini les chemins des ressources, la connexion à la base de données
     * ainsi que les paramètres GET de l'url et le nom du fichier de configuration
     */
    public function __construct(
        $get,
        $configPath,
        $db = null,
        $cssPath = "./datatable/",
        $jsPath = "./datatable/",
        $imgPath = "./img/datatable/",
        $log = null
    ) {
        $this->db = $db;
        $this->get = $get;

        $pathInfoConfig = pathinfo($configPath);

        $this->configPath = $pathInfoConfig["dirname"];
        $this->configName = str_replace(".cfg", "", $pathInfoConfig["filename"]);

        $this->log = $log;


        /* Augmentation de la limite des group_concat */
        $this->db->exec("SET SESSION group_concat_max_len = 100000;");

        if (isset($this->get["json"])) {
            $this->view = "json";
            $this->action = "json";
        }

        if (isset($this->get["editable"])) {
            $this->view = "editable";
            $this->action = "editable";
        }

        if (isset($this->get["editRender"])) {
            $this->view = "";
            $this->action = "editFormRender";
        }

        if (isset($this->get["add"])) {
            $this->view = "";
            $this->action = "add";
        }

        if (isset($this->get["edit"])) {
            $this->view = "";
            $this->action = "edit";
        }

        if (isset($this->get["select_load"])) {
            $this->view = "";
            $this->action = "selectLoad";
        }

        if (isset($this->get["multi_autocomplete"])) {
            $this->view = "";
            $this->action = "multiAutocomplete";
        }

        if (isset($this->get["dt_action"]) && $this->get["dt_action"] != "") {
            $this->view = "";
            $this->action = $this->get["dt_action"];
        }



        //Paramètrage du chemin des ressources
        $this->cssPath = $cssPath;
        $this->jsPath = $jsPath;
        $this->imgPath = $imgPath;

        //Création d'un chargeur JS/CSS
        $this->javascript = new Loader\Javascript(FrontController::$publicDirs);
        $this->css = new Loader\Css(FrontController::$publicDirs);
    }

    // --------------------------------------------------------------------

    /**
     * Initialise le datatable
     *
     * Cette méthode est toujours appelée.
     * Elle permet de charger le fichier de configuration
     * Puis elle appelle l'action à executer
     *
     * @return 	void
     */
    public function start()
    {
        if ($this->configName != '') {
            include $this->configPath . DIRECTORY_SEPARATOR . $this->configName . '.cfg.php';

            if(isset($_REQUEST["table"])) {
                $this->name = $_REQUEST["table"];
            } else
                $this->name = str_replace(array(".", "-"), "_", $this->configName) . '_' . str_replace(array(" ", "."), "", microtime());
            $this->nameConfig = str_replace(array(".", "-"), "_", $this->configName);
            $this->config = $config;

            $sTable = $this->config["table"]["name"];

            /* INITIALISATION DES PLUGINS */
            $plugins = array();
            if (isset($this->config["plugins"])) {
                foreach ($this->config["plugins"] as $plugin) {
                    $pluginName = "\Solire\Lib\Datatable\Plugin\Datatable" . $plugin;
                    $plugins[$pluginName] = new $pluginName($this->db, $this);
                }
            }

            $this->aFilterColumnAdditional = array();
            $this->additionalParams = "";

            if (isset($_GET["filter"])) {
                foreach ($_GET["filter"] as $filter) {
                    list($filterColumn, $filterValue) = explode("|", $filter);
                    $this->aFilterColumnAdditional[] = $filterColumn . ' = ' . $this->db->quote($filterValue);
                }
                $params["filter"] = $_GET["filter"];
//                $this->additionalParams = http_build_query($params);
            }

            if (isset($this->config["extra"]["logical delete"])) {
                $this->aFilterColumnAdditional[] = $sTable . "." . $this->config["extra"]["logical delete"]["column_bool"] . ' = 0';
            }
        }

        $columnAction = array();
        $columnActionButtons = array();


        $columnAction[0] = array(
            "width" => "93px",
            "sorting" => false,
            "content" => '<div class="btn-group">',
            "show" => true,
            "title" => "Action",
        );

        if (isset($this->config["extra"])
                && isset($this->config["extra"]["show"]) && $this->config["extra"]["show"]
        ) {
            $columnActionButtons[] = '
                <a href="#" class="btn btn-small btn-info show-item-no-ajax" title="Visualiser"><i class="icon-eye-open"></i></a>
                ';
        }

        if (isset($this->config["extra"])
                && isset($this->config["extra"]["editable"]) && $this->config["extra"]["editable"]
                && (!isset($this->config["form"])
                || !isset($this->config["form"]["ajax"])
                || $this->config["form"]["ajax"] == true)) {
            $columnActionButtons[] = '
                <a href="#" class="btn btn-small btn-info edit-item" title="Modifier"><i class="icon-pencil"></i></a>
                ';
        }

        if (isset($this->config["extra"])
                && isset($this->config["extra"]["editable"]) && $this->config["extra"]["editable"]
                && isset($this->config["form"])
                && isset($this->config["form"]["ajax"])
                && $this->config["form"]["ajax"] == false) {
            $columnActionButtons[] = '
                <a href="#" class="btn btn-small btn-info edit-item-no-ajax" title="Modifier"><i class="icon-pencil"></i></a>
                ';
        }

        if (isset($this->config["extra"])
                && isset($this->config["extra"]["deletable"]) && $this->config["extra"]["deletable"]) {
            $columnActionButtons[] = '
                <a href="#" class="btn btn-small btn-warning btn-info delete-item" title="Supprimer"><i class="icon-trash"></i></a>
                ';
        }

        /** On teste si l'option selectable est activé **/
        if (isset($this->config["extra"])
                && isset($this->config["extra"]["selectable"]) && $this->config["extra"]["selectable"]) {
            /** On ajoute une colonne checkbox **/
            $columnSelectable = array(
                'width' =>  '10px',
                'content' => '<input type="checkbox" class="datatable-selectable" />',
                'show' => true,
                'sorting'   =>  false,
                'title' => '<input type="checkbox" class="datatable-selectable-all" />',
            );
            array_unshift($this->config["columns"], $columnSelectable);
        }

        $this->columnActionButtons = $columnActionButtons;

        $this->url = self::selfURL() . "&table=" . $this->name ;
        $this->urlRaw = self::selfURLRaw();

        $this->beforeRunAction();

        if (count($this->columnActionButtons) > 0) {
            $columnAction[0]["content"] .= implode("", $this->columnActionButtons) . "</div>";
            $columnAction[0]["show_page_detail"] = false;
            $this->config["columns"] = array_merge($this->config["columns"], $columnAction);
        }

        if (method_exists($this, $this->action . "Action")) {
            call_user_func(array($this, $this->action . "Action"));

            /* Appel action dans plugins */
            if (isset($plugins) && count($plugins)) {
                foreach ($plugins as $plugin) {
                    if (method_exists($plugin, $this->action . "Action")) {
                        $this->pluginsOutput = call_user_func(array($plugin, $this->action . "Action"));
                    }
                }
            }
        }
    }

    protected function beforeRunAction() {

    }

    // --------------------------------------------------------------------

    /**
     * Permet de modifier le nombre d'item affiché par defaut
     *
     * @param string $nbItems nombre d'items
     * @return void
     */
    public function setDefaultNbItems($nbItems) {
        $this->config["table"]["default_nb_items"] = $nbItems;
    }

    // --------------------------------------------------------------------

    /**
     * Permet de modifier les styles
     *
     * @param array $style
     * @return void
     */
    public function addStyle($style) {
        if (!isset($this->config["style"])) {
            $this->config["style"] = array();
        }
        $this->config["style"] = array_merge($this->config["style"], $style);
    }

    // --------------------------------------------------------------------

    /**
     * Permet d'ajouter un filtre sur les requêtes
     *
     * @param string $whereString chaine sql de filtre
     * @return void
     */
    public function additionalWhereQuery($whereString) {
        $this->additionalWhereQuery = $whereString;
    }

    /**
     * Permet d'ajouter une jointure à la requete de comptage total
     *
     * @param string $joinString chaine sql de jointure
     * @return void
     */
    public function additionalJoinQueryCount($joinString) {
        $this->additionalJoinQueryCount = $joinString;
    }

    // --------------------------------------------------------------------

    /**
     * TODO à commenter
     *
     * @return 	void
     */
    public function beforeTable($html) {
        $this->beforeTableHTML = $html;
    }

    // --------------------------------------------------------------------

    /**
     * Permet entre autre d'ajouter le fil d'ariane
     *
     * @return 	void
     */
    public function beforeHtml($html) {
        $this->beforeHTML = $html;
    }

    // --------------------------------------------------------------------

    /**
     * Renvoi l'action courante
     *
     * @return 	string
     */
    public function getAction() {
        return $this->action;
    }

    // --------------------------------------------------------------------

    /**
     * Renvoi le fil de fer
     *
     * @return 	array
     */
    public function getBreadCrumbs() {
        $breadCrumbs = array();

        $breadCrumbs[] = array(
            "label" => $this->config["table"]["title"],
            "url" => isset($this->customeURLRaw) ? $this->customeURLRaw : $this->selfURLRaw(),
        );

        switch ($this->getAction()) {
            case "formAddRender":
                $breadCrumbs[] = array(
                    "label" => isset($this->pageTitle) ? $this->pageTitle : "Ajouter un" . $this->config["table"]["suffix_genre"] . " " . $this->config["table"]["title_item"],
                    "url" => "",
                );

                break;
            case "formEditRender":
                $breadCrumbs[] = array(
                    "label" => isset($this->pageTitle) ? $this->pageTitle : "Modifier un" . $this->config["table"]["suffix_genre"] . " " . $this->config["table"]["title_item"],
                    "url" => "",
                );

                break;
            case "show":
                $breadCrumbs[] = array(
                    "label" => isset($this->pageTitle) ? $this->pageTitle : "Détails " . $this->config["table"]["title_item"],
                    "url" => "",
                );

                break;

            default:
                break;
        }

        return $breadCrumbs;
    }

    // --------------------------------------------------------------------

    /**
     * TODO à commenter
     *
     * @return 	void
     */
    public function afterTable($html) {
        $this->afterTableHTML = $html;
    }

    // --------------------------------------------------------------------

    /**
     * Action qui va généré le HTML du tableau
     *
     * @return 	void
     */
    public function datatableAction() {
        $this->javascript->addLibrary($this->jsPath . "jquery/jquery.livequery.min.js");
        $this->javascript->addLibrary($this->jsPath . "jquery/jquery.dataTables.js");
        $this->javascript->addLibrary($this->jsPath . "jquery/jquery.jeditable.js");
        $this->javascript->addLibrary($this->jsPath . "jquery/jquery.autogrow.js");
        $this->javascript->addLibrary($this->jsPath . "jquery/jquery.jeditable.autogrow.js");

        $this->javascript->addLibrary($this->jsPath . "jquery/FixedHeader.js");
        $this->javascript->addLibrary($this->jsPath . "jquery/jquery.dataTables.columnFilter.js");

        if (isset($this->config["extra"])
                && isset($this->config["extra"]["hide_columns"]) && $this->config["extra"]["hide_columns"]) {
            $this->javascript->addLibrary($this->jsPath . "jquery/ColVis.js");
        }

        /* Formulaire de création AJAX ONLY */
        if (isset($this->config["extra"])
                && (isset($this->config["extra"]["creable"]) && $this->config["extra"]["creable"]
                || isset($this->config["extra"]["editable"]) && $this->config["extra"]["editable"])
                && (!isset($this->config["form"])
                || !isset($this->config["form"]["ajax"])
                || $this->config["form"]["ajax"] == true)) {
            $this->javascript->addLibrary($this->jsPath . "jquery/jquery.selectload.js");
            $this->javascript->addLibrary($this->jsPath . "jquery/jquery.tmpl.min.js");
            $this->javascript->addLibrary("back/js/plupload/plupload.full.min.js");
            $this->javascript->addLibrary($this->jsPath . "jquery/plupload_custom.js");
            $this->addRenderAction();
            if (isset($this->config["style"])
                    && isset($this->config["style"]["form"])) {
                $path = isset($this->config["style"]["formpath"]) ? $this->config["style"]["formpath"] : null;
                $this->beforeTableHTML .= $this->addRender($this->config["style"]["form"], $path);
            } else {
                $this->beforeTableHTML .= $this->addRender();
            }
        }

        /* Formulaire de création AJAX FALSE */
        if (isset($this->config["extra"])
                && isset($this->config["extra"]["creable"]) && $this->config["extra"]["creable"]
                && isset($this->config["form"])
                && isset($this->config["form"]["ajax"])
                && $this->config["form"]["ajax"] == false) {
            $this->beforeTableHTML .= '
                <div  class="btn-a gradient-green">
                    <a href="' . $this->url . '&dt_action=formAddRender' . '">Ajouter un' . $this->config["table"]["suffix_genre"] . ' ' . $this->config["table"]["title_item"] . '</a>
                </div>';
        }

        $this->javascript->addLibrary($this->jsPath . "jquery/ZeroClipboard.js");
        $this->javascript->addLibrary($this->jsPath . "jquery/TableTools.js");



        $this->css->addLibrary($this->cssPath . "demo_table_jui.css");
        $this->css->addLibrary($this->cssPath . "ColVis.css");
        $this->css->addLibrary($this->cssPath . "TableTools_JUI.css");

        if (isset($this->config["additional_script"]) && count($this->config["additional_script"]) > 0) {
            foreach ($this->config["additional_script"] as $script) {
                $this->javascript->addLibrary($script);
            }
        }

        if (isset($this->config["additional_stylesheet"]) && count($this->config["additional_stylesheet"]) > 0) {
            foreach ($this->config["additional_stylesheet"] as $stylesheet) {
                $this->css->addLibrary($stylesheet);
            }
        }

        //Configuration de l'entete et pied du tableau
        $this->sDom = '<"H"'
                . 'TC'
                . (isset($this->config["extra"]["desactiveLengthChanging"]) && $this->config["extra"]["desactiveLengthChanging"] ? '' : 'l' )
                . (isset($this->config["extra"]["desactiveFilteringInput"]) && $this->config["extra"]["desactiveFilteringInput"] ? '' : 'f' )
                . 'r>t'
                . '<"F"'
                . (isset($this->config["extra"]["desactiveInformation"]) && $this->config["extra"]["desactiveInformation"] ? '' : 'i' )
                . (isset($this->config["extra"]["desactivePagination"]) && $this->config["extra"]["desactivePagination"] ? '' : 'p' )
                . '>';

        $sFilterColumn = array();

        foreach ($this->config["columns"] as $column) {
            if (isset($column["filter"]))
                $sFilterColumn[] = $column["name"] . ' = ' . $this->db->quote($column["filter"]);
        }

        if ($this->additionalWhereQuery != "")
            $sFilterColumn[] = $this->additionalWhereQuery;

        $generalWhere = implode(" AND ", $sFilterColumn);


        /* DB table to use */
        $sTable = $this->config["table"]["name"];

        foreach ($this->config["columns"] as $iKeyJoin => &$column) {
            if (isset($column["filter_field"]) && $column["filter_field"] == "select") {
                /* Jointure sur autre table */
                $selectSqlArray = array();
                if (isset($column["from"]) && $column["from"]) {
                    $aVal = array();
                    foreach ($column["from"]["columns"] as $sCol) {
                        $sColVal = current($sCol);
                        $sColKey = key($sCol);
                        $column2 = $sCol;
                        /* Double jointure  */
                        if (isset($column2["from"]) && $column2["from"]) {
                            $table = "`" . $column2["from"]["table"] . "` `" . $column2["from"]["table"] . "_$iKeyJoin`";
                            /* On construit le select  */
                            $aVal = array();
                            foreach ($column2["from"]["columns"] as $sCol) {
                                $sColVal = current($sCol);
                                $sColKey = key($sCol);

                                if ($sColKey === "name") {
                                    $sColVal2 = next($sCol);
                                    $sColKey2 = key($sCol);
                                    if ($sColKey2 == "sql") {
                                        continue;
                                    } else
                                        $aVal[] = "`" . $column2["from"]["table"] . "_$iKeyJoin`.`$sColVal`";
                                } else {
                                    $aVal[] = $this->db->quote($sColVal);
                                }
                            }

                            foreach ($column2["from"]["index"] as $sColIndex => $sColIndexVal) {
                                if ($sColIndexVal == "THIS") {
//                                    $selectSqlArray[] = $sColIndex . " id";
                                }
                            }
                            /* FIN Double jointure  */
                        } else {
                            $table = $column["from"]["table"];
                            if ($sColKey === "name") {
                                $sColVal2 = next($sCol);
                                $sColKey2 = key($sCol);
                                if ($sColKey2 == "sql") {
                                    $aVal[] = "$sColVal2";
                                } else
                                    $aVal[] = "`" . $column["from"]["table"] . "`.`$sColVal`";
                            } else {
                                $aVal[] = $this->db->quote($sColVal);
                            }
                            foreach ($column["from"]["index"] as $sColIndex => $sColIndexVal) {
                                if ($sColIndexVal == "THIS") {
//                                    $selectSqlArray[] = $sColIndex . " id";
                                }
                            }
                        }
                    }
                    $selectSqlArray[] = "CONCAT(" . implode(",", $aVal) . ") name ";
                    $column["values"] = $this->db->query(
                                    'SELECT  DISTINCT ' . implode(",", $selectSqlArray) . '
                                     FROM ' . $table . (isset($column["filter_field_where"]) && $column["filter_field_where"] != "" ? " WHERE " . $column["filter_field_where"] : "") . ';')->fetchAll(\PDO::FETCH_COLUMN);
                } elseif (isset($column["sql"])) {
                    $column["values"] = $this->db->query("SELECT DISTINCT " . $column["sql"] . ""
                                    . " FROM `" . $this->config["table"]["name"] . "` WHERE " . $column["sql"] . " <> '' "
                                    . ($generalWhere == "" ? "" : "AND $generalWhere" )
                                    . " ORDER BY `" . $column["name"] . "` ASC")->fetchAll(\PDO::FETCH_COLUMN);
                } else {
                    $column["values"] = $this->db->query("SELECT DISTINCT `" . $column["name"] . "`"
                                    . " FROM `" . $this->config["table"]["name"] . "` WHERE `" . $column["name"] . "` <> '' "
                                    . ($generalWhere == "" ? "" : "AND $generalWhere" )
                                    . " ORDER BY `" . $column["name"] . "` ASC")->fetchAll(\PDO::FETCH_COLUMN);
                }
            }
        }
    }

    // --------------------------------------------------------------------

    /**
     * Affichage détails d'une ligne
     *
     * @return 	void
     */
    public function showAction() {
        $index = intval($_GET["index"]);
        $this->data = $this->getDataFormat($index);
        $this->view = "show";
    }

    // --------------------------------------------------------------------

    /**
     * Action qui va être appelée pour ajouter un item
     *
     * @return 	void
     */
    public function addAction() {

        $values = array();
        $queryAfterData = array();
        foreach ($this->config["columns"] as $column) {
            if (isset($column["creable_field"])) {
                if (isset($column["creable_field"]["value"]))
                    $values[$column["name"]] = $column["creable_field"]["value"];
                else if (isset($column["creable_field"]["encryption"])) {
                    $values[$column["name"]] = hash($column["creable_field"]["encryption"], $_POST[$column["name"]]);
                } elseif (isset($column['creable_field']['type'])
                        && $column['creable_field']['type'] == 'password'
                ) {
                    /**
                     * Si le champ est un mot de passe on le fait passer
                     * par la fonction de préparation des mots de passes
                     */
                    $values[$column['name']] = \Solire\Lib\Session::prepareMdp($_POST[$column['name']]);
                } else if (isset($column["creable_field"]["type"]) && $column["creable_field"]["type"] == "multi-autocomplete") {
                    if (isset($_POST[$column["name"]]) && $column["name"] != "") {
                        $ids = explode(",", $_POST[$column["name"]]);
                        $queryAfterData = array();
                        $queryAfterData["table"] = $column["from"]["table"];
                        $queryAfterData["columnjoin"] = key($column["from"]["index"]);
                        $queryAfterData["values"] = array();
                        foreach ($ids as $id) {
                            $queryAfterData["values"][][$column["from"]["columns"][0]["name"]] = $id;
                        }
                    }
                } else {
                    if (isset($_POST[$column["name"]]))
                        $values[$column["name"]] = $_POST[$column["name"]];
                }
            }
        }

        if (isset($_GET["filter"])) {
            foreach ($_GET["filter"] as $filter) {
                list($filterColumn, $filterValue) = explode("|", $filter);
                $values[$filterColumn] = $filterValue;
            }
        }

        /* DB table to use */
        $sTable = $this->config["table"]["name"];



        $r = $this->db->insert($sTable, $values);
        $insertId = $this->db->lastInsertId();

        if ($this->log && isset($this->config["log"]) && isset($this->config["log"]["create"])) {
            $this->log->logThis("Ajout de " . $sTable, $this->utilisateur->get("id"), "<b>Id</b> : " . $insertId);
        }

        if (count($queryAfterData) > 0) {
            foreach ($queryAfterData["values"] as $queryData) {
                $queryData[$queryAfterData["columnjoin"]] = $insertId;
                $this->db->insert($queryAfterData["table"], $queryData);
            }
        }


        $this->afterAddAction($insertId);

        exit(1);
    }

    // --------------------------------------------------------------------

    /**
     * Action qui va être appelée pour modifier un item
     *
     * @return 	void
     */
    public function editAction() {

        $filter = explode('|', $_GET["index"]);
        $i = 0;
        $j = 0;
        $where = array();

        $queryAfterData = array();

        $values = array();
        foreach ($this->config["columns"] as $column) {
            if (isset($column["index"]) && $column["index"]) {
                $where[] = $column["name"] . " = " . $this->db->quote($filter[$i]);
                if ($column["name"] == "cle")
                    $where[] = $column["name"] . " LIKE BINARY " . $this->db->quote($filter[$i]);
                $i++;
            }
            if (isset($column["creable_field"])) {
                if (isset($column["creable_field"]["editable"]) && $column["creable_field"]["editable"] == false) {
                    continue;
                }
                if (isset($column["creable_field"]["value"])) {
                    $values[$column["name"]] = $column["creable_field"]["value"];
                } else if (isset($column['creable_field']['encryption'])) {
                    if ($column['creable_field']['type'] != 'password' || $_POST[$column['name']] != '') {
                        $values[$column["name"]] = hash($column["creable_field"]["encryption"], $_POST[$column["name"]]);
                    }
                } elseif (isset($column['creable_field']['type'])
                        && $column['creable_field']['type'] == 'password'
                ) {
                    /**
                     * Si le champ est un mot de passe on le fait passer
                     * par la fonction de préparation des mots de passes
                     */
                    $values[$column['name']] = \Solire\Lib\Session::prepareMdp($_POST[$column['name']]);
                } else if (isset($column["creable_field"]["type"]) && $column["creable_field"]["type"] == "multi-autocomplete") {
                    if (isset($_POST[$column["name"]]) && $column["name"] != "") {
                        $ids = explode(",", $_POST[$column["name"]]);
                        $queryAfterData = array();
                        $queryAfterData["table"] = $column["from"]["table"];
                        $queryAfterData["columnjoin"] = key($column["from"]["index"]);
                        $queryAfterData["values"] = array();
                        foreach ($ids as $id) {
                            $queryAfterData["values"][][$column["from"]["columns"][0]["name"]] = $id;
                        }
                    }
                } else {
                    if (isset($_POST[$column["name"]])) {
                        $values[$column["name"]] = $_POST[$column["name"]];
                    } else {
                        $values[$column["name"]] = '';
                    }
                }
            }
        }




        /* DB table to use */
        $sTable = $this->config["table"]["name"];


        if (count($where) != 0) {
            $r = $this->db->update($sTable, $values, implode(" AND ", $where));
            if (count($queryAfterData) > 0) {
                $this->db->delete($queryAfterData["table"], $queryAfterData["columnjoin"] . " = " . intval($_GET["index"]));
                foreach ($queryAfterData["values"] as $queryData) {
                    $queryData[$queryAfterData["columnjoin"]] = $_GET["index"];
                    $this->db->insert($queryAfterData["table"], $queryData);
                }
            }
            if ($this->log && isset($this->config["log"]) && isset($this->config["log"]["edit"])) {
                $this->log->logThis("Edition de " . $sTable, $this->utilisateur->get("id"), "<b>Id</b> : " . $_GET["index"]);
            }

            $this->afterEditAction($_GET["index"]);
        }




        exit(1);
    }

    // --------------------------------------------------------------------

    /**
     * Formulaire sans ajax
     *
     * @return void
     */
    public function formAddRenderAction() {
        $this->addRenderAction();
        $this->javascript->addLibrary($this->jsPath . "jquery/jquery.selectload.js");
        $this->javascript->addLibrary($this->jsPath . "jquery/jquery.tmpl.min.js");
        $this->javascript->addLibrary("back/plupload/plupload.full.min.js");
        $this->javascript->addLibrary($this->jsPath . "jquery/plupload_custom.js");
        $this->noModal = "no";
        $this->view = "form/bootstrap-ajax-false";
    }

    // --------------------------------------------------------------------

    /**
     * Formulaire sans ajax
     *
     * @return void
     */
    public function formEditRenderAction() {
        if (isset($this->config["extra"])
                && isset($this->config["extra"]["editable"]) && $this->config["extra"]["editable"]) {
            $this->data = $this->getData($_GET["index"]);
            $this->modeEdit = true;
            $this->editRenderAction();
            $this->javascript->addLibrary($this->jsPath . "jquery/jquery.selectload.js");
            $this->javascript->addLibrary($this->jsPath . "jquery/jquery.tmpl.min.js");
            $this->javascript->addLibrary("back/plupload/plupload.full.min.js");
            $this->javascript->addLibrary($this->jsPath . "jquery/plupload_custom.js");
            $this->noModal = "no";
            $this->view = "form/bootstrap-ajax-false";
        }
    }

    // --------------------------------------------------------------------

    /**
     * Action qui va être appelée pour supprimer un élément
     *
     * @return 	void
     */
    public function deleteAction() {


        $filter = explode('|', $_POST["row_id"]);
        $i = 0;
        $j = 0;
        $where = array();
        foreach ($this->config["columns"] as $column) {
            if (isset($column["index"]) && $column["index"]) {
                $where[] = $column["name"] . " = " . $this->db->quote($filter[$i]);
                if ($column["name"] == "cle")
                    $where[] = $column["name"] . " LIKE BINARY " . $this->db->quote($filter[$i]);
                $i++;
            }
        }




        /* DB table to use */
        $sTable = $this->config["table"]["name"];

        $row = $this->db->select($sTable, "*", false, implode(" AND ", $where));

        if (isset($this->config["extra"]["logical delete"])) {
            $values = array(
                $this->config["extra"]["logical delete"]["column_bool"] => 1,
                $this->config["extra"]["logical delete"]["column_date"] => date('Y-m-d H:i:s'),
            );
            $r = $this->db->update($sTable, $values, implode(" AND ", $where));
        } else {
            $r = $this->db->delete($sTable, implode(" AND ", $where));
        }

        if ($this->log && isset($this->config["log"]) && isset($this->config["log"]["delete"])) {
            $this->log->logThis("Suppression de " . $sTable, $this->utilisateur->get("id"), "<b>Id</b> : " . implode(" AND ", $where));
        }



        $this->afterDeleteAction(current($row));

        if ($r)
            exit(1);
    }

    /**
     * Action qui va être appelée pour lors de la selection/ deselection d'un element
     *
     * @return 	void
     */
    public function selectAction() {
        $selectArrayName = "tableau-" . $_GET["table"] . "_select";
        $selectRows = isset($SESSION[$selectArrayName]) ? $SESSION[$selectArrayName] : array();
        if($_POST["select"] == 0) {
            unset($selectRows[$_POST["row_id"]]);
        } else {
            $selectRows[$_POST["row_id"]] = $_POST["row_id"];
        }

        $SESSION[$selectArrayName] = $selectRows;
        $r = array(
            "status"    => 1,
            "number"    =>  count($SESSION[$selectArrayName])
        );
        echo json_encode($r);
        exit();
    }

    /**
     * Action qui va être appelée pour lors de la selection/ deselection d'un element
     *
     * @return 	void
     */
    public function selectallAction() {
        $selectRows = array();
        $selectArrayName = "tableau-" . $_GET["table"] . "_select";
        if($_POST["select"] == 1) {
            //Permet de supprimer le limit dans la requete
            $_POST["iDisplayLength"] = -1;
            $this->jsonAction();
            $response = json_decode($this->response);
            for ($i = 0; $i < count($response->aaData); $i++) {
                $selectRows[$response->aaData[$i]->DT_RowId] = $response->aaData[$i]->DT_RowId;
            }
        }

        $SESSION[$selectArrayName] = $selectRows;
        $r = array(
            "status"    => 1,
            "number"    =>  count($SESSION[$selectArrayName])
        );
        echo json_encode($r);
        exit();
    }

    public function getSelectedRows() {
        $selectRows = array();
        $selectArrayName = "tableau-" . $_GET["table"] . "_select";
        $SESSION[$selectArrayName] = $selectRows;
    }

    // --------------------------------------------------------------------

    /**
     * Action qui va enregistrer les fichiers
     *
     * @return 	void
     */
    public function uploadAction() {
        if (isset($this->config["file"])) {
            $this->upload_path     = $this->config["file"]["upload_path"];
            $this->upload_temp     = $this->config["file"]["upload_temp"];
            $this->upload_vignette = $this->config["file"]["upload_vignette"];
            $this->upload_apercu   = $this->config["file"]["upload_apercu"];
        }
        $fileManager = new fileManager();
        $targetTmp      = $this->upload_temp;
        $targetDir      = $this->upload_path;
        $vignetteDir    = $this->upload_vignette;
        $apercuDir      = $this->upload_apercu;

        $json = $fileManager->upload($this->upload_path, $targetTmp,
            $targetDir, $vignetteDir, $apercuDir);

        $this->view = "";
        $this->response = json_encode($json);
    }

    // --------------------------------------------------------------------

    /**
     * Action qui va être appelée après l'ajout de l'item
     *
     * @return 	void
     */
    public function afterAddAction($insertId) {

    }

    // --------------------------------------------------------------------

    /**
     * Action qui va être appelée après la modification de l'item
     *
     * @return 	void
     */
    public function afterEditAction($insertId) {

    }

    // --------------------------------------------------------------------

    /**
     * Action qui va être appelée après la suppressiob de l'item
     *
     * @return 	void
     */
    public function afterDeleteAction($row) {

    }

    // --------------------------------------------------------------------

    /**
     * Action qui va être appelée pour charger les listes déroulantes
     *
     * @return 	void
     */
    public function selectLoadAction() {

        $values = array();
        $keyCol = $_REQUEST['load'];
        $selectSqlArray = array();
        $selectSqlArrayWhere = array();

        $column = $this->config["columns"][$keyCol];
        $aVal = array();
        foreach ($column["from"]["columns"] as $sCol) {
            $sColVal = current($sCol);
            $sColKey = key($sCol);
            $column2 = $sCol;
            /* Double jointure  */
            if (isset($column2["from"]) && $column2["from"]) {
                $table = "`" . $column2["from"]["table"] . "` `" . $column2["from"]["table"] . "_$keyCol`";
                /* On construit le select  */
                $aVal = array();
                foreach ($column2["from"]["columns"] as $sCol) {
                    $sColVal = current($sCol);
                    $sColKey = key($sCol);

                    if ($sColKey === "name") {
                        $sColVal2 = next($sCol);
                        $sColKey2 = key($sCol);
                        if ($sColKey2 == "sql") {
                            continue;
                        } else
                            $aVal[] = "`" . $column2["from"]["table"] . "_$keyCol`.`$sColVal`";
                    } else {
                        $aVal[] = $this->db->quote($sColVal);
                    }
                }

                foreach ($column2["from"]["index"] as $sColIndex => $sColIndexVal) {
                    if ($sColIndexVal == "THIS") {
                        $selectSqlArray[] = $sColIndex . " id";
                    }
                }
                /* FIN Double jointure  */
            } else {
                $table = $column["from"]["table"];
                if ($sColKey === "name") {
                    $sColVal2 = next($sCol);
                    $sColKey2 = key($sCol);
                    if ($sColKey2 == "sql") {
                        $aVal[] = "$sColVal2";
                    } else
                        $aVal[] = "`" . $column["from"]["table"] . "`.`$sColVal`";
                } else {
                    $aVal[] = $this->db->quote($sColVal);
                }
            }
        }
        foreach ($column["from"]["index"] as $sColIndex => $sColIndexVal) {
            if ($sColIndexVal == "THIS") {
                $selectSqlArray[] = $sColIndex . " id";
            } else {
                $selectSqlArrayWhere[] = $sColIndex . " = " . $this->db->quote($sColIndexVal);
            }
        }







        $selectSqlArray[] = "CONCAT(" . implode(",", $aVal) . ") name ";


        $response = array();
        $response = $this->db->query('
            SELECT ' . implode(",", $selectSqlArray) . '
            FROM ' . $table .
                        (count($selectSqlArrayWhere) > 0 ? " WHERE " . implode(" AND ", $selectSqlArrayWhere) : "") . ';')->fetchAll(\PDO::FETCH_UNIQUE);

        $this->response = json_encode($response, JSON_FORCE_OBJECT);
    }

    // --------------------------------------------------------------------

    /**
     * Action qui va être appelée pour charger les champs autocomplete multi
     *
     * @return 	void
     */
    public function multiAutocompleteAction() {


        $values = array();
        $keyCol = $_REQUEST['load'];

        $table = "";

        $column = $this->config["columns"][$keyCol];
        $aVal = array();
        foreach ($column["from"]["columns"] as $sCol) {
            $sColVal = current($sCol);
            $sColKey = key($sCol);
            $column2 = $sCol;
            /* Double jointure  */
            if (isset($column2["from"]) && $column2["from"]) {
                $table = "`" . $column2["from"]["table"] . "` `" . $column2["from"]["table"] . "_$keyCol`";
                /* On construit le select  */
                $aVal = array();
                foreach ($column2["from"]["columns"] as $sCol) {
                    $sColVal = current($sCol);
                    $sColKey = key($sCol);
                    $column3 = $sCol;
                    /* TRIPLE jointure  */
                    if (isset($column3["from"]) && $column3["from"]) {
                        $table .= " INNER JOIN `" . $column3["from"]["table"] . "` `" . $column3["from"]["table"] . "_$keyCol`";
                        $filterJoin = array();
                        foreach ($column3["from"]["index"] as $sColIndex => $sColIndexVal) {
                            if ($sColIndexVal == "THIS") {
                                $sVal = "`" . $column2["from"]["table"] . "_$keyCol`.`" . $column3["name"] . "`";
                            } else {
                                if (!is_array($sColIndexVal)) {
                                    $sColIndexVal = array($sColIndexVal);
                                }

                                foreach ($sColIndexVal as $ii => $v) {
                                    $sColIndexVal[$ii] = $this->db->quote($v);
                                }

                                $sVal = implode(",", $sColIndexVal);
                            }
                            $filterJoin[] = "`" . $column3["from"]["table"] . "_$keyCol`.`" . $sColIndex . "`"
                                    . " IN (" . $sVal . ")";
                        }

                        $table .= " ON " . implode(" AND ", $filterJoin);

                        foreach ($column3["from"]["columns"] as $sCol) {
                            $sColVal2 = current($sCol);
                            $sColKey2 = key($sCol);
                            if ($sColKey2 === "name") {
                                $sColVal3 = next($sCol);
                                $sColKey3 = key($sCol);
                                if ($sColKey3 == "sql") {
                                    continue;
                                } else
                                    $aVal[] = "`" . $column3["from"]["table"] . "_$keyCol`.`$sColVal2`";
                            } else {
                                $aVal[] = $this->db->quote($sColVal2);
                            }
                        }
                    } else if ($sColKey === "name") {
                        $sColVal2 = next($sCol);
                        $sColKey2 = key($sCol);
                        if ($sColKey2 == "sql") {
                            continue;
                        } else
                            $aVal[] = "`" . $column2["from"]["table"] . "_$keyCol`.`$sColVal`";
                    } else {
                        $aVal[] = $this->db->quote($sColVal);
                    }
                }

                foreach ($column2["from"]["index"] as $sColIndex => $sColIndexVal) {
                    if ($sColIndexVal == "THIS") {
                        $selectSqlArray[] = "`" . $column2["from"]["table"] . "_$keyCol`." . $sColIndex . " id";
                    }
                }
                /* FIN Double jointure  */
            } else {
                if ($sColKey == "name") {
                    $sColVal2 = next($sCol);
                    $sColKey2 = key($sCol);
                    if ($sColKey2 == "sql") {
                        $aVal[] = "$sColVal2";
                    } else
                        $aVal[] = "`" . $column["from"]["table"] . "`.`$sColVal`";
                } else {
                    $aVal[] = $this->db->quote($sColVal);
                }
            }
        }


        $term = $_REQUEST["term"];

        $label = "CONCAT(" . implode(",", $aVal) . ")";
        $selectSqlArray[] = "$label label ";



        $response = array();
        $response = $this->db->query('
            SELECT ' . implode(",", $selectSqlArray) . '
            FROM ' . $table . '
            WHERE ' . $label . ' LIKE "%' . $term . '%";')->fetchAll(\PDO::FETCH_ASSOC);

        $this->response = json_encode($response);
    }

    // --------------------------------------------------------------------

    /**
     * Action qui va être appelée pour éditer une donnée
     *
     * @return 	void
     */
    public function editableAction() {

        $columnModified = "";

        $filter = explode('|', $_POST["row_id"]);
        $i = 0;
        $j = 0;
        $where = array();
        foreach ($this->config["columns"] as $column) {
            if (isset($column["index"]) && $column["index"]) {
                $where[] = $column["name"] . " = " . $this->db->quote($filter[$i]);
                if ($column["name"] == "cle")
                    $where[] = $column["name"] . " LIKE BINARY " . $this->db->quote($filter[$i]);
                $i++;
            }
            if (isset($column["show"]) && $column["show"]) {
                if ($j == intval($_POST["column"]))
                    $columnModified = $column["name"];
                $j++;
            }
        }




        /* DB table to use */
        $sTable = $this->config["table"]["name"];



        $value = $this->db->quote($_POST["value"]);


        $query = "
            UPDATE $sTable SET `$columnModified` = $value WHERE " . implode(" AND ", $where) . ";
        ";

        $update = $this->db->prepare($query);
        $update->execute();

        $r = $update->rowCount();

        if ($r)
            exit($_POST["value"]);
    }

    // --------------------------------------------------------------------

    /**
     * Action qui va être appelée pour récupérer le sous forme de JSON les
     * données à charger dans le datatable.
     *
     * @return 	void
     */
    public function jsonAction() {
        /* = Tableau renvoyé.
          `------------------------------------------------------ */
        $output = array();

        /* = Array of database columns which should be read and sent back to DataTables. Use a space where
          |    you want to insert a non-database field (for example a counter or static image)
          `------------------------------------------------------ */
        $sIndexColumn = array();
        $sIndexColumnRaw = array();
        $sFilterColumn = array();
        $aColumns = array();
        $aColumnsFull = array();
        $aColumnsRaw = array();
        $aColumnsDetails = array();
        $aColumnsRawAll = array();
        $aColumnsAdvanced = array();
        $aColumnsContent = array();
        $aColumnsFunctions = array();
        $aColumnsTag = array();
        $aColumnsSelect = array();
        $sTableJoin = "";

        $realIndexes = array();
        $aColumnsBottom = array();
        $sSearchableColumn = array();

        /* = table de la BDD utilisée.
          `---------------------------------------------------------------------- */
        $sTable = $this->config["table"]["name"];
        $sGroupBy = isset($this->config["table"]["groupby"]) ? $this->config["table"]["groupby"] : false;
        $sHaving = isset($this->config["table"]["having"]) ? $this->config["table"]["having"] : false;

        $realIndex = 0;

        /* = Si la première colonne est un '+' pour ouvrir le détail.
          `---------------------------------------------------------------------- */
        if (isset($this->config["table"]["detail"]) && $this->config["table"]["detail"]) {
            $aColumnsAdvanced[] = NULL;
            $aColumnsFull[] = NULL;
            $realIndex++;
        }

        /* = Traitement des definition de columns
          `---------------------------------------------------------------------- */
        foreach ($this->config["columns"] as $keyCol => $column) {
            /* = Lien entre la clé et l'index du tableau réélle.
              `---------------------------------------------------------------------- */
            if (isset($column["show"]) && $column["show"]
//                || isset($column["show_detail"]) && $column["show_detail"]
            ) {
                $realIndexes[$keyCol] = $realIndex;
//                $realIndexes2[$column['title'] . "|" . $column['name']] = $realIndex;
//                $aColumnsAdvanced[] = $columnAdvancedName;
//                $aColumnsFull[] = $column;

                if (!isset($column["searchable"]) || $column["searchable"])
                    $sSearchableColumn[$realIndex] = TRUE;

                $realIndex++;
            }

            $aColumnsFunctions[$keyCol] = false;

            /* Gestion des formatage */
            if (isset($column["format"])) {
                foreach ($column["format"] as $type => $params) {
                    $paramsFunc = array();
                    $aColumnsFunctions[$keyCol][]["name"] = "\Solire\Lib\Format\\" . ($type);
                    $keyFunc = count($aColumnsFunctions[$keyCol]) - 1;
                    switch (strtolower($type)) {
                        case "datetime":
                            switch ($params["type"]) {
                                case "short":
                                    $aColumnsFunctions[$keyCol][$keyFunc]["name"] .= "::toShortText";
                                    $paramsFunc = array(
                                    );
                                    break;

                                default:
                                    $aColumnsFunctions[$keyCol][$keyFunc]["name"] .= "::" . $params["type"];
                                    unset($params["type"]);
                                    $paramsFunc = $params;
                                    break;
                            }

                            break;
                        case "number":
                            switch ($params["type"]) {
                                case "money":
                                    $aColumnsFunctions[$keyCol][$keyFunc]["name"] .= "::formatMoney";
                                    $paramsFunc = array(
                                        true,
                                        (isset($params["currency"]) ? $params["currency"] : ""),
                                    );
                                    break;

                                default:
                                    $aColumnsFunctions[$keyCol][$keyFunc]["name"] .= "::" . $params["type"];
                                    unset($params["type"]);
                                    $paramsFunc = $params;
                                    break;
                            }

                            break;

                        case "string":


                            break;


                        default:
                            break;
                    }

                    unset($params["type"]);

                    $aColumnsFunctions[$keyCol][$keyFunc]["params"] = $paramsFunc;
                }
            }

            /* Fin Gestion des formatage */



            if (isset($column["php_function"])) {
                $aColumnsFunctions[$keyCol] = isset($column["php_function"]) ? $column["php_function"] : false;
            }
            if (isset($column["special"])) {
                $aColumnsRaw[$keyCol] = $column["special"];
                $aColumnsFunctions[$keyCol] = $column["special"];
                if (!isset($column["name"]))
                    continue;
            }

            /* = Contenu statique
              `---------------------------------------------------------------------- */
            if (isset($column["content"]) && !isset($column["name"])) {
                $columnRawName = "content_$keyCol";
                $columnAdvancedName = $this->db->quote($column["content"]);
                $column["name"] = $columnAdvancedName . " `content_$keyCol`";
                $columnSelect = $column["name"];
            }
            /* Jointure sur autre table
              `---------------------------------------------------------------------- */ elseif (isset($column["from"]) && $column["from"]) {
                $aFilterJoin = array();
                $aFilterJoin3 = array();

                /* On construit le filtre pour la jointure */
                foreach ($column["from"]["index"] as $sColIndex => $sColIndexVal) {
                    $sTableJoin2 = "";
                    $sTableJoin3 = "";

                    if ($sColIndexVal == "THIS") {
                        $sVal = "`" . $sTable . "`.`" . $column["name"] . "`";
                    } else {
                        if (!is_array($sColIndexVal)) {
                            $sColIndexVal = array($sColIndexVal);
                        }

                        foreach ($sColIndexVal as $ii => $v) {
                            if (substr($v, 0, 1) == "`") {
                                $sColIndexVal[$ii] = $v;
                            } else {
                                $sColIndexVal[$ii] = $this->db->quote($v);
                            }
                        }

                        $sVal = implode(",", $sColIndexVal);
                    }

                    $aFilterJoin[] = "`" . $column["from"]["table"] . "_$keyCol`.`" . $sColIndex . "`"
                            . " IN (" . $sVal . ")";
                    $aFilterJoin3[] = "`" . $column["from"]["table"] . "_${keyCol}_2`.`" . $sColIndex . "`"
                            . " IN (" . $sVal . ")";
                }


                $aVal = array();
                foreach ($column["from"]["columns"] as $sCol) {
                    $sColVal = current($sCol);
                    $sColKey = key($sCol);
                    $column2 = $sCol;
                    /* Double jointure  */
                    if (isset($column2["from"]) && $column2["from"]) {
                        $aFilterJoin2 = array();
                        $aFilterJoin4 = array();

                        /* On construit le filtre pour la jointure */
                        foreach ($column2["from"]["index"] as $sColIndex => $sColIndexVal) {
                            if ($sColIndexVal == "THIS") {
                                $sVal = "`" . $column["from"]["table"] . "_$keyCol`.`" . $column2["name"] . "`";
                                $sVal3 = "`" . $column["from"]["table"] . "_${keyCol}_2`.`" . $column2["name"] . "`";
                            } else {
                                if (!is_array($sColIndexVal)) {
                                    $sColIndexVal = array($sColIndexVal);
                                }

                                foreach ($sColIndexVal as $ii => $v) {
                                    if (substr($v, 0, 1) == "`") {
                                        $sColIndexVal[$ii] = $v;
                                    } else {
                                        $sColIndexVal[$ii] = $this->db->quote($v);
                                    }
                                }

                                $sVal = implode(",", $sColIndexVal);
                                $sVal3 = implode(",", $sColIndexVal);
                            }

                            $aFilterJoin2[] = "`" . $column2["from"]["table"] . "_$keyCol`.`" . $sColIndex . "`"
                                    . " IN (" . $sVal . ")";
                            $aFilterJoin4[] = "`" . $column2["from"]["table"] . "_${keyCol}_2`.`" . $sColIndex . "`"
                                    . " IN (" . $sVal3 . ")";
                        }

                        /* On construit le select  */
                        $aVal = array();
                        $aVal3 = array();
                        $sTableJoin2 = "";
                        $sTableJoin3 = "";
                        $sTableJoin4 = "";
                        $sTableJoin5 = "";
                        foreach ($column2["from"]["columns"] as $sCol) {

                            $sColVal = current($sCol);
                            $sColKey = key($sCol);
                            $column3 = $sCol;
                            /* TRIPLE jointure  */
                            if (isset($column3["from"]) && $column3["from"]) {
                                $aFilterJoin6 = array();
                                $aFilterJoin7 = array();

                                /* On construit le filtre pour la jointure */
                                foreach ($column3["from"]["index"] as $sColIndex => $sColIndexVal) {
                                    if ($sColIndexVal == "THIS") {
                                        $sVal = "`" . $column2["from"]["table"] . "_$keyCol`.`" . $column3["name"] . "`";
                                        $sVal3 = "`" . $column2["from"]["table"] . "_${keyCol}_2`.`" . $column3["name"] . "`";
                                    } else {
                                        if (!is_array($sColIndexVal)) {
                                            $sColIndexVal = array($sColIndexVal);
                                        }

                                        foreach ($sColIndexVal as $ii => $v) {
                                            if (substr($v, 0, 1) == "`") {
                                                $sColIndexVal[$ii] = $v;
                                            } else {
                                                $sColIndexVal[$ii] = $this->db->quote($v);
                                            }
                                        }

                                        $sVal = implode(",", $sColIndexVal);
                                        $sVal3 = implode(",", $sColIndexVal);
                                    }

                                    $aFilterJoin6[] = "`" . $column3["from"]["table"] . "_$keyCol`.`" . $sColIndex . "`"
                                            . " IN (" . $sVal . ")";
                                    $aFilterJoin7[] = "`" . $column3["from"]["table"] . "_${keyCol}_2`.`" . $sColIndex . "`"
                                            . " IN (" . $sVal3 . ")";
                                }

                                /* On construit le select  */
//                                $aVal = array();
                                $aVal5 = array();
                                foreach ($column3["from"]["columns"] as $sCol) {




                                    $sColVal = current($sCol);
                                    $sColKey = key($sCol);

                                    if ($sColKey === "name") {
                                        $sColVal2 = next($sCol);
                                        $sColKey2 = key($sCol);
                                        if ($sColKey2 == "sql") {
                                            $aVal5[] = "$sColVal2";
                                        } else {
//                                            $aVal[] = "`" . $column3["from"]["table"] . "_$keyCol`.`$sColVal`";
                                            $aVal5[] = "`" . $column3["from"]["table"] . "_${keyCol}`.`$sColVal`";
                                            $aVal3[] = "`" . $column3["from"]["table"] . "_${keyCol}_2`.`$sColVal`";
                                        }
                                    } else {
                                        $aVal5[] = $this->db->quote($sColVal);
                                        $aVal3[] = $this->db->quote($sColVal);
                                    }
                                }


                                $column2RawName = $column2["name"];
                                for ($index = 1; $index < 10; $index++) {

                                    if (in_array($column2RawName, $aColumnsRawAll) === false)
                                        break;

                                    $column2RawName = $column3["name"] . "_" . $index;
                                }

                                $separator = isset($column3["from"]["separator"]) ? $column3["from"]["separator"] : '';

                                /* Si on ne veut pas de group concat */
//                                if (isset($column3["from"]["group_concat"]) && !$column3["from"]["group_concat"]) {
                                $column4AdvancedName = "CONCAT(" . implode(",", $aVal5) . ")";
                                $column3AdvancedName = "CONCAT(" . implode(",", $aVal5) . ")";
//                                } else {
//                                    $column4AdvancedName = "GROUP_CONCAT(DISTINCT " . implode(",", $aVal) . "  SEPARATOR '$separator')";
////                                    $column3AdvancedName = "CONCAT(" . implode(",", $aVal3) . ")";
//                                }



                                $column3["name"] = $column4AdvancedName . " `" . $column2RawName . "`";

                                $column2Select = $column3["name"];

                                /* Type de jointure (LEFT/RIGHT/INNER) */
                                $joinType = isset($column3["from"]["type"]) ? $column3["from"]["type"] : "INNER";
                                /* Si on a une clause groupby de definit pour la jointure */
                                if (isset($column3["from"]["groupby"])) {
                                    $sTableJoin4 .= " $joinType JOIN (SELECT *, $column2Select FROM `" . $column3["from"]["table"] . "` GROUP BY " . $column3["from"]["groupby"] . "";
                                    $column2Select = $column2RawName;
                                    if (isset($column3["from"]["having"])) {
                                        $sTableJoin4 .= " HAVING " . $column3["from"]["having"];
                                    }

                                    $sTableJoin4 .= ") `" . $column3["from"]["table"] . "_$keyCol`  ON " . implode(" AND ", $aFilterJoin6);
                                } else {
                                    $sTableJoin4 .= " $joinType JOIN `" . $column3["from"]["table"] . "` `" . $column3["from"]["table"] . "_$keyCol`  ON " . implode(" AND ", $aFilterJoin6);
                                    $sTableJoin5 .= " $joinType JOIN `" . $column3["from"]["table"] . "` `" . $column3["from"]["table"] . "_${keyCol}_2`  ON " . implode(" AND ", $aFilterJoin7);
//                                    exit($sTableJoin2);
                                }

                                //Si on a un group concat, on rejoint une fois la table
                                if (!isset($column3["from"]["group_concat"]) || $column3["from"]["group_concat"]) {
                                    $column2AdvancedName = $column3AdvancedName;
                                }
                                $aVal[] = $column4AdvancedName;

                                /* FIN TRIPLE jointure  */
                            } else {
                                $sColVal = current($sCol);
                                $sColKey = key($sCol);

                                if ($sColKey === "name") {
                                    $sColVal2 = next($sCol);
                                    $sColKey2 = key($sCol);
                                    if ($sColKey2 == "sql") {
                                        $aVal[] = "$sColVal2";
                                    } else {
                                        $aVal[] = "`" . $column2["from"]["table"] . "_$keyCol`.`$sColVal`";
                                        $aVal3[] = "`" . $column2["from"]["table"] . "_${keyCol}_2`.`$sColVal`";
                                    }
                                } else {
                                    $aVal[] = $this->db->quote($sColVal);
                                    $aVal3[] = $this->db->quote($sColVal);
                                }
                            }
                        }


                        $column2RawName = $column2["name"];
                        for ($index = 1; $index < 10; $index++) {

                            if (in_array($column2RawName, $aColumnsRawAll) === false)
                                break;

                            $column2RawName = $column2["name"] . "_" . $index;
                        }

                        $separator = isset($column2["from"]["separator"]) ? $column2["from"]["separator"] : '';

                        /* Si on ne veut pas de group concat */
                        if (isset($column2["from"]["group_concat"]) && !$column2["from"]["group_concat"]) {
                            $column2AdvancedName = "CONCAT(" . implode(",", $aVal) . ")";
                        } else {
                            $column2AdvancedName = "GROUP_CONCAT(DISTINCT " . implode(",", $aVal) . "  SEPARATOR '$separator')";
                            $column3AdvancedName = "CONCAT(" . implode(",", $aVal3) . ")";
                        }



                        $column2["name"] = $column2AdvancedName . " `" . $column2RawName . "`";

                        $column2Select = $column2["name"];

                        /* Type de jointure (LEFT/RIGHT/INNER) */
                        $joinType = isset($column2["from"]["type"]) ? $column2["from"]["type"] : "INNER";

                        /* Si on a une clause groupby de definit pour la jointure */
                        if (isset($column2["from"]["groupby"])) {
                            $sTableJoin2 .= " $joinType JOIN (SELECT *, $column2Select FROM `" . $column2["from"]["table"] . "` GROUP BY " . $column2["from"]["groupby"] . "";
                            $column2Select = $column2RawName;
                            if (isset($column2["from"]["having"])) {
                                $sTableJoin2 .= " HAVING " . $column2["from"]["having"];
                            }

                            $sTableJoin2 .= ") `" . $column2["from"]["table"] . "_$keyCol`  ON " . implode(" AND ", $aFilterJoin2);
                        } else {
                            $sTableJoin2 .= " $joinType JOIN `" . $column2["from"]["table"] . "` `" . $column2["from"]["table"] . "_$keyCol`  ON " . implode(" AND ", $aFilterJoin2);
                            $sTableJoin3 .= " $joinType JOIN `" . $column2["from"]["table"] . "` `" . $column2["from"]["table"] . "_${keyCol}_2`  ON " . implode(" AND ", $aFilterJoin4);
                        }

                        //Si on a un group concat, on rejoint une fois la table
                        if (!isset($column2["from"]["group_concat"]) || $column2["from"]["group_concat"]) {
                            $column2AdvancedName = $column3AdvancedName;
                        }

                        /* FIN Double jointure  */
                    } else {
                        if ($sColKey === "name") {
                            $sColVal2 = next($sCol);
                            $sColKey2 = key($sCol);
                            if ($sColKey2 === "sql") {
                                $aVal[] = "$sColVal2";
                            } else
                                $aVal[] = "`" . $column["from"]["table"] . "_$keyCol`.`$sColVal`";
                        } else {
                            $aVal[] = $this->db->quote($sColVal);
                        }
                    }
                }


                $columnRawName = $column["name"];
                for ($index = 1; $index < 10; $index++) {

                    if (in_array($columnRawName, $aColumnsRawAll) === false)
                        break;

                    $columnRawName = $column["name"] . "_" . $index;
                }

                if (isset($column["concat"]) && $column["concat"] == false) {
                    $columnAdvancedName = "" . implode(",", $aVal) . "";
                } else {
                    $columnAdvancedName = "CONCAT(" . implode(",", $aVal) . ")";
                }


                $column["name"] = $columnAdvancedName . " `" . $columnRawName . "`";

                $columnSelect = $column["name"];

                /* Type de jointure (LEFT/RIGHT/INNER) */
                $joinType = isset($column["from"]["type"]) ? $column["from"]["type"] : "INNER";

                /* Si on a une clause groupby de definit pour la jointure */
                if (isset($column["from"]["groupby"])) {
                    $sTableJoin .= " $joinType JOIN (SELECT *, $columnSelect FROM `" . $column["from"]["table"] . "` `" . $column["from"]["table"] . "_$keyCol` GROUP BY " . $column["from"]["groupby"] . "";
                    $columnSelect = $columnRawName;
                    if (isset($column["from"]["having"])) {
                        $sTableJoin .= " HAVING " . $column["from"]["having"];
                    }

                    $sTableJoin .= ") `" . $column["from"]["table"] . "_$keyCol`  ON " . implode(" AND ", $aFilterJoin);
                } else {
                    $sTableJoin .= " $joinType JOIN `" . $column["from"]["table"] . "` `" . $column["from"]["table"] . "_$keyCol`  ON " . implode(" AND ", $aFilterJoin);
                    $sTableJoin .= " $joinType JOIN `" . $column["from"]["table"] . "` `" . $column["from"]["table"] . "_${keyCol}_2`  ON " . implode(" AND ", $aFilterJoin3);
                }
                /* Double jointure */
                if (isset($column2AdvancedName)) {
                    $columnAdvancedName = $column2AdvancedName;
                    $column["name"] = $column2["name"];
                    $columnRawName = $column2RawName;
                    $columnSelect = $column2Select;
                    $sTableJoin .= " " . $sTableJoin2;
                    $sTableJoin .= " " . $sTableJoin3;
                    $sTableJoin .= " " . $sTableJoin4;
                    $sTableJoin .= " " . $sTableJoin5;
                }
            }
            /* = Cas par défaut : pas de jointure et pas de contenu statique.
              `---------------------------------------------------------------------- */ else {
                $columnRawName = $column["name"];
                for ($index = 1; $index < 10; $index++) {

                    if (in_array($columnRawName, $aColumnsRawAll) === false)
                        break;

                    $columnRawName = $column["name"] . "_" . $index;
                }

                if (isset($column["sql"])) {
                    /* = Si la colone est du sql avec des fonctions
                      `---------------------------------------------------------------------- */
                    $columnAdvancedName = $column["sql"];
                    $column["name"] = $columnAdvancedName;
                } else {
                    $columnAdvancedName = "`" . $sTable . "`.`" . $column["name"] . "`";
                    $column["name"] = "`" . $sTable . "`.`" . $column["name"] . "`";
                }

                $columnSelect = $column["name"] . " AS " . $columnRawName;
            }

//            if (isset($column["show"]) && $column["show"]) {
//                $aColumnsAdvanced[] = $columnAdvancedName;
//                $aColumnsFull[] = $column;
//            }

            if (!isset($column["searchable"]) || $column["searchable"]) {
                $aColumnsSearchable[] = $columnAdvancedName;
            }

            if (isset($column["show_detail"]) && $column["show_detail"]) {
                $aColumnsDetails[$keyCol] = true;
            }

            if (isset($column["show"]) && $column["show"]
                    || isset($column["show_detail"]) && $column["show_detail"]
            ) {
                $aColumns[$keyCol] = $column["name"];

                $aColumnsRaw[$keyCol] = $columnRawName;
                $aColumnsFull[] = $column;
                $aColumnsAdvanced[] = $columnAdvancedName;
                $aColumnsContent[$keyCol] = isset($column["content"]) && isset($column["name"]) ? $column["content"] : false;
                if (!$aColumnsFunctions[$keyCol])
                    $aColumnsFunctions[$keyCol] = isset($column["nl2br"]) ? array("nl2br") : false;
            }

            if (isset($column["name"])) {
                $aColumnsRawAll[$keyCol] = $columnRawName;
                $aColumnsSelect[$keyCol] = $columnSelect;
                $aColumnsTag[$keyCol] = "[#$columnRawName#]";
            }

            if (isset($column["index"]) && $column["index"]) {
                $sIndexColumnRaw[] = $columnRawName;
                $sIndexColumn[] = $column["name"] . " $columnRawName";
            }

            if (isset($column["filter"]))
                $sFilterColumn[$keyCol] = $column["name"] . ' = ' . $this->db->quote($column["filter"]);

            if (isset($column["bottom"]) && $column["bottom"])
                $aColumnsBottom[$keyCol] = $column["bottom"];
        }

        /* = Si on a des filtres addionnal (exemple en param de lurl)
          `-------------------------------------------------------- */
        $sFilterColumn = array_merge($this->aFilterColumnAdditional, $sFilterColumn);


        /* = Construction du "LIMIT" de la requête.
          `-------------------------------------------------------- */
        $sLimit = "";
        if (isset($_POST['iDisplayStart']) && $_POST['iDisplayLength'] != '-1') {
            $sLimit = "LIMIT " . intval($_POST['iDisplayStart']) . ", " .
                    intval($_POST['iDisplayLength']);
        }

        /* = Construction du "ORDER BY" de la requête.
          `-------------------------------------------------------- */
        $sOrder = array();

        if (isset($_POST['iSortCol_0'])) {
            for ($i = 0; $i < intval($_POST['iSortingCols']); $i++) {
                if ($_POST['bSortable_' . intval($_POST['iSortCol_' . $i])] == "true") {
                    $indexColumn = intval($_POST['iSortCol_' . $i]);
//                    $sOrder[]      .= "" . $aColumnsAdvanced[$indexColumn] . " " . $_POST['sSortDir_' . $i];

                    $keyCol = array_search($indexColumn, $realIndexes);
                    $sOrder[] .= "" . $aColumnsRawAll[$keyCol] . " " . $_POST['sSortDir_' . $i];
                }
            }
        }
        if (count($sOrder) > 0)
            $sOrder = "ORDER BY " . implode(",", $sOrder);
        else
            $sOrder = "";

        /* = Filtre sur toutes les colonnes individuelles.
          `-------------------------------------------------------- */
        if (isset($_POST['sSearch']) && $_POST['sSearch'] != "") {
            $sWhere = array(); //"WHERE (";
            $search = $this->db->quote('%' . $_POST['sSearch'] . '%');
        }
        else
            $search = FALSE;

        $sWhere2 = "";
        foreach ($realIndexes as $indexColumn => $realIndex) {
            /* = Filtre sur toutes les colonnes individuelles.
              `-------------------------------------------------------- */
            if ($search
                    && $aColumnsAdvanced[$realIndex]
                    && isset($sSearchableColumn[$realIndex])
            ) {
                $sWhere[] = $aColumnsAdvanced[$realIndex] . " LIKE $search";
            }

            /* = Filtre sur les colonnes individuelles
              `-------------------------------------------------------- */
            if (isset($_POST['bSearchable_' . $realIndex])
                    && $_POST['bSearchable_' . $realIndex] == "true"
                    && $_POST['sSearch_' . $realIndex] != ''
                    && $_POST['sSearch_' . $realIndex] != '~'
            ) {
                /* = Filtre sur les dates (date-range)
                  `-------------------------------------------------------- */
                if (isset($aColumnsFull[$realIndex]["filter_field"]) && $aColumnsFull[$realIndex]["filter_field"] == "date-range") {
                    $dateRange = explode("~", $_POST['sSearch_' . $realIndex]);
                    $dateRange[0] = \Solire\Lib\Format\DateTime::frToSql($dateRange[0]);
                    $sWhere2 .= ($sWhere2 != '' ? " AND " : " " ) . $aColumnsAdvanced[$realIndex] . " >= " . $this->db->quote('' . $dateRange[0] . ' 00:00:00') . "";
                    if ($dateRange[1] != "") {
                        $dateRange[1] = \Solire\Lib\Format\DateTime::frToSql($dateRange[1]);
                        $sWhere2 .= " AND " . $aColumnsAdvanced[$realIndex] . " <= " . $this->db->quote('' . $dateRange[1] . ' 23:59:59') . "";
                    }
                }
                /* = Autres Filtres
                  `-------------------------------------------------------- */ else {
                    $sWhere2 .= ($sWhere2 != '' ? " AND " : " " ) . $aColumnsAdvanced[$realIndex] . " LIKE " . $this->db->quote('%' . $_POST['sSearch_' . $realIndex] . '%') . "";
                }
            }
        }

        if ($search)
            $sWhere = " WHERE"
                    . ($sWhere ? " (" . implode(" OR ", $sWhere) . ")" : " 1")
                    . ($sWhere2 ? " AND $sWhere2" : "");
        elseif ($sWhere2)
            $sWhere = " WHERE " . $sWhere2;

        if (isset($this->config['where']) && count($this->config['where']))
            $sFilterColumn[] .= implode(" AND ", $this->config['where']);

        if ($this->additionalWhereQuery != "")
            $sFilterColumn[] = $this->additionalWhereQuery;

        if (!isset($sWhere))
            $sWhere = "";

        $generalWhere = "";
        if (count($sFilterColumn) > 0) {
            if ($sWhere == "")
                $generalWhere = "WHERE ";
            else {
                $generalWhere = " AND ";
            }
            $generalWhere .= implode(" AND ", $sFilterColumn);
        }

        $sColumnsSelect = array_unique(array_merge($aColumnsSelect, $sIndexColumn));



        $bottomsQuery = array();
        $bottomsValue = array();
        foreach ($aColumnsBottom as $keyCol => $value) {
            $sQuery = "SELECT SQL_CALC_FOUND_ROWS $value(" . $aColumns[$keyCol] . ")"
                    . " FROM $sTable"
                    . " $sTableJoin"
                    . " $sWhere"
                    . " $generalWhere";
//            exit("$sWhere | $generalWhere | $sQuery");
            $bottomsValue[$realIndexes[$keyCol]] = $this->db->query($sQuery)->fetch(\PDO::FETCH_COLUMN);
            $bottomsQuery[$realIndexes[$keyCol]] = $sQuery;
        }


        /* = Requête SQL récupérant les données à afficher.
          `-------------------------------------------------------- */
        $sQuery = "SELECT SQL_CALC_FOUND_ROWS " . str_replace(" , ", " ", implode(", ", $sColumnsSelect))
                . " FROM $sTable"
                . " $sTableJoin"
                . " $sWhere"
                . " $generalWhere"
                . ($sGroupBy !== false ? " GROUP BY " . $sGroupBy . ($sHaving !== false ? " HAVING " . $sHaving : "" ) : "" )
                . " $sOrder"
                . " $sLimit";
        /** Pour debug * */
//        $this->debugQuery($sQuery);
        $rResult = $this->db->query($sQuery);

        /* = Data set length after filtering.
          `-------------------------------------------------------- */
        $sQuery2 = "SELECT FOUND_ROWS()";
        $rResultFilterTotal = $this->db->query($sQuery2);
        $aResultFilterTotal = $rResultFilterTotal->fetch(\PDO::FETCH_NUM);
        $iFilteredTotal = $aResultFilterTotal[0];

        /* = Total data set length.
          `-------------------------------------------------------- */
        $sQuery2 = "SELECT COUNT(*)"
                . " FROM   $sTable "
                . $this->additionalJoinQueryCount
                . (substr($generalWhere, 0, 5) == "WHERE" ? " " : " WHERE 1 " )
                . " $generalWhere";

        $rResultTotal = $this->db->query($sQuery2);
        $aResultTotal = $rResultTotal->fetch(\PDO::FETCH_NUM);
        $iTotal = $aResultTotal[0];



        /* = On remplit le tableau renvoyé.
          `-------------------------------------------------------- */
        $output['sEcho'] = intval($_POST['sEcho']);
        $output['iTotalRecords'] = $iTotal;
        $output['iTotalDisplayRecords'] = $iFilteredTotal;
        $output['aaData'] = array();
        $output['bottomsValue'] = $bottomsValue;

        /* = Différents debug.
          `-------------------------------------------------------- */
        $output['query'] = $sQuery;
        $output['bottomsQuery'] = $bottomsQuery;
        $output['realIndexes'] = $realIndexes;
        $output['aColumnsAdvanced'] = $aColumnsAdvanced;
//        $output['aColumnsFull']         = $aColumnsFull;

        //Nom du tableau en session contenant les id des lignes selectionnées
        $selectArrayName = "tableau-" . $_GET["table"] . "_select";


        while ($aRow = $rResult->fetch(\PDO::FETCH_ASSOC)) {
            $row = array();
            $row2 = array();

            $row["DT_RowId"] = "";
            if (isset($this->config["table"]["detail"]) && $this->config["table"]["detail"]) {
                $row[] = '';
                $row2["detail"] = '';
            }

            foreach ($aColumnsRaw as $aColRawKey => $aColRaw) {
                if ($aColumnsRaw[$aColRawKey] != ' ') {
                    if ($aColumnsFunctions[$aColRawKey] !== false && is_array($aColumnsFunctions[$aColRawKey]) === false) {
                        $row[] = $this->$aColumnsFunctions[$aColRawKey]($aRow);
                    } else {

                        if ($aColumnsContent[$aColRawKey] !== false) {
                            $searchTag = array_merge($aColumnsTag, array("[#THIS#]"));
                            $replaceTag = array_merge($aRow, array($aRow[$aColumnsRaw[$aColRawKey]]));
                            $row[] = $aRow[$aColumnsRaw[$aColRawKey]] = str_replace($searchTag, $replaceTag, $aColumnsContent[$aColRawKey]);
                        } else {
                            if (isset($this->config["extra"]["highlightedSearch"]) && $this->config["extra"]["highlightedSearch"] && $_POST["sSearch"] != "" && $aColumnsFunctions[$aColRawKey] === false) {
                                $_POST["sSearch"] = trim($_POST["sSearch"]);
                                $words = strpos($_POST["sSearch"], " ") !== false ? explode(" ", $_POST["sSearch"]) : array($_POST["sSearch"]);
                                $row[] = Tools::highlightedSearch($aRow[$aColumnsRaw[$aColRawKey]], $words);
                            } else {
                                $row[] = $aRow[$aColumnsRaw[$aColRawKey]];
                            }
                        }

                        if ($aColumnsFunctions[$aColRawKey] !== false) {
                            foreach ($aColumnsFunctions[$aColRawKey] as $function) {
                                $params = array(
                                    $row[count($row) - 2],
                                );
                                if (is_array($function)) {
                                    if (isset($function["params"]) && is_array($function["params"]) && count($function["params"]) > 0) {
                                        $params = array_merge($params, $function["params"]);
                                    }
                                    $functionName = $function["name"];
                                } else {
                                    $functionName = $function;
                                }
                                $row[count($row) - 2] = call_user_func_array($functionName, $params);
                                if ($functionName == "nl2br") {
                                    $row[count($row) - 2] = preg_replace("/(\r\n|\n|\r)/", "", $row[count($row) - 2]);
                                }
                            }
                        }
                    }
                    $row2[$aColumnsRaw[$aColRawKey]] = $row[count($row) - 2];
                }
            }

            if (isset($this->config["table"]["detail"]) && $this->config["table"]["detail"]) {
                $currentId = 1;
                foreach ($aColumnsRaw as $aColRawKey => $aColRaw) {

                    if ($aColumnsRaw[$aColRawKey] != ' ') {

                        if (isset($aColumnsDetails[$aColRawKey]) && $aColumnsDetails[$aColRawKey]) {
                            if ($row[$currentId] != "") {
                                $row[0] = '<a href="#" class="btn btn-default btn-small detail" title="Visualiser"><i class="icon-plus"></i></a>';
                                break;
                            }
                        }
                        $currentId++;
                    }
                }
            }



            for ($i = 0; $i < count($sIndexColumnRaw); $i++) {
                $row["DT_RowId"] .= $aRow[$sIndexColumnRaw[$i]] . "|";
            }
            $row["DT_RowId"] = substr($row["DT_RowId"], 0, -1);
            if (isset($this->config["extra"]["selectable"]) && $this->config["extra"]["selectable"]) {
                if(isset($SESSION[$selectArrayName])
                        && in_array($row["DT_RowId"], $SESSION[$selectArrayName])) {
                    $row[0] = str_replace('<input ', '<input checked="checked" ', $row[0]);
                }
            }


            if (isset($this->config["table"]["postDataProcessing"])) {
                $fnNamePostDataProcessing = $this->config["table"]["postDataProcessing"];
                $this->$fnNamePostDataProcessing($aRow, $row2, $row);
            }
            $output['aaData'][] = $row;
        }

        $this->view = "";
        $this->response = json_encode($output);
    }

    // --------------------------------------------------------------------

    /**
     * Renvoi soit la vue générée, soit la reponse JSON
     *
     * @return 	string
     */
    public function display()
    {
        if ($this->viewPathRelative) {
            $rc = new \ReflectionClass(get_class($this));
        } else {
            $rc = new \ReflectionClass(__CLASS__);
        }
        $view = $this->view;
        if ($this->view == "" && $this->response != "")
            return $this->response;
        else if ($this->view != "") {
            return $this->output(dirname($rc->getFileName()) . DIRECTORY_SEPARATOR . $this->viewPath . $view . ".phtml") . $this->pluginsOutput;
        } else
            return $this->pluginsOutput;
    }

    public function __toString()
    {
        return $this->display();
    }

    // --------------------------------------------------------------------

    /**
     * Génère la vue
     *
     * @param string $file chemin de la vue à inclure
     * @return string Rendu de la vue après traitement
     */
    public function output($file) {
        ob_start();
        include($file);
        $output = ob_get_clean();
        return $output;
    }

    // --------------------------------------------------------------------

    /**
     * Renvoi le chargeur de fichier javascript
     *
     * @return Javascript
     */
    public function getJavascriptLoader() {
        return $this->javascript;
    }

    // --------------------------------------------------------------------

    /**
     * Renvoi le chargeur de fichier Css
     *
     * @return Css
     */
    public function getCssLoader() {
        return $this->css;
    }

    // --------------------------------------------------------------------

    /**
     * Renvoi le HTML relatif à l'ajout d'un item
     *
     * @return string Html du formulaire
     */
    protected function addRender($view = "default", $path = null) {
        $rc = new \ReflectionClass(__CLASS__);
        if ($path == null) {
            return $this->output(dirname($rc->getFileName()) . DIRECTORY_SEPARATOR . $this->viewPath . "form/$view.phtml");
        } else {
            return $this->output($path . "$view.phtml");
        }
    }

    /**
     * Renvoi le HTML relatif à la modification d'un item
     *
     * @return string Html du formulaire
     */
    protected function editFormRenderAction() {
        if (isset($this->config["extra"])
                && isset($this->config["extra"]["editable"]) && $this->config["extra"]["editable"]) {
            $this->data = $this->getData($_GET["index"]);
            $this->modeEdit = true;
            $this->editRenderAction();
            if (isset($this->config["style"])
                    && isset($this->config["style"]["form"])) {
                $path = isset($this->config["style"]["formpath"]) ? $this->config["style"]["formpath"] : null;
                echo $this->addRender($this->config["style"]["form"], $path);
            } else {
                echo $this->addRender();
            }
            exit();
        }
    }

    // --------------------------------------------------------------------

    /**
     * Executer avant le rendu du formulaire d'ajout d'un item
     *
     * @return void
     */
    protected function addRenderAction() {

    }

    // --------------------------------------------------------------------

    /**
     * Executer avant le rendu du formulaire de modification d'un item
     *
     * @return void
     */
    protected function editRenderAction() {

    }

    // --------------------------------------------------------------------

    /**
     * Récupere les valeurs d'une entrée
     *
     * @return void
     */
    protected function getData($index) {

        $filter = explode('|', $index);
        $i = 0;
        $queryAfterData = array();
        $j = 0;
        $where = array();
        foreach ($this->config["columns"] as $column) {
            if (isset($column["creable_field"])) {
                if (isset($column["creable_field"]["type"]) && $column["creable_field"]["type"] == "multi-autocomplete") {

                    $aVal = array();
                    foreach ($column["from"]["columns"] as $sCol) {
                        $sColVal = current($sCol);
                        $sColKey = key($sCol);
                        $column2 = $sCol;
                        /* Double jointure  */
                        if (isset($column2["from"]) && $column2["from"]) {
                            $table = "`" . $column2["from"]["table"] . "` `" . $column2["from"]["table"] . "`";
                            /* On construit le select  */
                            $aVal = array();
                            foreach ($column2["from"]["columns"] as $sCol) {
                                $sColVal = current($sCol);
                                $sColKey = key($sCol);
                                $column3 = $sCol;
                                /* TRIPLE jointure  */
                                if (isset($column3["from"]) && $column3["from"]) {
                                    $table .= " INNER JOIN `" . $column3["from"]["table"] . "` `" . $column3["from"]["table"] . "`";
                                    $filterJoin = array();
                                    foreach ($column3["from"]["index"] as $sColIndex => $sColIndexVal) {
                                        if ($sColIndexVal == "THIS") {
                                            $sVal = "`" . $column2["from"]["table"] . "`.`" . $column3["name"] . "`";
                                        } else {
                                            if (!is_array($sColIndexVal)) {
                                                $sColIndexVal = array($sColIndexVal);
                                            }

                                            foreach ($sColIndexVal as $ii => $v) {
                                                $sColIndexVal[$ii] = $this->db->quote($v);
                                            }

                                            $sVal = implode(",", $sColIndexVal);
                                        }
                                        $filterJoin[] = "`" . $column3["from"]["table"] . "`.`" . $sColIndex . "`"
                                                . " IN (" . $sVal . ")";
                                    }

                                    $table .= " ON " . implode(" AND ", $filterJoin);

                                    foreach ($column3["from"]["columns"] as $sCol) {
                                        $sColVal2 = current($sCol);
                                        $sColKey2 = key($sCol);
                                        if ($sColKey2 === "name") {
                                            $sColVal3 = next($sCol);
                                            $sColKey3 = key($sCol);
                                            if ($sColKey3 == "sql") {
                                                continue;
                                            } else
                                                $aVal[] = "`" . $column3["from"]["table"] . "`.`$sColVal2`";
                                        } else {
                                            $aVal[] = $this->db->quote($sColVal2);
                                        }
                                    }
                                } else if ($sColKey === "name") {
                                    $sColVal2 = next($sCol);
                                    $sColKey2 = key($sCol);
                                    if ($sColKey2 == "sql") {
                                        continue;
                                    } else
                                        $aVal[] = "`" . $column2["from"]["table"] . "`.`$sColVal`";
                                } else {
                                    $aVal[] = $this->db->quote($sColVal);
                                }
                            }

                            foreach ($column2["from"]["index"] as $sColIndex => $sColIndexVal) {
                                if ($sColIndexVal == "THIS") {
                                    $selectSqlArray[] = "`" . $column2["from"]["table"] . "`." . $sColIndex . " id";
                                }
                            }
                            /* FIN Double jointure  */
                        } else {
                            if ($sColKey == "name") {
                                $sColVal2 = next($sCol);
                                $sColKey2 = key($sCol);
                                if ($sColKey2 == "sql") {
                                    $aVal[] = "$sColVal2";
                                } else
                                    $aVal[] = "`" . $column["from"]["table"] . "`.`$sColVal`";
                            } else {
                                $aVal[] = $this->db->quote($sColVal);
                            }
                        }
                    }

                    $label = "CONCAT(" . implode(",", $aVal) . ")";
                    $selectSqlArray[] = "$label label ";

                    $queryAfterData = array();
                    $queryAfterData["table"] = $table;
                    $queryAfterData["table"] .= "
                        INNER JOIN " . $column["from"]["table"] . "
                            ON " . $column["from"]["table"] . "." . $column["from"]["columns"][0]["name"] . " = `" . $column2["from"]["table"] . "`." . key($column["from"]["columns"][0]["from"]["index"]);

                    $queryAfterData["columns"] = $selectSqlArray;
                    $queryAfterData["columnref"] = $column["name"];
                    $queryAfterData["where"] = key($column["from"]["index"]) . " = " . $this->db->quote((current($column["from"]["index"]) == "THIS" ? $index : current($column["from"]["index"])));
                }
            }


            if (isset($column["index"]) && $column["index"]) {
                $where[] = $column["name"] . " = " . $this->db->quote($filter[$i]);
                if ($column["name"] == "cle")
                    $where[] = $column["name"] . " LIKE BINARY " . $this->db->quote($filter[$i]);
                $i++;
            }
        }



        /* DB table to use */
        $sTable = $this->config["table"]["name"];

        $query = "
            SELECT * FROM $sTable WHERE " . implode(" AND ", $where) . ";
        ";

        $data = $this->db->query($query)->fetch(\PDO::FETCH_ASSOC);

        if (count($queryAfterData) > 0) {
            $query = "
                SELECT " . implode(",", $queryAfterData["columns"]) . " FROM " . $queryAfterData["table"] . " WHERE " . $queryAfterData["where"] . ";
            ";

            $dataM = $this->db->query($query)->fetchAll(\PDO::FETCH_ASSOC);
            $data[$queryAfterData["columnref"]] = htmlentities(json_encode($dataM));
        }



        return $data;
    }

    /**
     * Récupere les valeurs d'une entrée
     *
     * @return void
     */
    protected function getDataFormat($index) {


        $sTable = $this->config["table"]["name"];
        $_POST["sEcho"] = 1;

        $filter = explode('|', $index);
        $i = 0;
        $j = 0;
        $where = array();
        foreach ($this->config["columns"] as $column) {
            if (isset($column["index"]) && $column["index"]) {
                $this->aFilterColumnAdditional[] = "`" . $sTable . "`." . $column["name"] . ' = ' . $this->db->quote($filter[$i]);
                $i++;
            }
        }

        $this->jsonAction();
        $response = json_decode($this->response, true);
        return $response["aaData"][0];
    }

    // --------------------------------------------------------------------

    /**
     * Renvoi l'url de la page
     *
     * @return string url complète
     */
    private function selfURL() {
        $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
        $protocol = self::strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/") . $s;
        $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":" . $_SERVER["SERVER_PORT"]);
        $requestUri = $_SERVER['REQUEST_URI'];

        $params = $this->convertUrlQuery(str_replace($_SERVER['REDIRECT_URL'] . "?", "", $requestUri));
        $paramsString = "";
        foreach ($params as $paramsKey => $param) {
            if ($paramsKey == "name[]" || $paramsKey == "dt_action")
                unset($params[$paramsKey]);
            else {
                $paramsString[] = "$paramsKey=$param";
            }
        }
        $requestUri = $_SERVER['REDIRECT_URL'] . "?";
        if (is_array($paramsString) && count($paramsString) > 0) {
            $requestUri .= implode("&", $paramsString);
        }

        $requestUri .= (count($params) == 0 ? "" : "&") . "name=" . $this->configName;

        return $protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $requestUri;
    }

    /**
     * Renvoi l'url de la page complète
     *
     * @return string url complète
     */
    private function selfURLRaw() {
        $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
        $protocol = self::strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/") . $s;
        $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":" . $_SERVER["SERVER_PORT"]);
        $requestUri = $_SERVER['REQUEST_URI'];

        $params = explode('&', str_replace($_SERVER['REDIRECT_URL'] . "?", "", $requestUri));
        $paramsString = "";
        foreach ($params as $paramsKey => $param) {
            if (substr($param, 0, 10) == "dt_action=")
                unset($params[$paramsKey]);
            else {
                $paramsString[] = "$param";
            }
        }
        $requestUri = $_SERVER['REDIRECT_URL'] . "?";
        if (is_array($paramsString) && count($paramsString) > 0) {
            $requestUri .= implode("&", $paramsString);
        }
        return $protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $requestUri;
    }

    private function convertUrlQuery($query) {
        $queryParts = explode('&', $query);
        $params = array();
        foreach ($queryParts as $param) {
            if ($param == "") {
                continue;
            }
            $item = explode('=', $param);
            if (!isset($item[1])) {
                $item[1] = null;
            }
            $params[$item[0]] = $item[1];
        }

        return $params;
    }

    // --------------------------------------------------------------------

    /**
     * Récupérer la partie gauche d'une chaine à partir
     * d'un caractère ou d'une chaine
     *
     * @param string $s1 Chaine à rechercher pour couper
     * @param string $s2 Chaine à couper
     * @return string chaine coupée
     */
    private static function strleft($s1, $s2)
    {
        return substr($s1, 0, strpos($s1, $s2));
    }

    private function debugQuery($query) {
        require_once 'external/geshi/geshi.php';
        $geshi = new \GeSHi($query, "sql");
        $geshi->set_header_type(GESHI_HEADER_DIV);
        $geshi->set_highlight_lines_extra_style('background: #497E7E;');
        $geshi->highlight_lines_extra(6);
        $sourceCodeRaw = $geshi->parse_code();
        $sourceCode = $sourceCodeRaw;
        $keyWords = array("FROM", "WHERE", "ORDER", "INNER", "LIMIT", "LEFT");
        $keyWords = array('<span style="color: #993333; font-weight: bold;">');
        $sourceCode = str_replace($keyWords, array_map(function($value) {
                            return "<br />" . $value;
                        }, $keyWords), $sourceCode);
        $keyWords = array(
            '<span style="color: #993333; font-weight: bold;">LEFT</span>',
            '<span style="color: #993333; font-weight: bold;">RIGHT</span>',
            '<span style="color: #993333; font-weight: bold;">INNER</span>',
            '<span style="color: #993333; font-weight: bold;">JOIN</span>',
            '<span style="color: #993333; font-weight: bold;">WHERE</span>',
            '<span style="color: #993333; font-weight: bold;">FROM</span>',
            '<span style="color: #993333; font-weight: bold;">GROUP</span>',
            '<span style="color: #993333; font-weight: bold;">LIMIT</span>',
        );
        $sourceCode = str_replace($keyWords, array_map(function($value) {
                            return "<br /><hr>" . $value;
                        }, $keyWords), $sourceCode);
        echo $sourceCodeRaw;
        echo "<hr><hr>";
        echo $sourceCode;
    }

}
