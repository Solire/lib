<?php
/**
 * Exception class quand une ip n'est pas valide
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\Security\Exception;

/**
 * Exception class quand une ip n'est pas valide
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class InvalidIpException extends \Exception
{
    public function __construct($message = '', $code = 0, $previous = null)
    {
        if ($message == '') {
            $message = 'Invalid Ip';
        }
        parent::__construct((string)$message, (int)$code, $previous);
    }
}
