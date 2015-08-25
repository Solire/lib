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
        $clientIp = null;
        if ($_SERVER['HTTP_CLIENT_IP']) {
            $clientIp = $_SERVER['HTTP_CLIENT_IP'];
        } elseif ($_SERVER['HTTP_X_FORWARDED_FOR']) {
            $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif ($_SERVER['HTTP_X_FORWARDED']) {
            $clientIp = $_SERVER['HTTP_X_FORWARDED'];
        } elseif ($_SERVER['HTTP_FORWARDED_FOR']) {
            $clientIp = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif ($_SERVER['HTTP_FORWARDED']) {
            $clientIp = $_SERVER['HTTP_FORWARDED'];
        } elseif ($_SERVER['REMOTE_ADDR']) {
            $clientIp = $_SERVER['REMOTE_ADDR'];
        } else {
            $clientIp = 'unknown';
        }

        return $clientIp;
    }
}
