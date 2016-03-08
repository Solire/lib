<?php
/**
 * Erreur de l'utilisateur.
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\Exception;

use Solire\Lib\Formulaire\ExceptionTrait;

/**
 * Erreur de l'utilisateur.
 *
 * Par exemple, formulaire incomplet, ajout d'un produit non existant etc...
 * tout ce qui demande l'affichage d'un message pour l'utilisateur.
 * Ces erreurs entraineront l'affichage d'un message paramétrable pour
 * l'utilisateur
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class User extends \Exception
{
    use ExceptionTrait;

    /**
     * Lien vers la page qui suit le message.
     *
     * @var string
     */
    private $link;

    /**
     * Temps avant réorientation de la page.
     *
     * @var int
     */
    private $auto;

    /**
     * Paramètre les règles de redirection.
     *
     * @param string $link Url vers laquelle rediriger l'utilisateur
     * @param int    $auto Mettre le temps après lequel la redirection se fait automatiquement.
     *                     Laisser à vide pour ne pas avoir de redirection automatique
     *
     * @return void
     */
    public function link($link, $auto = null)
    {
        $this->link = $link;
        $this->auto = $auto;
    }

    /**
     * Renvois les paramètres de redirection.
     *
     * @return array
     */
    public function get()
    {
        return [$this->link, $this->auto];
    }
}
