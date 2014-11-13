<?php

namespace Solire\Lib\Datatable\Plugin;


//require_once '../library/form/shinform.php';

/**
 * Description of DatatableShinForm
 *
 * @author shinbuntu
 */
class DatatableShinForm extends \Solire\Lib\Form\ShinForm {

    protected $oDatatable;

    /**
     *
     * @param string $configName
     * @param MyPDO $db
     * @param \Solire\Lib\Datatable\Datatable $oDatatable
     */
    public function __construct($db, $oDatatable) {
        $this->oDatatable = $oDatatable;
        $configShinForm = $this->convertConfig();
        parent::__construct(null, $db, $configShinForm);

        $this->javascript = $this->oDatatable->getJavascriptLoader();
        $this->css = $this->oDatatable->getCssLoader();
//
        $this->javascript->addLibrary("app/back/js/datatable/jquery/jquery.validate.js");
        $this->javascript->addLibrary("app/back/js/datatable/jquery/additional-methods.js");
        $this->css->addLibrary("back/css/datatable/jquery.validate.css");
    }

    public function convertConfig() {
        $configShinForm = array();
        $configShinForm["form"] = array();
        $formName = "form_" . $this->oDatatable->name;
        $configShinForm["form"][$formName] = array();
        $configShinForm["form"][$formName]["fields"] = array();
        foreach ($this->oDatatable->config["columns"] as $column) {
            if (isset($column["creable_field"]) && isset($column["creable_field"]["validate"])) {
                $configShinForm["form"][$formName]["fields"][] = array(
                    "name" => $column["name"],
                    "validate"  =>  $column["creable_field"]["validate"],
                );
            }
        }

        return $configShinForm;
    }

    public function datatableAction() {
        return $this->getValidateJS();
    }

    public function editFormRenderAction() {
        return $this->getValidateJS();
    }

    public function formEditRenderAction() {
        return $this->getValidateJS();
    }

    public function formAddRenderAction() {
        return $this->getValidateJS();
    }

}

