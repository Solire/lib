<?php
/**
 * Erreur HTTP
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\Exception;

/**
 * Erreur HTTP
 *
 * Les HttpErrorExceptions entraineront un blocage de la page et la modification du
 * header http pour afficher son code d'erreur
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class HttpError extends \Exception
{
    /**
     * Code HTTP
     * @var int
     */
    private $code = 500;

    /**
     * Url de redirection
     * @var string
     */
    private $url = null;

    /**
     * Ajoute un code HTTP à l'erreur
     *
     * @param int    $code Code HTTP de l'erreur
     * @param string $url  Url vers laquelle rediriger l'utilisateur
     *
     * @return void
     */
    public function http($code, $url = null)
    {
        $this->code = $code;
        $this->url = $url;
    }

    /**
     * Renvois les informations relatives à l'erreur http
     *
     * @return string|array peut être le code http ou un tableau contenant le
     * code http et l'url vers laquelle rediriger l'utilisateur
     */
    public function getHttp()
    {
        if ($this->getCode() !== 0) {
            return $this->getCode();
        }

        return [$this->code, $this->url];
    }
}
