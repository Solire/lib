<?php

namespace Solire\Lib\Symfony\Form;

use Doctrine\Common\Persistence\ManagerRegistry as ManagerRegistryInterface;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;

/**
 * Description of ManagerRegistry
 *
 * @author thansen
 */
class ManagerRegistry implements ManagerRegistryInterface
{
    /**
     *
     * @var EntityManager[]
     */
    private $managers;

    /**
     *
     * @var Connection[]
     */
    private $connections;

    public function __construct(EntityManager $manager, Connection $connection)
    {
        $this->managers['base'] = $manager;
        $this->connections['base'] = $connection;
    }

    public function getAliasNamespace($alias)
    {
        return $this->managers['base']->getConfiguration()->getEntityNamespace($alias);
    }

    public function getConnection($name = null)
    {
        return $this->connections[$name];
    }

    public function getConnectionNames()
    {
        return ['base'];
    }

    public function getConnections()
    {
        return $this->connections;
    }

    public function getDefaultConnectionName()
    {
        return 'base';
    }

    public function getDefaultManagerName()
    {
        return 'base';
    }

    public function getManager($name = null)
    {
        return $this->managers[$name];
    }

    public function getManagerForClass($class)
    {
        return $this->managers['base'];
    }

    public function getManagerNames()
    {
        return ['base'];
    }

    public function getManagers()
    {
        return $this->managers;
    }

    public function getRepository($persistentObject, $persistentManagerName = null)
    {
        return $this->getManager($persistentManagerName)->getRepository($persistentObject);
    }

    public function resetManager($name = null)
    {
        $this->managers[$name] = null;
    }
}
