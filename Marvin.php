<?php
/**
 * Gestionnaire de rapport d'erreur Marvin
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib;

/**
 * Marvin est une methode de rapport d'erreur
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Marvin
{
    /**
     * Contrôle de l'affichage
     *
     * @var boolean
     */
    public static $display = true;

    /**
     * Chemin de configuration du fichier de configuration
     */
    const CONFIG_PATH = 'config/marvin.ini';

    /**
     * Génère un rapport d'alerte
     *
     * @param string    $title Titre du rapport
     * @param Exception $error Exception à exploiter
     *
     * @uses Config
     */
    public function __construct($title, $error)
    {
        $this->config = new Config(self::CONFIG_PATH);
        if (method_exists($error, 'getPrevious') && $error->getPrevious()) {
            $this->exc = $error->getPrevious();
        } else {
            $this->exc = $error;
        }
        $this->contact = $this->config->get('contact', 'mail');
        $this->headers = 'Content-type: text/html; charset=utf-8' . "\r\n"
                       . 'From: Marvin <marvin@solire.fr>' . "\r\n";

        /* = Couleurs :
          ------------------------------- */
        $colors = $this->config->get('color');
        foreach ($colors as $key => $value) {
            $this->{'color' . $key} = $value;
        }

        $this->title = $title;
        if (isset($_SERVER['SERVER_NAME'])) {
            $this->title = '[' . $_SERVER['SERVER_NAME'] . '] ' . $this->title;
        }

        $REQUEST = array_merge($_GET, $_POST, $_COOKIE, $_SERVER);

        /* = Chargement des données passées en paramètre de la page
          ------------------------------------------------- */
        if (!empty($REQUEST)) {
            foreach ($REQUEST as $key => $value) {
                $loc = array();

                if (isset($_GET[$key])) {
                    $loc[] = 'GET';
                }

                if (isset($_COOKIE[$key])) {
                    $loc[] = 'COOKIE';
                }

                if (isset($_POST[$key])) {
                    $loc[] = 'POST';
                }

                if (isset($_SERVER[$key])) {
                    $loc[] = 'SERVER';
                }

                $req = array();
                $req['loc'] = implode(' | ', $loc);
                $req['key'] = $key;
                $req['value'] = $this->varDump($value);
                $this->request[] = $req;
            }
        }

        /* = Mise en forme des données suplémentaires, si il y en a
          ------------------------------- */
        if (method_exists($error, 'getData')) {
            $data = $error->getData();
            foreach ($data as $key => $value) {
                $req = array();
                $req['key'] = $key;
                $req['value'] = $this->varDump($value);
                $this->data[] = $req;
            }
        }


        $traces = $this->exc->getTrace();
        foreach ($traces as $trace) {
            foreach ($trace['args'] as $key => $arg) {
                $trace['args'][$key] = $this->varDump($arg);
            }
            if (isset($trace['file'], $trace['line'])) {
                $trace['showFile'] = $this->readLines(
                    $trace['file'],
                    $trace['line']
                );
            }
            $this->trace[] = $trace;
        }
    }

    /**
     * Renvois la chaine contenant le var_dump() de la variable
     *
     * @param mixed $var Variable à afficher
     *
     * @return string
     */
    final public function varDump($var)
    {
        ob_start();
        var_dump($var);
        $str = ob_get_clean();

        return $str;
    }

    /**
     * Renvois une chaine contenant les lignes du fichiers formatées pour l'affichage
     *
     * @param string $fileName Chemin vers le fichier
     * @param int    $line     Ligne à lire
     *
     * @return string
     * @uses \GeSHi
     */
    protected function readLines($fileName, $line)
    {
        $file = file($fileName);
        $strFile = '';
        for ($i = $line - 6; $i < $line + 2; $i++) {
            if ($i < 0 || $i >= count($file)) {
                continue;
            }
            $strFile .= $file[$i];
        }
        $geshi = new \GeSHi($strFile, 'php');
        $geshi->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS, 2);
        $geshi->start_line_numbers_at($line - 6);
        $geshi->set_highlight_lines_extra_style('background: #497E7E;');
        $geshi->highlight_lines_extra(6);
        return $geshi->parse_code();
    }

    /**
     * Envois le rapport
     *
     * @return void
     */
    public function send()
    {
        $dir = dirname(__FILE__) . DIRECTORY_SEPARATOR;
        ob_start();
        include $dir . 'marvin.phtml';
        $str = ob_get_clean();
        mail($this->contact, $this->title, $str, $this->headers);
    }

    /**
     * Affiche le rapport
     *
     * @return void
     */
    public function display()
    {
        if (!self::$display) {
            return;
        }

        $dir = dirname(__FILE__) . DIRECTORY_SEPARATOR;
        include $dir . 'marvin.phtml';
        die();
    }
}
