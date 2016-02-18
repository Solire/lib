<?php
/**
 * Formatage de chaines de caractères.
 *
 * @author  smonnot <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\Format;

/**
 * Formatage de chaines de caractères.
 *
 * @author  smonnot <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class String
{
    const RANDOM_ALL = 1;
    const RANDOM_NUMERIC = 2;
    const RANDOM_ALPHA = 3;
    const RANDOM_ALPHALOWER = 4;
    const RANDOM_ALPHAUPPER = 5;

    /**
     * Tableau de translitération linguistique.
     *
     * @var array
     */
    protected static $charMap = [
        // Latin
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A',
        'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE', 'Ç' => 'C',
        'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'Ð' => 'D', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O',
        'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ő' => 'O',
        'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U',
        'Ü' => 'U', 'Ű' => 'U', 'Ý' => 'Y', 'Þ' => 'TH',
        'ß' => 'ss',
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a',
        'ä' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ð' => 'd', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o',
        'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ő' => 'o',
        'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u',
        'ü' => 'u', 'ű' => 'u', 'ý' => 'y', 'þ' => 'th',
        'ÿ' => 'y',
        // Latin symbols
        '©' => '(c)',
        // Greek
        'Α' => 'A', 'Β' => 'B', 'Γ' => 'G', 'Δ' => 'D',
        'Ε' => 'E', 'Ζ' => 'Z', 'Η' => 'H', 'Θ' => '8',
        'Ι' => 'I', 'Κ' => 'K', 'Λ' => 'L', 'Μ' => 'M',
        'Ν' => 'N', 'Ξ' => '3', 'Ο' => 'O', 'Π' => 'P',
        'Ρ' => 'R', 'Σ' => 'S', 'Τ' => 'T', 'Υ' => 'Y',
        'Φ' => 'F', 'Χ' => 'X', 'Ψ' => 'PS', 'Ω' => 'W',
        'Ά' => 'A', 'Έ' => 'E', 'Ί' => 'I', 'Ό' => 'O',
        'Ύ' => 'Y', 'Ή' => 'H', 'Ώ' => 'W', 'Ϊ' => 'I',
        'Ϋ' => 'Y',
        'α' => 'a', 'β' => 'b', 'γ' => 'g', 'δ' => 'd',
        'ε' => 'e', 'ζ' => 'z', 'η' => 'h', 'θ' => '8',
        'ι' => 'i', 'κ' => 'k', 'λ' => 'l', 'μ' => 'm',
        'ν' => 'n', 'ξ' => '3', 'ο' => 'o', 'π' => 'p',
        'ρ' => 'r', 'σ' => 's', 'τ' => 't', 'υ' => 'y',
        'φ' => 'f', 'χ' => 'x', 'ψ' => 'ps', 'ω' => 'w',
        'ά' => 'a', 'έ' => 'e', 'ί' => 'i', 'ό' => 'o',
        'ύ' => 'y', 'ή' => 'h', 'ώ' => 'w', 'ς' => 's',
        'ϊ' => 'i', 'ΰ' => 'y', 'ϋ' => 'y', 'ΐ' => 'i',
        // Turkish
        'Ş' => 'S', 'İ' => 'I', 'Ç' => 'C', 'Ü' => 'U',
        'Ö' => 'O', 'Ğ' => 'G',
        'ş' => 's', 'ı' => 'i', 'ç' => 'c', 'ü' => 'u',
        'ö' => 'o', 'ğ' => 'g',
        // Russian
        'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G',
        'Д' => 'D', 'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh',
        'З' => 'Z', 'И' => 'I', 'Й' => 'J', 'К' => 'K',
        'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
        'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T',
        'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
        'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sh', 'Ъ' => '',
        'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'Yu',
        'Я' => 'Ya',
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g',
        'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh',
        'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k',
        'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
        'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
        'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c',
        'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sh', 'ъ' => '',
        'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu',
        'я' => 'ya',
        // Ukrainian
        'Є' => 'Ye', 'І' => 'I', 'Ї' => 'Yi', 'Ґ' => 'G',
        'є' => 'ye', 'і' => 'i', 'ї' => 'yi', 'ґ' => 'g',
        // Czech
        'Č' => 'C', 'Ď' => 'D', 'Ě' => 'E', 'Ň' => 'N',
        'Ř' => 'R', 'Š' => 'S', 'Ť' => 'T', 'Ů' => 'U',
        'Ž' => 'Z',
        'č' => 'c', 'ď' => 'd', 'ě' => 'e', 'ň' => 'n',
        'ř' => 'r', 'š' => 's', 'ť' => 't', 'ů' => 'u',
        'ž' => 'z',
        // Polish
        'Ą' => 'A', 'Ć' => 'C', 'Ę' => 'e', 'Ł' => 'L',
        'Ń' => 'N', 'Ó' => 'o', 'Ś' => 'S', 'Ź' => 'Z',
        'Ż' => 'Z',
        'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l',
        'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ź' => 'z',
        'ż' => 'z',
        // Latvian
        'Ā' => 'A', 'Č' => 'C', 'Ē' => 'E', 'Ģ' => 'G',
        'Ī' => 'i', 'Ķ' => 'k', 'Ļ' => 'L', 'Ņ' => 'N',
        'Š' => 'S', 'Ū' => 'u', 'Ž' => 'Z',
        'ā' => 'a', 'č' => 'c', 'ē' => 'e', 'ģ' => 'g',
        'ī' => 'i', 'ķ' => 'k', 'ļ' => 'l', 'ņ' => 'n',
        'š' => 's', 'ū' => 'u', 'ž' => 'z',
        // Japanese
        'Ā' => 'A', 'Ū' => 'U', 'Ē' => 'E', 'Ō' => 'O',
        'ā' => 'a', 'ū' => 'u', 'ē' => 'e', 'ō' => 'o',
    ];

    /**
     * Renvoie une chaîne de n ($strLen) caractères aléatoirement.
     *
     * @param int $strLen Longueur de la chaîne
     * @param int $type   [optional] <p>
     *                    Type de caractères utilisés dans la chaîne générée. Doit être
     *                    une des constantes the String::RANDOM_*.
     *                    </p>
     *
     * @return string
     *
     * @deprecated deprecated since version 6.0
     */
    public static function random($strLen, $type = self::RANDOM_ALL)
    {
        $string = '';
        switch ($type) {
            case self::RANDOM_NUMERIC:
                $chaine = '0123456789';
                break;

            case self::RANDOM_ALPHA:
                $chaine = 'abcdefghijklmnopqrstuvwxyz'
                        . 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;

            case self::RANDOM_ALPHALOWER:
                $chaine = 'abcdefghijklmnopqrstuvwxyz';
                break;

            case self::RANDOM_ALPHAUPPER:
                $chaine = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;

            default:
                $chaine = 'abcdefghijklmnopqrstuvwxyz'
                        . 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
                        . '0123456789';
                break;
        }

        srand((double) microtime() * 1000000);
        for ($i = 0; $i < $strLen; $i++) {
            $string .= $chaine[rand() % strlen($chaine)];
        }

        return $string;
    }

    /**
     * Construit une url propre à partir d'une chaine de caractere,.
     *
     * @param string $string          La chaine de base à transformer.
     * @param string $charReplacement Caractere de remplacement.
     *
     * @return string La chaine transfomée.
     *
     * @deprecated since version 4.1.0
     */
    public static function friendlyURL($string, $charReplacement = '-')
    {
        $string = preg_replace('`\[.*\]`U', '', $string);
        $string = preg_replace('`&(amp;)?#?[a-z0-9]+;`i', $charReplacement, $string);
        $string = htmlentities($string, ENT_COMPAT, 'utf-8');
        $replace = '`&([a-z])?(acute|uml|circ|grave|ring|cedil|slash|tilde|caron|lig|quot|rsquo);`i';
        $string = preg_replace($replace, '\\1', $string);
        $string = preg_replace(
            ['`[^a-z0-9]`i', '`[' . $charReplacement . ']+`'],
            $charReplacement,
            $string
        );

        return strtolower(trim($string, $charReplacement));
    }

    /**
     * Remplace les lettres accentués par les lettres sans accents.
     *
     * @param string $str Chaine à éditer
     *
     * @return string
     */
    public static function replaceAccent($str)
    {
        /*
         * On transforme les caractères spéciaux en caractères simples
         */
        $str = str_replace(array_keys(self::$charMap), self::$charMap, $str);

        return $str;
    }

    /**
     * Construit une url propre à partir d'une chaine de caractere.
     *
     * @param string $str       Chaine à transformer
     * @param string $delimiter Délimiteur
     * @param int    $limit     Nombre de caractères maximum
     *
     * @return string
     */
    public static function urlSlug($str, $delimiter = '-', $limit = null)
    {
        $str = self::replaceAccent($str);

        /*
         * On remplace tous les caractères non alpha numériques par le délimiteur
         */
        $str = preg_replace('/[^\p{L}\p{Nd}]+/u', $delimiter, $str);

        /*
         * On supprime les délimiteurs en double
         */
        $str = preg_replace('/(' . preg_quote($delimiter, '/') . '){2,}/', '$1', $str);

        /*
         * Si on a une limite, on coupe la chaine
         */
        if ($limit !== null) {
            $str = mb_substr($str, 0, $limit, 'UTF-8');
        }

        /*
         * On supprime les délimiteurs en debut et en fin de chaîne
         */
        $str = trim($str, $delimiter);

        /*
         * On met en minuscule la chaine de caractère
         */
        $str = mb_strtolower($str, 'UTF-8');

        return $str;
    }

    /**
     * Coupe une chaîne de caractères à N caractères.
     *
     * @param string $string Chaine à couper
     * @param string $length Longueur maximum
     *
     * @return string
     */
    public static function cut($string, $length, $ellipsis = true)
    {
        mb_internal_encoding('UTF-8');
        if ($length && mb_strlen($string) > $length) {
            $str = $string;
            $str = mb_substr($str, 0, $length);
            $pos = mb_strrpos($str, ' ');

            $str = mb_substr($str, 0, $pos);

            if ($ellipsis) {
                $str .= '&hellip;';
            }

            return $str;
        }

        return $string;
    }
}
