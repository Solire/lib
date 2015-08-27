<?php
/**
 * Gestionnaire de pagination
 *
 * @author  smonnot <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
namespace Solire\Lib;

/**
 * Gestionnaire de pagination
 *
 * @author  smonnot <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Pagination
{
    /**
     * Numéro de la page courante
     * @var int
     */
    protected $currentPage = 1;

    /**
     * Requete sans le SELECT
     * @var string
     */
    protected $queryWithoutSelect;

    /**
     * Partie SELECT de la requete
     * @var string
     */
    protected $queryGetField;

    /**
     * Nombre de résultats
     * @var int
     */
    protected $countResults;

    /**
     * Tableau contenant les résultats
     * @var array
     */
    protected $results;

    /**
     * Nombre d'éléments à afficher par page
     * @var int
     */
    protected $nbElemsByPage;

    /**
     * Nombre de pages
     * @var int
     */
    protected $nbPages;

    /**
     * Limites de récupération
     * @var array
     */
    protected $limit;

    /**
     * Html/Text du lien page précédente (Vide pour desactiver)
     * @var string
     */
    protected $prevHtml = '&lsaquo;';

    /**
     * Html/Text du lien page suivante  (Vide pour desactiver)
     * @var string
     */
    protected $nextHtml = '&rsaquo;';

    /**
     * Permet de limiter l'affichage des pages à N pages avant et après la page
     * courante (-1 pour la liste complete des pages)
     * @var int
     */
    protected $delta = 1;

    /**
     * Html/Text à afficher lorsqu'un delta est paramétré
     * @var string
     */
    protected $deltaHtml = '&#8230;';

    /**
     * Accès base de données
     * @var MyPDO
     */
    private $db = null;

    /**
     * Constructeur de Pagination
     *
     * @param MyPDO  $myPdo              Connection MyPDO
     * @param string $queryWithoutSelect Requete sans la clause SELECT
     * @param string $queryGetField      Clause SELECT sans 'SELECT'
     * @param int    $nbElemsByPage      Nombres d'éléments par page
     * @param int    $currentPage        Numéro de la page courante
     */
    public function __construct(
        MyPDO $myPdo,
        $queryWithoutSelect,
        $queryGetField,
        $nbElemsByPage,
        $currentPage
    ) {
        $this->db = $myPdo;
        $this->queryWithoutSelect = $queryWithoutSelect;
        $this->queryGetField = $queryGetField;
        $this->nbElemsByPage = intval($nbElemsByPage);
        $this->executeCountQuery();
        if ($this->countResults == 0) {
            return;
        }

        $this->calculNbPages();
        $this->setCurrentPage($currentPage);
        $this->calculLimit();
        $this->executeQuery();
    }

    /**
     * Renvoie le numéro de la page courante
     *
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    /**
     * Renvoie le nombre d'éléments par page
     *
     * @return int
     */
    public function getNbPage()
    {
        return $this->nbPages;
    }

    /**
     * Définit le numéro de la page courante
     *
     * @param int $currentPage Numéro de la page courante
     *
     * @return void
     */
    public function setCurrentPage($currentPage)
    {
        if (intval($currentPage) == 0) {
            $currentPage = 1;
        } else {
            $currentPage = intval($currentPage);
        }

        // Si la valeur de $currentPage est plus grande que le nombre de pages
        if ($currentPage > $this->nbPages) {
            $this->currentPage = $this->nbPages;
        } else {
            $this->currentPage = $currentPage;
        }
    }

    /**
     * Renvoie un tableau avec toutes les pages disponibles
     *
     * @return array
     */
    public function getPaginationArray()
    {
        $pages = [];

        /* Lien page précédente */
        if ($this->prevHtml && $this->currentPage > 1) {
            $pages[] = [
                'text'    => $this->prevHtml,
                'num'     => $this->currentPage - 1,
                'current' => false,
                'link'    => true
            ];
        }

        /* Lien des pages numérotées */
        for ($i = 1; $i <= $this->nbPages; $i++) {
            //Si il s'agit de la page actuelle
            if ($i == $this->currentPage) {
                $pages[] = [
                    'text'    => $i,
                    'num'     => $i,
                    'current' => true,
                    'link'    => false
                ];
            } else {
                /* Si première page
                 *  ou dernière page
                 *  ou toutes les pages à afficher
                 */
                if ($i == 1
                    || $i == $this->nbPages
                    || $this->delta < 0
                    || $i < $this->currentPage
                    && ($i + $this->delta) >= $this->currentPage
                    || $i > $this->currentPage
                    && ($i - $this->delta) <= $this->currentPage
                ) {
                    $pages[] = [
                        'text'    => $i,
                        'num'     => $i,
                        'current' => false,
                        'link'    => true
                    ];
                } elseif ($i < $this->currentPage
                    && ($i + $this->delta + 1) == $this->currentPage
                    || $i > $this->currentPage
                    && ($i - $this->delta - 1) == $this->currentPage
                ) {
                    $pages[] = [
                        'text'    => $this->deltaHtml,
                        'num'     => $this->deltaHtml,
                        'current' => false,
                        'link'    => false
                    ];
                }
            }
        }

        /* Lien page suivante*/
        if ($this->nextHtml && $this->currentPage < $this->nbPages) {
            $pages[] = [
                'text'    => $this->nextHtml,
                'num'     => $this->currentPage + 1,
                'current' => false,
                'link'    => true
            ];
        }

        return $pages;
    }

    /**
     * Renvoie le tableau des résultats de la page courante
     *
     * @return array
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * Définit les résultats
     *
     * @param array $results Tableau de résultats
     *
     * @return void
     */
    public function setResults($results)
    {
        $this->results = $results;
    }

    /**
     * Execute la requete de récupération du nombre total d'éléments
     *
     * @return void
     */
    private function executeCountQuery()
    {
        $query = 'SELECT count(*) ' . $this->queryWithoutSelect;
        $prepareQuery = $this->db->prepare($query);
        $prepareQuery->execute();
        $this->countResults = $prepareQuery->fetch(\PDO::FETCH_COLUMN);
    }

    /**
     * Execute la requete de récupération des éléments de la page courante
     *
     * @return void
     */
    private function executeQuery()
    {
        $query = 'SELECT '
                . $this->queryGetField . ' '
                . $this->queryWithoutSelect . ' '
                . $this->getLimit();
        $prepareQuery = $this->db->prepare($query);
        $prepareQuery->execute();
        $this->results = $prepareQuery->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Calcule des limites de la requete à executer
     *
     * @return void
     */
    private function calculLimit()
    {
        // On calcul la première entrée à lire
        $this->limit[] = intval(($this->currentPage - 1) * $this->nbElemsByPage);
        // On calcul le nombre d'entrées à lire
        $this->limit[] = $this->nbElemsByPage;
    }

    /**
     * Renvoie la clause LIMIT de la requête à exécuter
     *
     * @return string
     */
    private function getLimit()
    {
        if (is_null($this->limit) || $this->limit == '' || $this->limit[1] == 0) {
            $limit = '';
        } else {
            $limit = 'LIMIT ';
            if (is_array($this->limit)) {
                $limit .= implode($this->limit, ',');
            } else {
                $limit .= $this->limit;
            }
        }

        return $limit;
    }

    /**
     * Calcule le nombre de page total
     *
     * @return void
     */
    private function calculNbPages()
    {
        if (intval($this->nbElemsByPage) == 0) {
            $this->nbPages = 1;
        } else {
            $this->nbPages = ceil($this->countResults / $this->nbElemsByPage);
        }
    }
}
