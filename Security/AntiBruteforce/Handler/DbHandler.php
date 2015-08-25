<?php

namespace Solire\Lib\Security\AntiBruteforce\Handler;

use Solire\Lib\Registry;

/**
 * DB Handler class providing the Handler structure
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class DbHandler extends AbstractHandler
{
    /**
     * The database connection
     *
     * @var MyPDO
     */
    protected $connection = null;

    /**
     * {@inheritdoc}
     */
    public function __construct($conf)
    {
        $this->connection = Registry::get('db');
        parent::__construct($conf);
    }

    /**
     * {@inheritdoc}
     */
    public function countFailed($ip, $findTime)
    {
        $where = [];
        foreach ($this->conf['failregex'] as $regex) {
            $where[] = $this->conf['search-column']
                . ' LIKE ' . $this->connection->quote($regex);
        }

        $dateTimeC = $this->conf['datetime-column'];
        $ipC       = $this->conf['ip-column'];

        $query = 'SELECT COUNT(*) FROM ' . $this->conf['table']
            . ' WHERE (' . implode(' OR ', $where) . ')'
            . '   AND ' . $dateTimeC . ' >= NOW()-INTERVAL ' . ((int) $findTime) . ' SECOND'
            . '   AND ' . $ipC . ' = ' . $this->connection->quote($ip);

        return $this->connection->query($query)->fetchColumn();
    }
}
