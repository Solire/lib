<?php

namespace Solire\Lib\Doctrine;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Solire\Lib\DB;

/**
 * Classe instantiant un manager d'entité.
 *
 * @author  thansen <thansen@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Orm
{
    /**
     * Connection doctrine à la bdd.
     *
     * @var Connection
     */
    private $connection;

    /**
     * ORM configuration.
     *
     * @var Configuration
     */
    private $config;

    /**
     * Manager d'entité.
     *
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Constructeur.
     *
     * @param string $connectionName Nom de la connection
     * @param array  $entityDirs     Dossier contenant les fichiers de
     *                               configuration yaml
     * @param string $proxyDir       Dossier contenant les classes Proxy
     */
    public function __construct($connectionName, array $entityDirs, $proxyDir)
    {
        $this->connection = DriverManager::getConnection([
            'pdo' => DB::get($connectionName),
        ]);
        $this->config = Setup::createYAMLMetadataConfiguration($entityDirs, false, $proxyDir);
        $this->entityManager = EntityManager::create($this->connection, $this->config);
    }

    /**
     * Retourne le Manager d'entité.
     *
     * @return EntityManager
     */
    public function getEM()
    {
        return $this->entityManager;
    }

    /**
     * Retourne la Connection doctrine à la bdd.
     *
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }
}
