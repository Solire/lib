<?php
/**
 * Gestionnaire des fichiers de configurations
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
namespace Solire\Lib;

header('Content-Type: text/html; charset=utf-8');
define('DS', DIRECTORY_SEPARATOR);

/** Session PHP */
session_name();
session_start();

require 'vendor/autoload.php';

$dir = pathinfo(__FILE__, PATHINFO_DIRNAME);
set_include_path(
    get_include_path()
    . PATH_SEPARATOR . realpath($dir)
);
unset($dir);
require_once __DIR__ . '/Path.php';

$debug = false;
