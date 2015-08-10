<?php
namespace Solire\Lib\Security;

use Solire\Lib\MyPDO;

/**
 * Gestionnaire de blocages des attacks par bruteforce
 * (Privilégié fail2ban sur les serveurs dédiés)
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class AntiBruteforce
{
    /**
     * The configuration
     *
     * @var Conf
     */
    protected $conf = null;

    /**
     * The database connection
     *
     * @var MyPDO
     */
    protected $connection = null;

    /**
     * Constructor
     *
     * @param Conf  $conf       The configuration
     * @param MyPDO $connection The database connection
     *
     */
    public function __construct(Conf $conf)
    {
        $this->conf       = $conf;
        $this->connection = Registry::get('db');

        $this->checkFilters($ip);
    }

    protected function checkFilters($ip)
    {
        // Loop on defined filters
        foreach ($this->conf['filter'] as $filterName => $filter) {
            if ($filter['enabled']) {
                $typeHandler = $filter['enabled'];
                $countFailed = 0;
                foreach ($filter['log'] as $configName => $handlerConfig) {
                    $handlerClassname = 'Handler\\' . $handlerConfig['handler'];
                    $handler = new $handlerClassname($handlerConfig);
                    $countFailed += $handler->countFailed($ip, $filter['findtime']);

                    // Limit reached
                    if ($countFailed >= $filter['maxretry']) {
                        $this->blockIp($ip);
                        return false;
                    }
                }
            }
        }
    }

    public function isBlocking($ip)
    {
        // IP + TIME
        $query = 'SELECT COUNT(*) FROM so_fail2ban'
            . ' WHERE ip = '  . $ip
            . '     AND endDate >= NOW()';
//        $this->connection->
    }

    /**
     *
     * @param type $ip
     *
     * @todo gérer des handlers différents pour le stockage des ips
     * bloquées, un peu comme les handlers pour la lecture des logs
     */
    protected function blockIp($ip)
    {
        $banTime = '?';

        $date = date("Y-m-d H:i:s", time() + $banTime);

        // Insertion de l'ip dans la table de ban
        $query = 'INSERT INTO so_fail2ban'
            . ' SET ip = ' . $this->connection->quote($ip) .', '
            . '   endDate = ' . $this->connection->quote($date);

        return $this->connection->exec($query);
    }
}
