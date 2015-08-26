<?php

namespace Solire\Lib\Security\AntiBruteforce\Handler;

use Solire\Conf\Conf;
use Solire\Lib\MyPDO;
use Solire\Lib\Registry;

/**
 * Monolog PDO Handler class
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class MonologPDOHandler extends AbstractHandler
{
    /**
     * The database connection
     *
     * @var MyPDO
     */
    protected $connection = null;

    /**
     * {@inheritdoc}
     *
     * @param Conf|array[] $conf Antibruteforce configuration
     */
    public function __construct($conf)
    {
        $this->connection = Registry::get('db');
        parent::__construct($conf);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $ip       Remote URI
     * @param string $findTime Find time in seconds
     *
     * @return int Total number
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

        /* Channel filter */
        if (isset($this->conf['channel-column'])
            && isset($this->conf['channel'])
        ) {
            $channelC  = $this->conf['channel-column'];
            $channel   = $this->conf['channel'];
            $query .= ' AND ' . $channelC . ' = ' . $this->connection->quote($channel);
        }

        return $this->connection->query($query)->fetchColumn();
    }
}
