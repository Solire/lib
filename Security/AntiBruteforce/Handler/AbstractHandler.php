<?php
namespace Solire\Lib\Security\AntiBruteforce\Handler;

/**
 * Base Handler class providing the Handler structure
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
abstract class AbstractHandler
{
    /**
     * The configuration
     *
     * @var Conf
     */
    protected $conf = null;

    /**
     * Constructor
     *
     * @param Conf $conf The configuration
     * 
     */
    public function __construct($conf)
    {
        $this->conf = $conf;
    }

    /**
     * Return the total of fail
     *
     * @return int Total number
     */
    abstract public function countFailed($ip, $findTime);
}
