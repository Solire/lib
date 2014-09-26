<?php
/**
 * Manager
 *
 * @author  Thomas <thansen@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\Model;

use Solire\Lib\Registry;

/**
 * Manager
 *
 * @author  Thomas <thansen@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Manager
{

    /**
     *
     * @var Solire\Lib\MyPDO
     */
    protected $db;

    /**
     * Initialisation du manager
     *
     * @param Solire\Lib\MyPDO $db Accès à la bdd
     */
    public function __construct(Solire\Lib\MyPDO $db = null)
    {
        if ($db) {
            $this->db = $db;
        } else {
            $this->db = Registry::get('db');
        }
    }
}
