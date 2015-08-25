<?php
/**
 * Champ d'un gabarit
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
namespace Solire\Lib\Model\Gabarit\Field;

use Solire\Lib\Registry;

/**
 * Champ d'un gabarit
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
abstract class GabaritField
{
    /**
     * Nom de la vue utilisée
     *
     * @var string
     */
    protected $view = 'default';

    /**
     * Données du champ (nom, label, type...)
     *
     * @var array
     */
    protected $champ;

    /**
     * Paramètre additif du champ
     * <ul>
     * <li><i>table</i> pour <b>jointure</b></li>
     * <li><i>largeur</i>, <i>hauteur</i> pour <b>image</b></li>
     * <li>...</li>
     * </ul>
     *
     * @var array
     */
    protected $params;

    /**
     * Nom du label
     *
     * @var string
     */
    protected $label;

    /**
     * Valeur du champ
     *
     * @var string
     */
    protected $value;

    /**
     * Identifiant de la page
     *
     * @var int
     */
    protected $idGabPage;

    /**
     * Identifiant du champ
     *
     * @var int
     */
    protected $id;

    /**
     * Identifiant de la version
     *
     * @var int
     */
    protected $versionId;

    /**
     * Classes html de l'attribut
     *
     * @var string
     */
    protected $classes;

    /**
     * Connection à la BDD
     *
     * @var \Solire\Lib\MyPDO
     */
    protected $db;

    /**
     * Création du champ
     *
     * @param array  $champ     Champ
     * @param string $label     Label
     * @param mixed  $value     Valeur
     * @param int    $id        Id
     * @param string $classes   Classes
     * @param int    $idGabPage Id page
     * @param int    $versionId Identifiant version
     * @param \PDO   $db        Accès à la bdd
     */
    public function __construct(
        $champ,
        $label,
        $value,
        $id,
        $classes,
        $idGabPage,
        $versionId,
        $db = null
    ) {
        if (isset($champ['params'])) {
            $this->params = $champ['params'];
            unset($champ['params']);
        }
        if ($db) {
            $this->db = $db;
        } else {
            $this->db = Registry::get('db');
        }

        $this->idGabPage = $idGabPage;
        $this->champ = $champ;
        $this->label = $label;
        $this->value = $value;
        $this->id = $id;
        $this->classes = $classes;
        $this->versionId = $versionId;
    }

    /**
     * Toujours exécuté au début
     *
     * @return void
     */
    public function start()
    {

    }

    /**
     * Renvoi le code HTML
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Renvoi le code HTML
     *
     * @return string
     */
    public function toString()
    {
        $rc = new \ReflectionClass(get_class($this));
        $viewFile   = dirname($rc->getFileName()) . DIRECTORY_SEPARATOR
                    . 'view/' . $this->view . '.phtml';
        return $this->output($viewFile);
    }

    /**
     * Renvoi le contenu dynamisé d'une vue
     *
     * @param string $viewFile Chemin de la vue à inclure
     *
     * @return string Rendu de la vue après traitement
     */
    public function output($viewFile)
    {
        ob_start();
        include($viewFile);
        $output = ob_get_clean();

        if ($this->champ['aide'] != '') {
            $output    .= '<div class="aide" id="aide-champ' . $this->champ['id']
                        . '" style="display: none">' . $this->champ['aide']
                        . '</div>';
        }

        return $output;
    }
}
