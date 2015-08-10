<?php
namespace Solire\Lib\Security\Handler;

/**
 * Base Handler class providing the Handler structure
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license MIT http://mit-license.org/
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
    public function __construct(Conf $conf)
    {
        $this->conf = $conf;
    }

    /**
     * Return the total of fail
     *
     * @return int Total number
     */
    abstract public function countFailed();
}
