<?php
/**
 * Contrôle de variables.
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib;

/** @todo faire la présentation du code */

/**
 * Contrôle de variables.
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Param
{
    /**
     * Variable.
     *
     * @var mixed
     */
    private $foo = null;

    /**
     * Charge une nouvelle variable.
     *
     * @param mixed $param Valeur de la variable à tester
     */
    public function __construct($param = null)
    {
        $this->foo = $param;
    }

    /**
     * Retourne la valeur du paramètre.
     *
     * @return mixed
     */
    public function get()
    {
        return $this->foo;
    }

    /**
     * Envois d'une erreur.
     *
     * @param string $message Message d'erreur
     *
     * @return void
     *
     * @throws Exception\Lib
     */
    private function error($message)
    {
        throw new Exception\Lib($message);
    }

    /**
     * Permet d'effectuer differents tests sur la variable.
     *
     * @param array $options Tableau de tests à effectuer
     *
     * @return bool
     */
    public function tests($options)
    {
        if (!is_array($options) || empty($options)) {
            $this->error('$options doit être un tableau');
        }

        foreach ($options as $option) {
            $param = null;
            if (strpos($option, ':')) {
                $foo = explode(':', $option);
                $option = $foo[0];
                $param = $foo[1];
                unset($foo);
            }
            $method = 'test' . ucwords($option);
            if (!method_exists(__CLASS__, $method)) {
                $this->error('erreur : ' . $method . ' n\'existe pas');
            }

            if (!$this->$method($param)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Test si le parametre n'est pas vide.
     *
     * @return bool
     */
    public function testNotEmpty()
    {
        if (empty($this->foo)) {
            return false;
        }

        return true;
    }

    /**
     * Test si le parametre est un entier.
     *
     * @return bool
     */
    public function testIsInt()
    {
        if ((string) ((int) $this->foo) == (string) $this->foo) {
            return true;
        }

        return false;
    }

    /**
     * Test si le parametre est un boolean.
     *
     * @return bool
     */
    public function testIsBoolean()
    {
        if ($this->foo == 0 || $this->foo == 1) {
            return true;
        }

        return false;
    }

    /**
     * Test si le parametre est positif.
     *
     * @return bool
     */
    public function testIsPositive()
    {
        if ($this->foo > 0) {
            return true;
        }

        return false;
    }

    /**
     * Test si le parametre est un float.
     *
     * @return bool
     */
    public function testIsFloat()
    {
        if ((string) ((float) $this->foo) == (string) $this->foo) {
            return true;
        }

        return false;
    }

    /**
     * Test si le parametre est un mail.
     *
     * @return bool
     */
    public function testIsMail()
    {
        $mask = '#^[a-z0-9._\-\+]+@[a-z0-9.-]{2,}[.][a-z0-9]{2,5}$#i';
        if (preg_match($mask, $this->foo)) {
            return true;
        }

        return false;
    }

    /**
     * Test si le parametre est un tableau.
     *
     * @return bool
     */
    public function testIsArray()
    {
        if (is_array($this->foo)) {
            return true;
        }

        return false;
    }

    /**
     * Test si le parametre est une chaine.
     *
     * @return bool
     */
    public function testIsString()
    {
        if ((string) $this->foo === $this->foo) {
            return true;
        }

        return false;
    }

    /**
     * Test si le parametre est un numéro de téléphone.
     *
     * @return bool
     */
    public function testIsPhone()
    {
        if (preg_match('#^0[1-9]([-. ]?[0-9]{2}){4}$#', $this->foo)) {
            return true;
        }

        return false;
    }

    /**
     * Test la longueur en nombre de charactères d'une chaine.
     *
     * @param int $length Chaine au formatage spéciale, voir .ini
     *
     * @return bool
     */
    public function testLength($length)
    {
        $sign = preg_replace('#([0-9]+)#', '', $length);
        $length = str_replace($sign, '', $length);

        switch ($sign) {
            case '=':
                if (strlen($this->foo) == $length) {
                    return true;
                }
                break;
            case '>=':
                if (strlen($this->foo) >= $length) {
                    return true;
                }
                break;
            case '<=':
                if (strlen($this->foo) <= $length) {
                    return true;
                }
                break;
            case '>':
                if (strlen($this->foo) > $length) {
                    return true;
                }
                break;
            case '<':
                if (strlen($this->foo) < $length) {
                    return true;
                }
                break;

        }

        return false;
    }

    /**
     * Test si le parametre ne contient que des chiffres.
     *
     * @return bool
     */
    public function testOnlyNumber()
    {
        $char = preg_replace('#([0-9]+)#', '', $this->foo);
        if (!empty($char)) {
            return false;
        }

        return true;
    }

    /**
     * Test si le paramètre n'est pas une valeur de blocage.
     *
     * @param string $value Valeur de blocage
     *
     * @return bool
     */
    public function testNot($value)
    {
        if ($this->foo == $value) {
            return false;
        }

        return true;
    }
}
