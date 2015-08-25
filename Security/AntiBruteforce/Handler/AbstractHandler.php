<?php
namespace Solire\Lib\Security\AntiBruteforce\Handler;

use Solire\Conf\Conf;

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
     * @var Conf|array
     */
    protected $conf = null;

    /**
     * Construct
     *
     * @param Conf|array $conf Antibruteforce configuration
     */
    public function __construct($conf)
    {
        $this->conf = $conf;
    }

    /**
     * Count failed
     *
     * @param string $ip       Remote URI
     * @param string $findTime Find time in seconds
     *
     * @return int Total number
     */
    abstract public function countFailed($ip, $findTime);
}
