<?php

namespace Solire\Lib\Http;

/**
 * Classe qui représente une requête HTTP
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Request
{
    /**
     * @var string|null Ip du client
     */
    protected $clientIp = null;

    /**
     * Retourne l'adresse IP du client
     *
     * @return string L'adresse IP du client
     */
    public function getClientIp()
    {
        if ($this->clientIp === null) {
            $this->clientIp = $this->getClientIps();
        }

        return $this->clientIp;
    }

    /**
     * Retourne l'adresse IP du client
     *
     * @return string L'adresse IP du client
     */
    protected function getClientIps()
    {
        $serverIpKeys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];

        $clientIp = 'unknown';
        foreach ($serverIpKeys as $serverIpKey) {
            if (isset($_SERVER[$serverIpKey]) && $_SERVER[$serverIpKey] != null) {
                $clientIp = $_SERVER[$serverIpKey];
            }
        }

        return $clientIp;
    }
}
