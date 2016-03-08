<?php

namespace Solire\Lib\Security\Util;

use Solire\Lib\Security\Util\Exception\InvalidRandomRangeException;

/**
 * Generate secure random string.
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
final class SecureRandom
{
    const RANDOM_NUMERIC = 1;
    const RANDOM_ALPHAUPPER = 2;
    const RANDOM_ALPHALOWER = 4;
    const RANDOM_SYMBOL = 8;
    const RANDOM_ALL = 15;

    /**
     * Generate a random string.
     *
     * @param int $length Length of random string
     * @param int $type   Range type to generate random string
     *
     * @return null|string
     *
     * @throws InvalidRandomRangeException
     */
    public function generate($length, $type = self::RANDOM_ALL)
    {
        $range = null;
        $randomString = null;

        if ($type & self::RANDOM_NUMERIC) {
            $range .= '0123456789';
        }

        if ($type & self::RANDOM_ALPHAUPPER) {
            $range .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }

        if ($type & self::RANDOM_ALPHALOWER) {
            $range .= 'abcdefghijklmnopqrstuvwxyz';
        }

        if ($type & self::RANDOM_SYMBOL) {
            $range .= '~!@#$%^&*(){}[],./?';
        }

        if ($range == null || mb_strlen($range) <= 1) {
            throw new InvalidRandomRangeException();
        }

        srand((double) microtime() * 1000000);
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $range[rand() % mb_strlen($range)];
        }

        return $randomString;
    }
}
