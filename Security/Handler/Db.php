<?php
namespace Solire\Lib\Security\Handler;

use Solire\Lib\Registry;

/**
 * DB Handler class providing the Handler structure
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license MIT http://mit-license.org/
 */
class Db extends AbstractHandler
{
    /**
     * The database connection
     *
     * @var MyPDO
     */
    protected $connection = null;

    public function __construct()
    {
        $this->connection = Registry::get('db');
    }

    protected function countFailed($ip, $findTime)
    {
        $where = [];
        foreach ($this->conf['failregex'] as $regex) {
            $where[] = $this->conf['search-column'] . 'LIKE' . $db->quote($regex);
        }

        $query = 'SELECT COUNT(*) FROM ' . $this->conf['table']
            . ' WHERE ' . implode(' OR ', $where)
            . '     AND ' . $this->conf['datetime-column'] . ' >= DATE(NOW()-INTERVAL ' . (int) $findTime . ' SECOND)'
            . '     AND ' . $this->conf['ip-column'] . ' = ' . $db->quote($ip);

        return $this->connection->query($query)->fetchColumn();
    }
}
