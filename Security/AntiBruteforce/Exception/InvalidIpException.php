<?php
/**
 * Exception class quand une ip n'est pas valide
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\Security\AntiBruteforce\Exception;

/**
 * Exception class quand une ip n'est pas valide
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class InvalidIpException extends \Exception
{
    /**
     * {@inheritdoc}
     *
     * @param string     $message  [optional] The Exception message to throw.
     * @param int        $code     [optional] The Exception code.
     * @param \Exception $previous [optional] The previous exception used for the exception chaining. Since 5.3.0
     */
    public function __construct($message = '', $code = 0, $previous = null)
    {
        if ($message == '') {
            $message = 'Invalid Ip';
        }

        parent::__construct((string) $message, (int) $code, $previous);
    }
}
