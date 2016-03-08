<?php
/**
 * Chargement de la session client.
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\Formulaire;

/**
 * Chargement de la session client.
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
trait ExceptionTrait
{
    protected $targetInput = '';

    /**
     * Enregistre le nom de l'input qui contient une erreur.
     *
     * @param string|array $inputName Nom de l'input fautif
     *
     * @return self
     */
    public function setErrorInputName($inputName)
    {
        $this->targetInput = $inputName;

        return $this;
    }

    /**
     * Renvoie le nom du champ du formulaire qui pose problème.
     *
     * @return string|array
     */
    public function getTargetInputName()
    {
        return $this->targetInput;
    }
}
