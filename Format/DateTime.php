<?php
/**
 * Formatage des dates et heures
 *
 * @author  smonnot <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\Format;

/**
 * Formatage des dates et heures
 *
 * @author  smonnot <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class DateTime
{
    /**
     *  Renvoi un temps relatif entre maintenant et la date en paramètre
     *
     * @param string|int $timestampOrDate Date à afficher
     * @param bool       $modeDate        ?
     *
     * @return string
     */
    public static function relativeTime1($timestampOrDate, $modeDate = false)
    {
        $periods = [
            'seconde',
            'minute',
            'heure',
            'jour',
            'semaine',
            'mois',
            'année',
        ];
        $lengths = [
            '60',
            '60',
            '24',
            '7',
            '4.35',
            '12',
        ];
        $difference = time() - $timestampOrDate;
        if ($difference >= 0) {
            /**
             * C'est dans le passé
             */
            $ending = 'il y a';
        } else {
            /**
             * C'est dans le futur
             */
            $difference = -$difference;
            $ending = 'dans';
        }

        /**
         * On recherche la plus grande période seconde, minute etc.
         */
        $j = 0;
        while (isset($lengths[$j]) && $difference >= $lengths[$j]) {
            $difference /= $lengths[$j];
            $j++;
        }
        $difference = round($difference);
        if ($difference != 1 && $periods[$j] != 'mois') {
            $periods[$j] .= 's';
        }
        $text = $ending . ' ' . $difference . ' ' . $periods[$j];
        return $text;
    }

    /**
     *  Renvoi un temps relatif entre maintenant et la date en paramètre
     *
     * @param string|int $timestampOrDate Date au format mysql ou timestamp
     * @param bool       $modeDate        Vrai si c'est une date mysql, faux
     * si c'est un timestamp
     *
     * @return string
     */
    public static function relativeTime($timestampOrDate, $modeDate = false)
    {
        /**
         * Tableau des noms des périodes
         */
        $periods = [
            'année',
            'mois',
            'jour',
            'heure',
            'minute',
            'seconde',
        ];

        /**
         * Tableau des attributs de la classe DateInterval
         * http://www.php.net/manual/fr/class.dateinterval.php
         */
        $periodsMember = [
            'y',
            'm',
            'd',
            'h',
            'i',
            's',
        ];

        $max = count($periodsMember);
        if ($modeDate) {
            /**
             * La date est nulle
             * On retourne null
             */
            if ($timestampOrDate == '') {
                return null;
            }

            $time = $timestampOrDate;
            if (strlen($timestampOrDate) == 10) {
                /**
                 * Si l'heure n'est pas précisé (H:i:s)
                 * on limite le résultat à un nombre de jours
                 */
                $max  = 3;
            }
        } else {
            $time = $timestampOrDate;
        }

        $d = new \DateTime($time);
        $n = new \DateTime();
        $difference = $n->diff($d);

        if ($difference->invert > 0) {
            /**
             * C'est dans le passé
             */
            $ending = 'il y a';
        } else {
            /**
             * C'est dans le futur
             */
            $ending = 'dans';
        }

        $ii = 0;
        do {
            /**
             * Nom de l'attribut de la classe DateInterval
             */
            $mb = $periodsMember[$ii];

            /**
             * Nombre d'occurence (nombre de jours, de mois etc.)
             */
            $nb = $difference->$mb;

            /**
             * Nom de la période en français
             */
            $pr = $periods[$ii];

            $ii++;
        } while ($nb == 0 && $ii < $max);

        /**
         * Si on obtient plus de 7 jours, on parle de semaines
         */
        if ($mb == 'd' && $nb > 7) {
            $pr = 'semaine';
            $nb = round($nb / 7);
        }

        if ($nb > 1 && $pr != 'mois') {
            $pr .= 's';
        }

        $text = $ending . ' ' . $nb . ' ' . $pr;
        return $text;
    }

    /**
     * Renvoi la date en français
     *
     * @param string $date        Date au format mysql
     * @param bool   $moiscomplet Vrai si on veut la version complète du mois
     * (janvier), faux si on veut seulement une abréviation (janv.)
     * @param bool   $jour        Vrai pour afficher le jour
     *
     * @return string
     */
    public static function toText($date, $moiscomplet = false, $jour = false)
    {
        if (substr($date, 0, 10) == '0000-00-00') {
            return '';
        }

        $ladate = '';

        if ($jour) {
            $timestamp = strtotime($date);
            $nbJour = date('w', $timestamp);

            $jours = [
                'dimanche',
                'lundi',
                'mardi',
                'mercredi',
                'jeudi',
                'vendredi',
                'samedi',
            ];

            $ladate .= $jours[$nbJour];
        }

        if ($moiscomplet) {
            $lesmois = [
                '',
                'janvier',
                'février',
                'mars',
                'avril',
                'mai',
                'juin',
                'juillet',
                'août',
                'septembre',
                'octobre',
                'novembre',
                'décembre'
            ];
        } else {
            $lesmois = [
                '',
                'janv.',
                'fév.',
                'mars',
                'avril',
                'mai',
                'juin',
                'juil.',
                'août',
                'sept.',
                'oct.',
                'nov.',
                'déc.'
            ];
        }

        $dateTab = explode('-', substr($date, 0, 10));
        $ladate .= (int) $dateTab[2] . ' '
                 . $lesmois[(int) $dateTab[1]] . ' '
                 . $dateTab[0];

        $d = strlen($date);
        if ($d > 10) {
            $heure   = substr($date, 11, 5);
            $ladate .= ' à ' . $heure;
        }

        return $ladate;
    }

    /**
     * Renvoi la date au format court
     *
     * @param string $datetime    Date au format mysql
     * @param bool   $moiscomplet Affichage du nom du mois en entier
     *
     * @return string
     */
    public static function toShortText($datetime, $moiscomplet = false)
    {
        if ($moiscomplet) {
            $lesmois = [
                '',
                'janvier',
                'février',
                'mars',
                'avril',
                'mai',
                'juin',
                'juillet',
                'août',
                'septembre',
                'octobre',
                'novembre',
                'décembre'
            ];
        } else {
            $lesmois = [
                '',
                'janv.',
                'fév.',
                'mars',
                'avril',
                'mai',
                'juin',
                'juil.',
                'août',
                'sept.',
                'oct.',
                'nov.',
                'déc.'
            ];
        }

        /**
         * On prend la partie date (année, mois et jour)
         */
        $datePart = substr($datetime, 0, 10);

        /**
         * On prend les heures et minutes mais pas les secondes
         */
        $timePart = substr($datetime, 11, 5);

        if ($datePart != date('Y-m-d')) {
            /**
             * Si ce n'est pas aujourd'hui, on précise la date
             */
            $date = explode('-', $datePart);
            $ladate = (int) $date[2] . ' ' . $lesmois[(int) $date[1]];

            if ($date[0] != date('Y')) {
                /**
                 * Si ce n'est pas la même année, on précise l'année
                 */
                $ladate .= ' ' . $date[0];
            }
        } else {
            /**
             * Si c'est aujourd'hui, on précise uniquement l'heure
             */
            $ladate = $timePart;
        }

        return $ladate;
    }

    /**
     * Transforme une date au format sql dans un autre format, format francais
     * jj/mm/yyyy par défaut
     *
     * @param string $dateSql Date au format sql
     * @param string $format  Format de sortie accepté par date()
     *
     * @return string
     *
     * @link http://php.net/manual/en/function.date.php documentaion pour
     * paramètre $format
     */
    public static function sqlTo($dateSql, $format = 'd/m/Y')
    {
        if (substr($dateSql, 0, 10) == '0000-00-00') {
            return null;
        }

        $date = new \DateTime($dateSql);
        return $date->format($format);
    }

    /**
     * Convertion d'une date du format français au format SQL
     *
     * @param string $dateFr    Date au format FR
     * @param string $delimiter Séparateur
     *
     * @return string
     * @throws \Solire\Lib\Exception\lib En cas d'erreur de format
     */
    public static function frToSql($dateFr, $delimiter = '/')
    {
        $sizeExpected = 8 + 2 * strlen($delimiter);
        if (strlen($dateFr) != $sizeExpected) {
            $format  = 'Wrong french date format %s';
            $message = sprintf($format, $dateFr);
            throw new \Solire\Lib\Exception\lib($message);
        }

        $dateArray = explode($delimiter, $dateFr);
        $dateArray = array_reverse($dateArray);
        $dateSql   = implode('-', $dateArray);
        unset($dateArray);

        return $dateSql;
    }
}
