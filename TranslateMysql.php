<?php
/**
 * Traduction des textes statiques.
 *
 * @author  smonnot <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib;

/**
 * Traduction des textes statiques.
 *
 * @author  smonnot <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class TranslateMysql
{
    /**
     * Instance (pour pouvoir utiliser la classe partout) avec la méthode
     * statique TranslateMysql::trad().
     *
     * @var TranslateMysql
     */
    protected static $self;

    /**
     * Tableau des traductions.
     *
     * @var array
     */
    protected $translate = [];

    /**
     * Identifiant de la version.
     *
     * @var int
     */
    protected $locale = false;

    /**
     * Identifiant de l'api courante.
     *
     * @var int
     */
    protected $api = 1;

    /**
     * Tableau des identifiants de versions.
     *
     * @var int[]
     */
    protected $versions = [];

    /**
     * Connection à la bdd.
     *
     * @var MyPDO
     */
    protected $db = null;

    /**
     * Module de traduction.
     *
     * @param string $locale Identifiant de la langue
     * @param int    $idApi  Id API
     * @param \PDO   $db     Accès à la bdd
     */
    public function __construct($locale, $idApi, $db)
    {
        $this->setLocale($locale);
        $this->setApi($idApi);
        $this->db = $db;

        self::$self = $this;
    }

    /**
     * Traduit un message.
     *
     * @param string $message Message à traduire
     *
     * @return string message traduit
     *
     * @throws Exception\Lib Si aucune instance de TranslateMysql n'est active
     */
    public static function trad($message)
    {
        if (empty(self::$self)) {
            throw new Exception\Lib('Aucune traduction activée');
        }

        return self::$self->translate($message);
    }

    /**
     * Traduit un message.
     *
     * @param string $string Message à traduire
     * @param string $aide   Aide supplémentaire
     *
     * @return string message traduit
     */
    public function translate($string, $aide = '')
    {
        $stringSha = hash('sha256', $string);
        if (isset($this->translate[$this->locale][$stringSha])) {
            return $this->translate[$this->locale][$stringSha];
        }

        if (count($this->versions) == 0) {
            $query = 'SELECT id FROM version '
                   . 'WHERE id_api =' . $this->api;
            $this->versions = $this->db->query($query)->fetchAll(
                \PDO::FETCH_COLUMN
            );
        }

        foreach ($this->versions as $versionId) {
            $query = 'INSERT IGNORE INTO traduction SET'
                   . ' `cle_sha` =  ' . $this->db->quote($stringSha) . ','
                   . ' id_version =  ' . $versionId . ','
                   . ' id_api =  ' . $this->api . ','
                   . ' cle = ' . $this->db->quote($string) . ','
                   . ' valeur = ' . $this->db->quote($string) . ','
                   . ' aide = ' . $this->db->quote($aide);
            $this->db->exec($query);
        }

        $this->translate[$this->locale][$string] = $string;

        return $string;
    }

    /**
     * Choix de la langue utilisée.
     *
     * @param string $locale Identifiant de la langue
     *
     * @return void
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Choix de l'api utilisée.
     *
     * @param string $idApi Id Api
     *
     * @return void
     */
    public function setApi($idApi)
    {
        $this->api = $idApi;
    }

    /**
     * charge les translations de la base.
     *
     * @return void
     */
    public function addTranslation()
    {
        $this->loadTranslationData($this->locale);
    }

    /**
     * Chargement des traductions d'une version donnée.
     *
     * @param int $locale Identifiant de la version
     *
     * @return void
     */
    protected function loadTranslationData($locale)
    {
        $query = 'SELECT cle_sha, valeur'
               . ' FROM traduction'
               . ' WHERE id_api = ' . $this->api
               . ' AND id_version = ' . $locale;
        $this->translate[$locale] = $this->db->query($query)->fetchAll(
            \PDO::FETCH_UNIQUE | \PDO::FETCH_COLUMN
        );
    }
}
