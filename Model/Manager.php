<?php
/**
 * Manager
 *
 * @author  Thomas <thansen@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\Model;

use Solire\Lib\Registry;
use Solire\Lib\MyPDO;

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
     * @var MyPDO
     */
    protected $db;

    /**
     * Initialisation du manager
     *
     * @param MyPDO $db Accès à la bdd
     */
    public function __construct(MyPDO $db = null)
    {
        if ($db) {
            $this->db = $db;
        } else {
            $this->db = Registry::get('db');
        }
    }
}
