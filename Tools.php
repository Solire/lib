<?php
/**
 * Tools
 *
 * @author  smonnot <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib;

/**
 * Tools
 *
 * @author  smonnot <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Tools
{
    /**
     * Coupe une chaine à une longueur donnée
     *
     * @param string $text       Chaine à couper
     * @param int    $nbCharsMax Longueur maximal
     *
     * @return string
     */
    public static function cut($text, $nbCharsMax)
    {
        return mb_strlen($text, 'UTF-8') > $nbCharsMax ? mb_substr($text, 0, $nbCharsMax, 'UTF-8') . '...' : $text;
    }

    /**
     * Formate un poids en octet
     *
     * @param int $valeur Poids en octet
     *
     * @return string
     */
    public static function formatTaille($valeur)
    {
        $strTmp = '';

        if (preg_match('#^[0-9]{1,}$#', $valeur)) {
            if ($valeur >= 1000000) {
                // Taille supérieur à  1 MegaOctet
                $strTmp = sprintf('%01.2f', $valeur / 1000000);
                // Suppression des "0" en fin de chaine
                $strTmp = preg_replace('#[\.]{1}[0]{1,}$#', '', $strTmp) . ' Mo';
            } elseif ($valeur >= 1000) {
                // Taille inférieur à  1 MegaOctet
                $strTmp = sprintf('%01.2f', $valeur / 1000);
                // Suppression des "0" en fin de chaine
                $strTmp = preg_replace('#[\.]{1}[0]{1,}$#', '', $strTmp) . ' Ko';
            } elseif ($valeur >= 0) {
                // Taille inférieur à  1 KiloOctet
                $strTmp = $valeur . ' octect';
                if ($valeur > 0) {
                    $strTmp .= 's';
                }
            } else {
                $strTmp = $valeur;
            }
        } else {
            $strTmp = $valeur;
        }

        return $strTmp;
    }

    /**
     * Recherche dans un array multidim
     *
     * @param array $parents  Tableau dans lequel faire la recherche
     * @param array $searched Valeur recherché
     *
     * @return boolean
     */
    public static function multidimensionalSearch($parents, $searched)
    {
        if (empty($searched) || empty($parents)) {
            return false;
        }

        foreach ($parents as $key => $value) {
            $exists = true;
            foreach ($searched as $skey => $svalue) {
                $exists = ($exists && isset($parents[$key][$skey]) && $parents[$key][$skey] == $svalue);
            }
            if ($exists) {
                return $key;
            }
        }

        return false;
    }

    /**
     * Remplace les accents par des lettres sans accents
     *
     * @param string $chaine Chaine à vider de ses accents
     *
     * @return string
     */
    public static function regexAccents($chaine)
    {
        mb_internal_encoding('UTF-8');
        mb_regex_encoding('UTF-8');
        $accent = ['a', 'à', 'á', 'â', 'ã', 'ä', 'å', 'c', 'ç', 'e', 'è', 'é',
            'ê', 'ë', 'i', 'ì', 'í', 'î', 'ï', 'o', 'ð', 'ò', 'ó', 'ô', 'õ',
            'ö', 'u', 'ù', 'ú', 'û', 'ü', 'y', 'ý', 'ý', 'ÿ'];
        $inter = ['%01', '%02', '%03', '%04', '%05', '%06', '%07', '%08', '%09',
            '%10', '%11', '%12', '%13', '%14', '%15', '%16', '%17', '%18',
            '%19', '%20', '%21', '%22', '%23', '%24', '%25', '%26', '%27',
            '%28', '%29', '%30', '%31', '%32', '%33', '%34', '%35'];
        $regex = ['[aàáâãäå]', '[aàáâãäå]', '[aàáâãäå]', '[aàáâãäå]', '[aàáâãäå]',
            '[aàáâãäå]', '[aàáâãäå]', '[cç]', '[cç]', '[eèéêë]', '[eèéêë]',
            '[eèéêë]', '[eèéêë]', '[eèéêë]', '[iìíîï]', '[iìíîï]', '[iìíîï]',
            '[iìíîï]', '[iìíîï]', '[oðòóôõö]', '[oðòóôõö]', '[oðòóôõö]',
            '[oðòóôõö]', '[oðòóôõö]', '[oðòóôõö]', '[oðòóôõö]', '[uùúûü]',
            '[uùúûü]', '[uùúûü]', '[uùúûü]', '[yýýÿ]', '[yýýÿ]', '[yýýÿ]', '[yýýÿ]'];
        $chaine = str_ireplace($accent, $inter, $chaine);
        $chaine = str_replace($inter, $regex, $chaine);
        return $chaine;
    }

    /**
     * Documentation à faire
     *
     * @param type   $chaine   Chaîne
     * @param string $keywords Mots clés
     *
     * @return type
     * @todo Documenter
     */
    public static function highlightedSearch($chaine, $keywords)
    {
        mb_internal_encoding('UTF-8');
        mb_regex_encoding('UTF-8');
        for ($Z = 0; $Z < count($keywords); $Z++) {
            if (str_replace(' ', '', $keywords) != '') {
                $keywords[$Z] = '#('
                    . self::regexAccents(
                        str_replace(array('<¤>', '</¤>'), '', $keywords[$Z])
                    )
                    . ')#iu'
                ;
            } else {
                array_splice($keywords, $Z, 1);
                $Z--;
            }
        }
        if (is_array($keywords) && count($keywords) > 0) {
            $chaine = preg_replace($keywords, '<¤>$1</¤>', $chaine);
            $chaine = str_replace(array('<¤>', '</¤>'), array('<strong>', '</strong>'), $chaine);
        }
        return $chaine;
    }
}
