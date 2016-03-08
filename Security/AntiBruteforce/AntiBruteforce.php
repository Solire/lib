<?php

namespace Solire\Lib\Security\AntiBruteforce;

use Solire\Conf\Conf;
use Solire\Lib\MyPDO;
use Solire\Lib\Registry;
use Solire\Lib\Security\AntiBruteforce\Exception\InvalidIpException;
use Solire\Lib\Security\AntiBruteforce\Handler\AbstractHandler;

/**
 * Gestionnaire de blocages des attacks par bruteforce
 * (Privilégié fail2ban sur les serveurs dédiés).
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class AntiBruteforce
{
    /**
     * La configuration.
     *
     * @var Conf|array[]
     */
    protected $conf = null;

    /**
     * L'ip courante testée.
     *
     * @var string
     */
    protected $ip = null;

    /**
     * La connexion à la base de données.
     *
     * @var MyPDO
     */
    protected $connection = null;

    /**
     * Construct.
     *
     * @param Conf|array[] $conf La configuration
     * @param null|string  $ip   L'ip à tester, si null, l'ip du client
     *
     * @throws InvalidIpException
     */
    public function __construct($conf, $ip = null)
    {
        $this->conf = $conf;
        $this->connection = Registry::get('db');

        if ($ip === null) {
            $ip = Registry::get('request')->getClientIp();
        }

        if (!$this->isBlocking($ip)) {
            $this->checkFilters($ip);
        }
    }

    /**
     * Cherche en fonction des filtres, les tentatives dans l'historique et si
     * le nombre de tentatives max est atteint, bloque l'ip.
     *
     * @param string $ip L'ip a testé
     *
     * @return bool False en cas de blockage
     *
     * @throws InvalidIpException Ip invalide
     */
    protected function checkFilters($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            throw new InvalidIpException();
        }

        $this->ip = $ip;

        // Liste blanche des IPs à ne pas bloquer
        if (isset($this->conf['ignoreip'])
            && in_array($this->ip, (array) $this->conf['ignoreip'])
        ) {
            return true;
        }

        // On boucle sur les filtres définis
        foreach ($this->conf['filter'] as $filterName => $filter) {
            if ($filter['enabled']) {
                $countFailed = 0;
                foreach ($filter['log'] as $configName => $handlerConfig) {
                    $handlerClassname = 'Solire\\Lib\\Security\\AntiBruteforce\\Handler\\'
                        . $handlerConfig['handler'] . 'Handler';
                    /** @var AbstractHandler $handler */
                    $handler = new $handlerClassname($handlerConfig);
                    $countFailed += $handler->countFailed($ip, $filter['findtime']);

                    // Limite atteinte
                    if ($countFailed >= $filter['maxretry']) {
                        $this->blockIp($this->ip, $this->conf['bantime']);

                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Teste si une IP est bloquée ou non.
     *
     * @param string $ip L'ip a testé
     *
     * @return bool True si l'ip est bloquée
     *
     * @throws InvalidIpException Ip invalide
     */
    public function isBlocking($ip = null)
    {
        $blocked = true;

        if ($ip != null) {
            $this->ip = $ip;
        }

        if (filter_var($this->ip, FILTER_VALIDATE_IP) === false) {
            throw new InvalidIpException();
        }

        $query = 'SELECT COUNT(*) FROM so_fail2ban'
            . ' WHERE ip = ' . $this->connection->quote($this->ip)
            . '     AND endDate >= NOW()';

        $statement = $this->connection->query($query);
        if ($statement !== false) {
            $result = $statement->fetchColumn();
            $blocked = $result == 0 ? false : true;
        }

        return $blocked;
    }

    /**
     * Renvoi le nombre de secondes à attendre lors d'un blockage.
     *
     * @param string $ip L'ip a testé
     *
     * @return int Nombre de secondes
     *
     * @throws InvalidIpException Remote ip invalide
     */
    public function unblockRemainingTime($ip = null)
    {
        if ($ip != null) {
            $this->ip = $ip;
        }

        if (filter_var($this->ip, FILTER_VALIDATE_IP) === false) {
            throw new InvalidIpException();
        }

        $query = 'SELECT TIMESTAMPDIFF(SECOND, NOW(), MAX(endDate))'
            . ' FROM so_fail2ban'
            . ' WHERE ip = ' . $this->connection->quote($this->ip)
            . '     AND endDate >= NOW()';

        $statement = $this->connection->query($query);
        $result = $statement->fetchColumn();

        return $result;
    }

    /**
     * Permet de blocker une ip pendant un temps donné.
     *
     * @param string $ip      Ip à bloquer
     * @param int    $banTime Temps de bannissement en secondes
     *
     * @return bool True si la requête s'est bien exécutée
     *
     * @todo gérer des handlers différents pour le stockage des ips
     * bloquées, un peu comme les handlers pour la lecture des logs
     */
    protected function blockIp($ip, $banTime)
    {
        $date = date('Y-m-d H:i:s', time() + $banTime);

        // Insertion de l'ip dans la table de ban
        $query = 'INSERT INTO so_fail2ban'
            . ' SET ip = ' . $this->connection->quote($ip) . ', '
            . '   endDate = ' . $this->connection->quote($date);

        return $this->connection->exec($query);
    }
}
