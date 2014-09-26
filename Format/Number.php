<?php
/**
 * Formatage des nombres
 *
 * @author  smonnot <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\Format;

/**
 * Formatage des nombres
 *
 * @author  smonnot <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Number
{
    /**
     * Formate un prix pour un affichage
     *
     * @param float  $price    Prix à afficher
     * @param bool   $show     Afficher ou non le ',00'
     * @param string $currency Nom de la monnaie utilisée
     *
     * @return string
     */
    public static function money($price, $show = true, $currency = '')
    {
        $price = number_format($price, 2, ',', ' ');

        if ($show === false) {
            $price = str_replace(',00', '', $price);
        }
        return $price . $currency;
    }

    /**
     * Formate un float avec des zéros
     *
     * @param float $number Float à formater
     * @param int   $nbZero Nombre de zero
     *
     * @return string
     */
    public static function zeroFill($number, $nbZero = 11)
    {
        return printf('%0' . $nbZero . 'd', $number);
    }

    /**
     * Formatage d'une taille en octet en Ko, Mo
     *
     * @param int $size Taille à reformater en octet
     *
     * @return string
     */
    public static function formatSize($size)
    {
        $strTmp = '';

        if (preg_match('`^[0-9]{1,}$`', $size)) {
            if ($size >= 1000000) {
                /**
                 * Taille supérieur à 1 MegaOctet
                 */
                $strTmp = sprintf('%01.2f', $size / 1000000);

                /**
                 * Suppression des '0' en fin de chaine
                 */
                $strTmp = preg_replace('`[\.]{1}[0]{1,}$`', '', $strTmp) . ' Mo';
            } elseif ($size >= 1000) {
                /**
                 * Taille inférieur à 1 MegaOctet
                 */
                $strTmp = sprintf('%01.2f', $size / 1000);

                /**
                 * Suppression des '0' en fin de chaine
                 */
                $strTmp = preg_replace('`[\.]{1}[0]{1,}$`', '', $strTmp) . ' Ko';
            } elseif ($size >= 0) {
                /**
                 * Taille inférieur à  1 KiloOctet
                 */
                $strTmp = $size . ' octect';
                if ($size > 0) {
                    $strTmp .= 's';
                }
            } else {
                $strTmp = $size;
            }
        } else {
            $strTmp = $size;
        }

        return $strTmp;
    }
}
