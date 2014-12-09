<?php
/**
 * Description of shinform
 *
 * @author  smonnot <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\Form;

/**
 * Description of shinform
 *
 * @author  smonnot <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class ShinForm
{

    const VALIDATE_FLOAT = 'number';
    const VALIDATE_INT = 'digits';

    protected $config = null;

    /**
     *
     * @var MyPDO
     */
    protected $db = null;
    protected $validateRules = null;
    protected $errors = null;
    protected $dataValidated = null;
    protected $dataNotValidated = null;
    protected $queries = null;

    /**
     * construct
     *
     * @param type $configName  Nom
     * @param type $db          DB
     * @param type $configArray Configuration
     */
    public function __construct($configName, $db, $configArray = null)
    {
        if ($configName != null) {
            $config = \Solire\Lib\Registry::get('mainconfig');
            include($config->get('dirs', 'formulaire') . $configName);
            $this->config = $config;

            $this->buildValidate();
        }

        if ($configArray != null) {
            $this->config = $configArray;

            $this->buildValidate();
        }

        $this->db = $db;
    }

    /**
     * __toString
     *
     * @return type
     */
    public function __toString()
    {
        require_once 'dbug.php';
        ob_start();

        if ($this->config != null) {
            new dBug($this->config, '', true);
        }

        if ($this->validateRules != null) {
            new dBug($this->validateRules, '', true);
        }

        if ($this->dataNotValidated != null) {
            new dBug($this->dataNotValidated, '', true);
        }

        if ($this->dataValidated != null) {
            new dBug($this->dataValidated, '', true);
        }

        if ($this->errors != null) {
            new dBug($this->errors, '', true);
        }

        if ($this->queries != null) {
            new dBug($this->queries, '', true);
        }


        $output = ob_get_clean();
        return $output;
    }

    /**
     * Permet de tester les données d'un formulaire ou autre source de donnée
     * selon le fichier de configuration passé en paramètre de l'objet.
     * Documentation à venir ...
     *
     * @param type $data Valeurs à tester
     *
     * @return boolean true en cas de succès (données validées)
     */
    public function validatePHP($data)
    {
        $dataC = array();
        $this->dataNotValidated['form'] = $data;

        $errorExist = false;

        //On parcourt tous les formulaires définis dans le fichier de configuration
        foreach ($this->validateRules as $formName => $form) {
            //On parcourt chaque champ du formulaire
            foreach ($form['validate']['rules'] as $fieldName => $field) {

                //Si la valeur n'est pas défini, on la définit vide

                if (isset($form['information'][$fieldName]['type']) && $form['information'][$fieldName]['type'] == 'file') {
                    //cas fichier
                    //Test si une erreur
                    $dataC[$formName][$fieldName]['value'] = isset($data[$fieldName]) && isset($data[$fieldName]['name']) && $data[$fieldName]['error'] == 0 ? $data[$fieldName]['name'] : '';
                } else {
                    $dataC[$formName][$fieldName]['value'] = isset($data[$fieldName]) ? $data[$fieldName] : '';
                }

                //On supprime les balises HTML

                $dataC[$formName][$fieldName]['value'] = $this->removeHtml($dataC[$formName][$fieldName]['value']);

                //Si fichier
                if (isset($form['information'][$fieldName]['type']) && $form['information'][$fieldName]['type'] == 'file') {
                    //cas fichier
                    $dataC[$formName][$fieldName]['value'] = strtr($dataC[$formName][$fieldName]['value'], 'ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ', 'AAAAAACEEEEIIIIOOOOOUUUUYaaaaaaceeeeiiiioooooouuuuyy');
                    $dataC[$formName][$fieldName]['value'] = preg_replace('/([^.a-z0-9]+)/i', '-', $dataC[$formName][$fieldName]['value']);
                }

                //On parcourt chaque règle du champ
                foreach ($field as $rule => $ruleValue) {
                    $continue = false;
                    switch ($rule) {
                        case 'restricted':
                            //Pour les champs avec valeurs restreintes, on formate le message avec les valeurs
                            $ruleValue2 = $ruleValue;
                            $lastValue = array_pop($ruleValue2['value']);
                            $listRestrictedString = implode(', ', $ruleValue2['value']);
                            $listRestrictedString .= ' ou ' . $lastValue;
                            $form['validate']['messages'][$fieldName][$rule] = str_replace("[%restricted.value%]", $listRestrictedString, $form['validate']['messages'][$fieldName][$rule]);

                            break;
                        case 'restricted_extension':
                            //Pour les champs de type fichier avec extensions restreintes, on formate le message avec les valeurs
                            $ruleValue2 = $ruleValue;
                            $lastValue = array_pop($ruleValue2);
                            $listRestrictedString = implode(', ', $ruleValue2);
                            $listRestrictedString .= ' ou ' . $lastValue;
                            $form['validate']['messages'][$fieldName][$rule] = str_replace("[%restricted_extension.value%]", $listRestrictedString, $form['validate']['messages'][$fieldName][$rule]);

                            break;
                        case 'max_size':
                            //Pour les champs de type fichier avec extensions restreintes, on formate le message avec les valeurs
                            $ruleValue2 = $ruleValue;
                            $listRestrictedString = $ruleValue2 . 'ko';
                            $form['validate']['messages'][$fieldName][$rule] = str_replace("[%max_size.value%]", $listRestrictedString, $form['validate']['messages'][$fieldName][$rule]);
                            break;
                        case 'depends':
                            //Dependance sur toutes les regles
                            unset($field[$rule]);
                            foreach ($field as $key2 => $rule2) {
                                if (is_array($form['validate']['rules'][$fieldName][$key2])) {
                                    $ruleValue['depends'][key($ruleValue)] = current($ruleValue);
                                    $field[$key2]['depends'] = $ruleValue['depends'];
                                    $form['validate']['rules'][$fieldName][$key2]['depends'] = $ruleValue['depends'];
                                } else {
                                    $field[$key2] = array(
                                        'param' => $field[$key2],
                                    );

                                    $form['validate']['rules'][$fieldName][$key2] = array(
                                        'param' => $field[$key2],
                                    );

                                    $ruleValue['depends'][key($ruleValue)] = current($ruleValue);
                                    $field[$key2]['depends'] = $ruleValue['depends'];
                                    $form['validate']['rules'][$fieldName][$key2]['depends'] = $ruleValue['depends'];
                                }
                            }
                            unset($form['validate']['rules'][$fieldName][$key]);
                            unset($form['validate']['messages'][$fieldName][$key]);
                            $continue = true;
                            break;

                        default:
                            break;
                    }

                    if ($continue) {
                        continue;
                    }

                    if (isset($field['required'])) {
                        if (is_array($field['required'])) {

                            foreach ($field['required'] as $keyRuleParam => $ruleParam) {
                                switch ((string) $keyRuleParam) {
                                    case 'depends':
                                        //Gestion de dependance avec d'autre champs
                                        list($type, $formNameDep, $fieldDep) = explode('.', key($ruleParam));
                                        $fieldDepVal = current($ruleParam);
                                        if (is_array($fieldDepVal)) {
                                            $typeDependsTest = key($fieldDepVal);
                                            switch ($typeDependsTest) {
                                                case 'ISNOT':
                                                    $fieldDepVal = current($fieldDepVal);
                                                    if ($this->dataNotValidated[$type][$fieldDep] == $fieldDepVal) {
                                                        $field['required'] = false;
                                                    } else {
                                                        $field['required'] = true;
                                                    }
                                                    break;

                                                default:
                                                    break;
                                            }
                                        } else {
                                            if ($this->dataNotValidated[$type][$fieldDep] != $fieldDepVal) {
                                                $field['required'] = false;
                                            } else {
                                                $field['required'] = true;
                                            }
                                        }

                                        break;


                                    default:
                                        break;
                                }
                            }
                        }
                    }

                    //Si le champs n'est pas requis et la valeur est vide, on ne procede pas au controle
                    if ((!isset($field['required']) || $field['required'] == false) && $dataC[$formName][$fieldName]['value'] == '') {

                        continue;
                    }
                    /* On demande à la fonction _validate de valider notre champ avec la règle courante
                     * Si la valeur vérifie la règle, la fonction nous renvoie celle-ci
                     * Sinon elle renvoi false.
                     */
                    $file = null;
                    if (isset($form['information'][$fieldName]['type']) && $form['information'][$fieldName]['type'] == 'file') {
                        $file = $data[$fieldName];
                    }
                    $dataC[$formName][$fieldName]['value'] = $this->validate($dataC[$formName][$fieldName]['value'], array($rule => $field[$rule]), $file);
                    //Si la valeur est à false, on traite le message derreur correspondant et on sort de la boucle (On passe au champ suivant)
                    if ($dataC[$formName][$fieldName]['value'] === false) {
                        $dataC[$formName][$fieldName]['error'] = $form['validate']['messages'][$fieldName][$rule];

                        //Pour facilité le parcourt des erreurs
                        $this->errors[] = $form['validate']['messages'][$fieldName][$rule];

                        //Permet de savoir si une ou plusieurs erreurs sont survenus (valeur retourné par la fonction)
                        $errorExist = true;
                        break;
                    }
                }
            }
        }

        //Puis on met tous les champs (valide ou non) dans un array

        $this->dataValidated['form'] = $dataC;
        return !$errorExist;
    }

    /**
     * Permet de construire les requetes par rapport à la configuration
     *
     * @param boolean $execute Permet d'executer les requetes directement
     *
     * @return boolean Succes ou non de l'execution
     */
    public function buildQueries($execute = true, $add = true)
    {
        $r = true;


        //On parcourt toutes nos insertions définis
        foreach ($this->config['save']['mysql'] as $keyConfig => $tableConfig) {
            $continue = false;
            //Si l'insertion à une dépendance, on controle celle ci
            if (isset($tableConfig['depends'])) {
                $ruleParam = $tableConfig['depends'];

                foreach ($ruleParam as $fieldDepName => $fieldDepVal) {
                    //Gestion de dependance
                    list($type, $formNameDep, $fieldDep) = explode('.', $fieldDepName);

                    if (is_array($fieldDepVal)) {
                        $typeDependsTest = key($fieldDepVal);
                        switch ($typeDependsTest) {
                            case 'ISNOT':
                                $fieldDepVal = current($fieldDepVal);
                                if ($this->dataNotValidated[$type][$fieldDep] == $fieldDepVal) {
                                    $continue = true;
                                }

                                break;
                            case 'IS':
                                $fieldDepVal = current($fieldDepVal);
                                if ($this->dataNotValidated[$type][$fieldDep] != $fieldDepVal) {
                                    $continue = true;
                                }

                                break;


                            default:
                                break;
                        }
                    } else {

                        //La dépendance n'est pas satisfaite, donc on passe à l'insertion suivante
                        if ($this->dataNotValidated[$type][$fieldDep] != $fieldDepVal) {
                            $continue = true;
                        }
                    }
                    if ($continue) {
                        break;
                    }
                }

                if ($continue) {
                    continue;
                }
            }


            if ($add) {
                $query = 'INSERT INTO ';
            } else {
                $query = 'UPDATE ';
            }


            $query .= '`' . $tableConfig['table']['name'] . "` ";
            $query .= 'SET ';
            $querySetters = array();
            $queryWhere = array();

            //Tous les champs du formulaire sont sauvé
            if (is_string($tableConfig['columns']) && $tableConfig['columns'] == 'all') {
                $tableConfig['columns'] = array();
                foreach ($this->dataValidated['form'][$keyConfig] as $columnName => $columnValue) {
                    $tableConfig['columns'][] = array(
                        'name' => $columnName,
                        'value' => $columnValue['value'],
                    );
                }
            }



            foreach ($tableConfig['columns'] as $column) {
                $continue = false;
                if (isset($column['depends'])) {
                    $ruleParam = $column['depends'];

                    foreach ($ruleParam as $fieldDepName => $fieldDepVal) {
                        //Gestion de dependance
                        list($type, $formNameDep, $fieldDep) = explode('.', $fieldDepName);

                        if (is_array($fieldDepVal)) {
                            $typeDependsTest = key($fieldDepVal);
                            switch ($typeDependsTest) {
                                case 'ISNOT':
                                    $fieldDepVal = current($fieldDepVal);

                                    //Cas des fichier
                                    if (is_array($this->dataNotValidated[$type][$fieldDep])) {
                                        if ($this->dataValidated[$type][$formNameDep][$fieldDep]['value'] == $fieldDepVal) {
                                            $continue = true;
                                        }
                                    } else {
                                        if ($this->dataNotValidated[$type][$fieldDep] == $fieldDepVal) {
                                            $continue = true;
                                        }
                                    }


                                    break;
                                case 'IS':
                                    $fieldDepVal = current($fieldDepVal);
                                    //Cas des fichier
                                    if (is_array($this->dataNotValidated[$type][$fieldDep])) {
                                        if ($this->dataValidated[$type][$formNameDep][$fieldDep]['value'] != $fieldDepVal) {
                                            $continue = true;
                                        }
                                    } else {
                                        if ($this->dataNotValidated[$type][$fieldDep] != $fieldDepVal) {
                                            $continue = true;
                                        }
                                    }

                                    break;


                                default:
                                    break;
                            }
                        } else {

                            //La dépendance n'est pas satisfaite, donc on passe à l'insertion suivante
                            if ($this->dataNotValidated[$type][$fieldDep] != $fieldDepVal) {
                                $continue = true;
                            }
                        }
                        if ($continue) {
                            break;
                        }
                    }

                    if ($continue) {
                        continue;
                    }
                }

                $varValue = null;
                if (key_exists('value', $column)) {
                    if (is_array($column['value'])) {
                        if (isset($column['value']['from'])) {
                            $valueFrom = explode('.', $column['value']['from']);
                            $varValue = $this->dataValidated;
                            foreach ($valueFrom as $v) {
                                $varValue = $varValue[$v];
                            }
                        } else {
                            foreach ($column['value'] as $colV) {
                                if (isset($colV['from'])) {
                                    $valueFrom = explode('.', $colV['from']);
                                    $varValueTmp = $this->dataValidated;
                                    foreach ($valueFrom as $v) {
                                        $varValueTmp = $varValueTmp[$v];
                                    }
                                    if (isset($colV['function'])) {
                                        $function = $colV['function'];
                                        $varValueTmp = $function($varValueTmp);
                                    }
                                    $varValue .= $varValueTmp;
                                } else {
                                    $varValue .= current($colV);
                                }
                            }
                        }
                    } elseif (is_string($column['value']) || is_int($column['value']) || is_float($column['value'])) {
                        $varValue = $column['value'];
                    }




                    $value = $varValue;
                } else {
                    $value = '';
                }

                //Si c'est un rewriting
                if (isset($column['rewriting'])) {
                    $where = $column['rewriting']['where'];
                    //Si modification, on ajoute une condition pour exclure le rewriting de lelement que l'on edite
                    if (!$add) {
//                        $where = " AND ";
                    }

                    $varValue = $this->db->rewrit($varValue, $column['rewriting']['from'], $column['rewriting']['field'], $where);
                    $value = $varValue;
                }

                $value = $this->db->quote($value);

                if (!$add && isset($column['index']) && $column['index'] == true) {
                    $queryWhere [] = '`' . $column['name'] . '` = ' . $value;
                }

                if (isset($column['mysql_function']) && $column['mysql_function'] != '')
                    $value = $column['mysql_function'] . "($value)";
                $querySetters [] = '`' . $column['name'] . '` = ' . $value;
            }


            $query .= implode(', ', $querySetters);
            if (count($queryWhere) > 0)
                $query .= ' WHERE ' . implode(' AND ', $queryWhere);

            if ($execute && $r !== false) {
                $r = $this->db->exec($query);
                $lastId = $this->db->lastInsertId();
                if (intval($lastId) > 0) {
                    $this->dataValidated['mysql'][$keyConfig]['id'] = $lastId;
                }
            }
            if ($r === false) {
                break;
            }


            $this->queries[] = $query;
        }

        return $r !== false;
    }

    /**
     * getValidateJS
     *
     * @return string
     */
    public function getValidateJS()
    {
        $js = '';
        $js .= '<script>';
        $js .= '$().ready(function() {';
        $functions = array();
        $functionsKeys = array();

        //On parcourt chaque formulaire
        foreach ($this->validateRules as $formName => $form) {
            //On parcourt chaque champs
            foreach ($form['validate']['rules'] as $fieldName => $field) {
                //On parcourt chaque règle
                uksort($field, array($this, 'sortRulesJs'));

                foreach ($field as $key => $rule) {
                    $continue = false;
                    //Si la règle demande un pre traitement
                    switch ($key) {
                        case 'unique':
                            $form['validate']['messages'][$fieldName]['remote'] = $form['validate']['messages'][$fieldName][$key];
                            $form['validate']['rules'][$fieldName]['remote'] = array(
                                'url' => $rule['url'],
                                'type' => 'post',
                                'data' => array(
                                    'value' => "[%function$fieldName%]"
                                ),
                            );
                            $functions[] = 'function(){ return $("form[name=\'' . $formName . '\'] input[name=\'' . $fieldName . '\']").val(); }';
                            $functionsKeys[] = '"[%function' . $fieldName . '%]"';
                            unset($form['validate']['rules'][$fieldName][$key]);
                            unset($form['validate']['messages'][$fieldName][$key]);
                            break;
                        case 'restricted':
                            $lastValue = array_pop($rule['value']);
                            $listRestrictedString = implode(', ', $rule['value']);
                            $listRestrictedString .= ' ou ' . $lastValue;
                            $form['validate']['messages'][$fieldName][$key] = str_replace('[%restricted.value%]', $listRestrictedString, $form['validate']['messages'][$fieldName][$key]);
                            break;
                        case 'restricted_extension':
                            $lastValue = array_pop($rule);
                            $listRestrictedString = implode(', ', $rule);
                            $rule[] = $lastValue;
                            $listRestrictedString .= ' ou ' . $lastValue;
                            $form['validate']['messages'][$fieldName]['accept'] = str_replace('[%restricted_extension.value%]', $listRestrictedString, $form['validate']['messages'][$fieldName][$key]);
                            $form['validate']['rules'][$fieldName]['accept'] = implode('|', $rule);
                            unset($form['validate']['rules'][$fieldName][$key]);
                            unset($form['validate']['messages'][$fieldName][$key]);
                            break;
                        case 'max_size':
                            unset($form['validate']['rules'][$fieldName][$key]);
                            unset($form['validate']['messages'][$fieldName][$key]);
                            break;
                        case 'equalTo':
                            list($type, $formNameDep, $fieldNameEqual) = explode('.', $rule);
                            if ($formNameDep == 'this') {
                                $formNameDep = $formName;
                            }
                            $fieldDepSelector = "form[name='$formNameDep'] input[name='$fieldNameEqual'], form[name='$formNameDep'] select[name='$fieldNameEqual']";
                            $form['validate']['rules'][$fieldName][$key] = $fieldDepSelector;
                            break;
                        case 'depends':
                            //Dependance sur toutes les regles
                            unset($field[$key]);

                            list($type, $formNameDep, $fieldNameDep) = explode('.', key($rule));
                            if ($formNameDep == 'this') {
                                $formNameDep = $formName;
                            }
                            $fieldDepSelector = "form[name='$formNameDep'] input[name='$fieldNameDep'], form[name='$formNameDep'] select[name='$fieldNameDep']";
                            $fieldDepSelectorCheckbox = "form[name='$formNameDep'] input[name='$fieldNameDep']:checked, form[name='$formNameDep'] select[name='$fieldNameDep']";
                            $fieldDepVal = current($rule);
                            if (is_array($fieldDepVal)) {
                                $typeDependsTest = key($fieldDepVal);
                                switch ($typeDependsTest) {
                                    case 'ISNOT':
                                        $fieldDepVal = current($fieldDepVal);
                                        //Ajout du cas des checkbox
                                        $functions[] = "function(){ if($(\"$fieldDepSelector\").is('[type=checkbox]')) return $(\"$fieldDepSelectorCheckbox\").val() != '$fieldDepVal'; else return $(\"$fieldDepSelector\").val() != '$fieldDepVal'; }";
                                        $functionsKeys[] = '"[%function' . $fieldName . $key . '%]"';
                                        break;

                                    default:
                                        break;
                                }
                            } else {
                                //Ajout du cas des checkbox
                                $functions[] = "function(){ if($(\"$fieldDepSelector\").is('[type=checkbox]')) return $(\"$fieldDepSelectorCheckbox\").length == 0 ? true : false ; else return $(\"$fieldDepSelector\").val() == '$fieldDepVal'; }";

                                $functionsKeys[] = '"[%function' . $fieldName . $key . '%]"';
                            }

                            $rule['depends'] = '[%function' . $fieldName . $key . '%]';

                            foreach ($form['validate']['rules'][$fieldName] as $key2 => $rule2) {
                                if (is_array($form['validate']['rules'][$fieldName][$key2])) {
                                    $form['validate']['rules'][$fieldName][$key2]['depends'] = $rule['depends'];
                                    if ($key2 == 'remote') {
                                        $form['validate']['rules'][$fieldName]['xremote'] =
                                            $form['validate']['rules'][$fieldName][$key2]
                                        ;
                                        $form['validate']['messages'][$fieldName]['xremote'] =
                                            $form['validate']['messages'][$fieldName][$key2]
                                        ;
                                        unset($form['validate']['rules'][$fieldName][$key2]);
                                        unset($form['validate']['messages'][$fieldName][$key2]);
                                    }
                                } else {
                                    $form['validate']['rules'][$fieldName][$key2] = array(
                                        'param' => $field[$key2],
                                    );

                                    $form['validate']['rules'][$fieldName][$key2] =
                                        $form['validate']['rules'][$fieldName][$key2]
                                    ;

                                    $form['validate']['rules'][$fieldName][$key2]['depends'] =
                                        $rule['depends']
                                    ;
                                }
                            }
                            unset($form['validate']['rules'][$fieldName][$key]);
                            unset($form['validate']['messages'][$fieldName][$key]);
                            $continue = true;
                            break;

                        default:
                            break;
                    }
                    if ($continue) {
                        continue;
                    }



                    if (is_array($field[$key])) {
                        foreach ($field[$key] as $keyRuleParam => $ruleParam) {
                            switch ((string) $keyRuleParam) {
                                case 'depends':
                                    //Gestion de dependance avec d'autre champs
                                    list($type, $formNameDep, $fieldNameDep) = explode('.', key($ruleParam));
                                    if ($formNameDep == 'this') {
                                        $formNameDep = $formName;
                                    }
                                    $fieldDepSelector = 'form[name="' . $formNameDep . '"] input[name="'
                                        . $fieldNameDep . '"], form[name="' . $formNameDep
                                        . '"] select[name="' . $fieldNameDep . '"]'
                                    ;
                                    $fieldDepSelectorCheckbox = 'form[name="' . $formNameDep
                                        . '"] input[name="' . $fieldNameDep
                                        . '"]:checked, form[name="' . $formNameDep
                                        . '"] select[name="' . $fieldNameDep . '"]'
                                    ;
                                    $fieldDepVal = current($ruleParam);
                                    if (is_array($fieldDepVal)) {
                                        $typeDependsTest = key($fieldDepVal);
                                        switch ($typeDependsTest) {
                                            case 'ISNOT':
                                                $fieldDepVal = current($fieldDepVal);
                                                $functions[] = 'function(){ if($("'
                                                    . $fieldDepSelector . '").is("[type=checkbox]")) return $("'
                                                    . $fieldDepSelectorCheckbox . '").val() != "'
                                                    . $fieldDepVal . '"; else return $("'
                                                    . $fieldDepSelector . '").val() != ' . $fieldDepVal . '; }"'
                                                ;
                                                $functionsKeys[] = '"[%function'
                                                    . $fieldName . $key
                                                    . $keyRuleParam . '%]"'
                                                ;
                                                break;

                                            default:
                                                break;
                                        }
                                    } else {
                                        $functions[] = 'function(){ if($('
                                        . '\'' . $fieldDepSelector . '\')'
                                        . '.is("[type=checkbox]")) return'
                                        . ' $(\'' . $fieldDepSelectorCheckbox . '\').length'
                                        . ' == 0 ? true : false ; else '
                                        . 'return $(\'' . $fieldDepSelector . '\').val() == "' . $fieldDepVal . '"; }';
                                        $functionsKeys[] = '"[%function' . $fieldName . $key . $keyRuleParam . '%]"';
                                    }
                                    $form['validate']['rules'][$fieldName][$key][$keyRuleParam] =
                                        '[%function' . $fieldName . $key . $keyRuleParam . '%]'
                                    ;
                                    break;

                                default:
                                    break;
                            }
                        }
                    }
                }
            }

            $functions[] = '
            function(form, validator){
                var position = $(validator.invalidElements()[0]).position()
                var newTopPosition = position.top
                if($(validator.invalidElements()[0]).parents(".modal-body:first").length > 0) {
                    $scrollRel = $(validator.invalidElements()[0]).parents(".modal-body:first")
                } else {
                    $scrollRel = $("html, body")
                    newTopPosition = newTopPosition - 60
                }
                $scrollRel.animate({scrollTop: newTopPosition}, "slow", function() {
                    $(validator.invalidElements()[0]).focus();
                });

            }';
            $functionsKeys[] = '"[%functioninvalidHandler%]"';
            $form['validate']['invalidHandler'] = '[%functioninvalidHandler%]';
            $form['validate']['focusInvalid'] = false;




            $form['validate']['ignore'] = '';

            $json = json_encode($form['validate']);


            $json = str_replace($functionsKeys, $functions, $json);


            $js .= '$("form[name=\'' . $formName . '\']").validate(' . $json . ');';
        }
        $js .= '});';
        $js .= '</script>';

        /* Decommentez pour annuler les controles JS afin de bien tester coté php */

        return $js;
    }

    /**
     * addCustomData
     *
     * @param type $key   Key
     * @param type $value Value
     *
     * @return void
     */
    public function addCustomData($key, $value)
    {
        $this->dataValidated['custom'][$key] = $value;
    }

    /**
     * addCustomDataForm
     *
     * @param type $formName  FormName
     * @param type $fieldName FieldName
     * @param type $value     Value
     *
     * @return void
     */
    public function addCustomDataForm($formName, $fieldName, $value)
    {
        $this->dataValidated['form'][$formName][$fieldName] = array(
            'value' => $value
        );
    }

    /**
     * injectData
     *
     * @param type $key   Key
     * @param type $value Value
     *
     * @return void
     */
    public function injectData($key, $value)
    {
        $this->dataValidated[$key] = $value;
    }

    /**
     * getErrors
     *
     * @return type
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * getDataValidated
     *
     * @return type
     */
    public function getDataValidated()
    {
        return $this->dataValidated;
    }

    /**
     * getQueries
     *
     * @return type
     */
    public function getQueries()
    {
        return $this->queries;
    }

    /**
     * buildValidate
     *
     * @return void
     */
    protected function buildValidate()
    {
        foreach ($this->config['form'] as $formName => $form) {
            foreach ($form['fields'] as $field) {
                uksort($field['validate']['rules'], array($this, 'sortRules'));
                $this->validateRules[$formName]['validate']['rules'][$field['name']] = $field['validate']['rules'];
                $this->validateRules[$formName]['validate']['messages'][$field['name']] =
                    $field['validate']['messages']
                ;
                unset($field['validate']);
                $this->validateRules[$formName]['information'][$field['name']] = $field;
            }
        }
    }

    /**
     * validate
     *
     * @param type $stringToValidate StringToValidate
     * @param type $validateType     ValidateType
     * @param type $file             File
     *
     * @return type
     */
    public function validate($stringToValidate, $validateType, $file = null)
    {

        if (is_array(current($validateType))) {
            foreach (current($validateType) as $keyRuleParam => $ruleParam) {
                switch ((string) $keyRuleParam) {
                    case 'depends':
                        //Gestion de dependance avec d'autre champs
                        list($type, $formNameDep, $fieldDep) = explode('.', key($ruleParam));
                        $fieldDepVal = current($ruleParam);
                        if (is_array($fieldDepVal)) {
                            $typeDependsTest = key($fieldDepVal);
                            switch ($typeDependsTest) {
                                case 'ISNOT':
                                    $fieldDepVal = current($fieldDepVal);
                                    if ($this->dataNotValidated[$type][$fieldDep] == $fieldDepVal) {
                                        return $stringToValidate;
                                    } else {
                                        $currentKey = key($validateType);
                                        unset($validateType[$currentKey]['depends']);
                                        if (
                                            count($validateType[$currentKey]) == 1
                                            && key($validateType[$currentKey]) == 'param'
                                        ) {
                                            $validateType[$currentKey] = $validateType[$currentKey]['param'];
                                        }
                                    }
                                    break;

                                default:
                                    break;
                            }
                        } else {
                            if ($this->dataNotValidated[$type][$fieldDep] != $fieldDepVal) {
                                return $stringToValidate;
                            } else {
                                $currentKey = key($validateType);
                                unset($validateType[$currentKey]['depends']);
                                if (
                                    count($validateType[$currentKey]) == 1
                                    && key($validateType[$currentKey]) == 'param'
                                ) {
                                    $validateType[$currentKey] = $validateType[$currentKey]['param'];
                                }
                            }
                        }


                        break;


                    default:
                        break;
                }
            }
        }


        switch (key($validateType)) {
            case 'equalTo':
                //Vérification de mot de passe
                list($type, $formNameDep, $fieldDep) = explode('.', current($validateType));
                $fieldDepVal = $stringToValidate;
                if ($this->dataNotValidated[$type][$fieldDep] != $fieldDepVal) {
                    $stringToValidate = false;
                }
                break;
            case 'required':
                $stringToValidate = $this->validateRequired($stringToValidate);
                break;
            case 'minlength':
                $stringToValidate = $this->validateMinLength($stringToValidate, current($validateType));
                break;
            case 'maxlength':
                $stringToValidate = $this->validateMaxLength($stringToValidate, current($validateType));
                break;
            case 'unique':
                $stringToValidate = $this->validateUnique($stringToValidate, current($validateType));
                break;
            case 'restricted':
                $stringToValidate = $this->validateRestricted($stringToValidate, current($validateType));
                break;
            case 'max_size':
                $stringToValidate = $this->validateFileSize($stringToValidate, current($validateType), $file['size']);
                break;
            case 'restricted_extension':
                $stringToValidate = $this->validateFileExtension($stringToValidate, current($validateType), $file);
                break;
            default:
                $stringToValidate = $this->validateType($stringToValidate, key($validateType), current($validateType));
                break;
        }
        return $stringToValidate;
    }

    /**
     * validateFile
     *
     * @return void
     */
    protected function validateFile()
    {

    }

    /**
     * validateType
     *
     * @param type $stringToValidate StringToValidate
     * @param type $secureType       SecureType
     * @param type $params           Params
     *
     * @return boolean
     */
    protected function validateType($stringToValidate, $secureType, $params = null)
    {
        switch ($secureType) {
            case 'digits':
                $stringToValidate = str_replace(CHR(32), '', $stringToValidate);
                $filteredString = filter_var($stringToValidate, FILTER_VALIDATE_INT);
                break;
            case 'email':
                $filteredString = filter_var($stringToValidate, FILTER_VALIDATE_EMAIL);
                break;
            case 'number':
                $filteredString = filter_var($stringToValidate, FILTER_VALIDATE_FLOAT);
                break;
            case 'url':
                $filteredString = filter_var($stringToValidate, FILTER_VALIDATE_URL);
                break;
            case 'username':
                $result = preg_match('/^([a-zA-Z0-9_]+)$/', $stringToValidate);
                if ($result !== false && $result == 1) {
                    $filteredString = $stringToValidate;
                } else {
                    $filteredString = false;
                }
                break;
            case 'postcode_fr':
                $result = preg_match('/^(2[ab]|0[1-9]|[1-9][0-9])[0-9]{3}$/', $stringToValidate);
                if ($result !== false && $result == 1) {
                    $filteredString = $stringToValidate;
                } else {
                    $filteredString = false;
                }
                break;
            case 'postcode_be':
            case 'postcode_lu':
            case 'postcode_ch':
                $result = preg_match('/^[0-9]{4}$/', $stringToValidate);
                if ($result !== false && $result == 1) {
                    $filteredString = $stringToValidate;
                } else {
                    $filteredString = false;
                }
                break;
            case 'date_fr':
                $pattern = '/^(((0[1-9]|[12]\d|3[01])\/(0[13578]|1[02])\/((19|'
                . '[2-9]\d)\d{2}))|((0[1-9]|[12]\d|30)\/(0[13456789]|'
                . '1[012])\/((19|[2-9]\d)\d{2}))|((0[1-9]|1\d|2[0-8])'
                . '\/02\/((19|[2-9]\d)\d{2}))|(29\/02\/((1[6-9]|[2-9]'
                . '\d)(0[48]|[2468][048]|[13579][26])|((16|[2468][048]'
                . '|[3579][26])00))))$/'
                ;
                $result = preg_match($pattern, $stringToValidate);
                if ($result !== false && $result == 1) {
                    list( $Jour, $Mois, $Annee) = explode('/', $stringToValidate);
                    $filteredString = ($Annee . '-' . $Mois . '-' . $Jour);
                } else {
                    $filteredString = false;
                }
                break;

            default:
                $filteredString = false;
                break;
        }

        return $filteredString;
    }

    /**
     * validateUnique
     *
     * @param type $stringToValidate StringToValidate
     * @param type $params           Params
     *
     * @return type
     */
    protected function validateUnique($stringToValidate, $params)
    {

        $stringToValidate2 = $this->db->quote($stringToValidate);
        $query = 'SELECT count(`' . $params['column'] . '`) FROM `' . $params['table'] . '` WHERE `'
        . $params['column'] . '` = ' . $stringToValidate2;
        $stringToValidate = $this->db->query($query)->fetchColumn() > 0 ? false : $stringToValidate;
        return $stringToValidate;
    }

    /**
     * validateRestricted
     *
     * @param type $stringToValidate StringToValidate
     * @param type $params           Params
     *
     * @return type
     */
    protected function validateRestricted($stringToValidate, $params)
    {
        $filteredString = in_array($stringToValidate, $params['value']) ? $stringToValidate : false;
        return $filteredString;
    }

    /**
     * validateRequired
     *
     * @param type $stringToValidate StringToValidate
     *
     * @return type
     */
    protected function validateRequired($stringToValidate)
    {
        $filteredString = !isset($stringToValidate) || $stringToValidate == ''
            || $stringToValidate === 0 ? false : $stringToValidate;

        return $filteredString;
    }

    /**
     * validateMaxLength
     *
     * @param type $stringToValidate StringToValidate
     * @param type $len              Len
     *
     * @return type
     */
    protected function validateMaxLength($stringToValidate, $len)
    {
        if (strlen($stringToValidate) > $len) {
            $filteredString = false;
        } else {
            $filteredString = $stringToValidate;
        }
        return $filteredString;
    }

    /**
     * validateMinLength
     *
     * @param type $stringToValidate StringToValidate
     * @param type $len              Len
     *
     * @return type
     */
    protected function validateMinLength($stringToValidate, $len)
    {
        if (strlen($stringToValidate) != 0 && strlen($stringToValidate) < $len) {
            $filteredString = false;
        } else {
            $filteredString = $stringToValidate;
        }
        return $filteredString;
    }

    /**
     * validateFileSize
     *
     * @param type $stringToValidate StringToValidate
     * @param type $sizeMax          SiteMax
     * @param type $sizeToValidate   SizeToValidate
     *
     * @return boolean
     */
    protected function validateFileSize($stringToValidate, $sizeMax, $sizeToValidate)
    {
        $sizeMax = $sizeMax * 1024;
        if ($sizeToValidate > $sizeMax) {
            return false;
        }
        return $stringToValidate;
    }

    /**
     * Valide extension
     *
     * @param type $fileName           Nom du fichier
     * @param type $fileExtensionAllow Extensions autorisées
     *
     * @return boolean
     */
    protected function validateFileExtension($fileName, $fileExtensionAllow)
    {
        $extension = strrchr($fileName, '.');
        if (
            $fileName != ''
            && $fileExtensionAllow != null
            && in_array(substr($extension, 1), $fileExtensionAllow) === false
        ) { //Si l'extension n'est pas dans le tableau
            return false;
        }
        return $fileName;
    }

    /**
     * Trie
     *
     * @param type $a A
     * @param type $b B
     *
     * @return int
     */
    protected function sortRules($a, $b)
    {
        $priority = array('depends' => 0, 'required' => 1, 'minlength' => 2, 'maxlength' => 3);
        // company logic dictates a week begins on a Tuesday.
        if (
            (isset($priority[$a])
            && !isset($priority[$b]))
            || (isset($priority[$b])
            && isset($priority[$a])
            && $priority[$a] < $priority[$b])
        ) {
            return -1;
        } else {
            return 1;
        }
    }

    /**
     * Trie
     *
     * @param type $a A
     * @param type $b B
     *
     * @return int
     */
    protected function sortRulesJs($a, $b)
    {
        $priority = array('depends' => 0, 'required' => 1, 'minlength' => 2, 'maxlength' => 3);

        if ((isset($priority[$a]) && !isset($priority[$b]))
            || (isset($priority[$b]) && isset($priority[$a]) && $priority[$a] < $priority[$b])
        ) {
            return -1;
        } else {
            return 1;
        }
    }

    /**
     * Strip tags
     *
     * @param type $string Chaine
     *
     * @return type
     */
    protected function removeHtml($string)
    {
        return strip_tags($string);
    }
}
