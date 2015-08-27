<?php
/**
 * Gestionnaire des fichiers de configurations
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib;

/**
 * Gestionnaire des fichiers de configurations
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Config
{
    /**
     * Nom de la section de configuration du .ini
     */
    const KEY_CONF = '__config';

    /**
     * Format des variables
     */
    const VAR_FORMAT = '#{%([a-z0-9_:]+)}#i';

    /**
     * Contenu du fichier de config
     *
     * @var array
     */
    protected $config = null;

    /**
     * Tableau de paramétrage du fichier de configuration
     *
     * @var array
     */
    private $headerConfig;

    /**
     * Charge un nouveau fichier de configuration
     *
     * @param string $iniFile Chemin vers le fichier de configuration
     *
     * @uses \Solire\Lib\Path Contrôle du chemin du fichier
     */
    public function __construct($iniFile)
    {
        $iniPath = new Path($iniFile);
        $this->config = parse_ini_file($iniPath->get(), true);

        $this->headerConfig = $config = $this->get(self::KEY_CONF);
        unset($this->config[self::KEY_CONF]);

        /** Extends **/
        if (isset($config['extends'])) {
            $extends = $config['extends'];
            if (!is_array($extends)) {
                $extends = [$extends];
            }

            foreach ($extends as $path) {
                $this->setExtends($path);
            }
        }

        $this->parseVar();
    }

    /**
     * Renvois la section de configuration du .ini
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->headerConfig;
    }

    /**
     * Fait hériter le fichier de configuration d'un autre fichier.
     * Permet d'incorporer un fichier de configuration par défaut qui sera
     * surchargé par le fichier actuel.
     *
     * @param string $path Chemin vers le fichier de configuration "défaut"
     *
     * @return void
     */
    public function setExtends($path)
    {
        $iniPath = new Path($path);
        $configBase = parse_ini_file($iniPath->get(), true);
        $this->config = $this->arrayMerge($configBase, $this->config);
    }

    /**
     * Merge les tableaux en replaçants les clés identiques
     *
     * @param array $array1 Tableau à merge
     * @param array $array2 Tableau à merge
     *
     * @return array
     */
    private function arrayMerge(array &$array1, array &$array2)
    {
        $merged = $array1;

        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset($merged[$key])
                && is_array($merged [$key])
            ) {
                $merged [$key] = $this->arrayMerge($merged [$key], $value);
                continue;
            }
            $merged [$key] = $value;
        }

        return $merged;
    }

    /**
     * Application des variables
     *
     * @return void
     */
    private function parseVar()
    {
        /*
         * Parcour des options de configurations
         */
        foreach ($this->config as $divName => $section) {
            /*
             * on test si dans la section il y a une variable
             * ça permet de passer à la suivante sans avoir à tout tester
             */
            $testString = '';
            foreach ($section as $value) {
                if (is_array($value)) {
                    $value = implode(' ', $value);
                }
                $testString .= $value;
            }
            if (!preg_match(self::VAR_FORMAT, $testString)) {
                continue;
            }

            foreach ($section as $key => $value) {
                /*
                 * On prend en compte la possibilite de mettre un tableau dans
                 * un attribut
                 * @example
                 * [section]
                 * item[] = "a"
                 * item[] = "b"
                 */
                if (!is_array($value)) {
                    $type = 'string';
                    $value = [$value];
                } else {
                    $type = 'array';
                }

                foreach ($value as $index => $valueLine) {
                    if (preg_match_all(self::VAR_FORMAT, $valueLine, $matches)) {
                        for ($i = 0; $i < count($matches[0]); $i++) {
                            $id = $matches[1][$i];
                            /**
                             * Si il y a un : dans le nom de la variable c'est
                             * qu'elle pointe sur un autre bloc sinon on prend
                             * le bloc en cours
                             */
                            if (strpos($id, ':') !== false) {
                                $opt = explode(':', $id);
                                $val = $this->get($opt[0], $opt[1]);
                            } else {
                                $val = $this->get($divName, $id);
                            }

                            /**
                             * On replace la valeur de la variable dans le champ
                             */
                            $valueLine = str_replace(
                                $matches[0][$i],
                                $val,
                                $valueLine
                            );

                            if ($type == 'string') {
                                $this->config[$divName][$key] = $valueLine;
                            } else {
                                $this->config[$divName][$key][$index] = $valueLine;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Renvois le contenu du fichier de configuration
     *
     * @return array Tableau de la configuration
     */
    public function getAll()
    {
        return $this->config;
    }

    /**
     * Renvois la valeur d'un parametre de configuration
     *
     * @param string $section Code de la section
     * @param string $key     Nom de la clé de configuration
     *
     * @return mixed null si aucune configuration ne répond aux critères
     */
    public function get($section, $key = null)
    {
        if (!empty($key)) {
            if (isset($this->config[$section][$key])) {
                return $this->config[$section][$key];
            }

        } else {
            if (isset($this->config[$section])) {
                return $this->config[$section];
            }
        }

        return null;
    }

    /**
     * Enregistre la valeur
     *
     * @param mixed  $value   Valeur à mettre dans la configuration
     * @param string $section Code de la section
     * @param string $key     Nom de la clé de configuration
     *
     * @return self
     */
    public function set($value, $section, $key = null)
    {
        if (!empty($key)) {
            $this->config[$section][$key] = $value;
            return $this;
        } else {
            $this->config[$section] = $value;
        }

        return $this;
    }

    /**
     * Supprime un parametre de configuration
     *
     * @param string $section Code de la section
     * @param string $key     Nom de la clé de configuration
     *
     * @return self
     */
    public function kill($section, $key = null)
    {
        if (!empty($key)) {
            if (isset($this->config[$section][$key])) {
                unset($this->config[$section][$key]);
            }

        } else {
            if (isset($this->config[$section])) {
                unset($this->config[$section]);
            }
        }
        return $this;
    }
}
