<?php
/**
 * Gestionnaire des erreurs
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib;

/**
 * Gestionnaire des erreurs
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
final class Error
{
    /**
     * Code HTTP de l'erreur
     *
     * @var int
     */
    static protected $code;

    /**
     * Liste des entêtes http utilisés
     * @todo Déporter les descriptions des erreurs http possible dans un tutorial
     * @var array
     */
    static private $headers = [
        301 => '301 Moved Permanently',
        // Une authentification est nécessaire pour accéder à la ressource
        401 => '401 Unauthorized',
        // L’authentification est refusée. Contrairement à l’erreur 401, aucune
        //  demande d’authentification ne sera faite
        403 => '403 Forbidden',
        404 => '404 Not Found',
        405 => '405 Method Not Allowed',
        418 => '418 I’m a teapot',
        429 => '429 Too Many Requests',
        500 => '500 Internal Server Error',
        // Service temporairement indisponible ou en maintenance
        503 => '503 Service Unavailable',
    ];

    /**
     * Fonctionnement par défaut, fait passer la page en erreur 500
     *
     * @return void
     * @uses Error::http()
     */
    public static function run()
    {
        self::http(500);
    }

    /**
     * Affiche une erreur HTTP
     *
     * @param int|array $code Code HTTP de l'erreur
     *
     * @return void
     * @uses Error::setHeader()
     */
    public static function http($code)
    {
        $url = null;
        if (is_array($code)) {
            $url = $code[1];
            $code = $code[0];
        }

        self::$code = $code;

        self::setHeader($url);

        $fileName = self::getPath($code);
        if ($fileName !== false) {
            include $fileName;
        } else {
            include __DIR__ . '/error/500.phtml';
        }
    }


    /**
     * Renvois le chemin vers la vue relative à l'erreur
     *
     * @param string $code Code de l'erreur
     *
     * @return mixed Chemin vers le fichier ou false
     */
    private static function getPath($code)
    {
        $dirs = FrontController::getSourceDirectories();
        foreach ($dirs as $dir) {
            $path = $dir['dir'] . Path::DS . 'error' . Path::DS;
            $path = new Path($path . $code . '.phtml', Path::SILENT);
            if ($path->get()) {
                return $path->get();
            }
        }

        return false;
    }

    /**
     * Affiche le message d'erreur demandé pour l'utilisateur
     *
     * @param Exception\User $exc Exception utilisateur
     *
     * @return void
     * @uses Message
     */
    public static function message(Exception\User $exc)
    {
        $message = new Message($exc->getMessage());
        $message->setEtat('error');
        list($link, $auto) = $exc->get();
        $message->addRedirect($link, $auto);
        if ($exc->getTargetInputName() !== '') {
            $message->inputName = $exc->getTargetInputName();
        }
        try {
            $message->display();
        } catch (\Exception $exc) {
            self::http(500);
        }
    }

    /**
     * Envois un rapport Marvin et affiche une erreur 500
     *
     * @param Exception\Marvin $exc Exception à marquer d'un rapport
     *
     * @return void
     * @uses Marvin
     * @uses Exception\Marvin::getTitle()
     */
    public static function report(Exception\Marvin $exc)
    {
        $marvin = new Marvin($exc->getTitle(), $exc);
        $marvin->send();

        self::run();
    }



    /**
     * Affiche le header correspondant à l'erreur
     *
     * @param string $url Ajoute une redirection au header
     *
     * @return void
     * @uses Error::$headers
     */
    private static function setHeader($url = null)
    {
        header('HTTP/1.0 ' . self::$headers[self::$code]);
        if ($url !== null) {
            self::setHeaderRedirect($url);
        }
    }

    /**
     * Ajoute une redirection dans le header
     *
     * @param string $url Url vers laquelle on redirige l'utilisateur
     *
     * @return void
     */
    private static function setHeaderRedirect($url)
    {
        header('Location: ' . $url);
    }
}
