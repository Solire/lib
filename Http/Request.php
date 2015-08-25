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
        if (isset($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP'] != null) {
            $clientIp = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != null) {
            $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED']) && $_SERVER['HTTP_X_FORWARDED'] != null) {
            $clientIp = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (isset($_SERVER['HTTP_FORWARDED_FOR']) && $_SERVER['HTTP_FORWARDED_FOR'] != null) {
            $clientIp = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_FORWARDED']) && $_SERVER['HTTP_FORWARDED'] != null) {
            $clientIp = $_SERVER['HTTP_FORWARDED'];
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] != null) {
            $clientIp = $_SERVER['REMOTE_ADDR'];
        } else {
            $clientIp = 'unknown';
        }

        return $clientIp;
    }
}
