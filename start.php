<?php
/**
 * Lancement du framework
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib;

/* = lancement du script
  ------------------------------- */
try {
    FrontController::setApp('app');
    FrontController::init();
    FrontController::run();
} catch (Exception\Marvin $exc) {
    Error::report($exc);
} catch (Exception\User $exc) {
    Error::message($exc);
} catch (Exception\HttpError $exc) {
    if (current($exc->getHttp()) == '404') {
        header('HTTP/1.0 404 Not Found');
        FrontController::run('Error', 'error404');
    } else {
        Error::http($exc->getHttp());
    }
} catch (\Exception $exc) {
    $marvin = new Marvin('debug', $exc);
    if ($debug) {
        $marvin->display();
    } else {
        $marvin->send();
    }
    Error::run();
}
