<?php
/**
 * Extensions pour TWIG permettant de traduire des textes statiques
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\Templating\Twig\Extensions\Extension;

use Solire\Lib\Registry;
use Solire\Lib\Templating\Twig\Extensions\TokenParser\Trans as TransTokenParser;
use Solire\Lib\TranslateMysql;

/**
 * Extensions pour TWIG permettant de traduire des textes statiques
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class I18n extends \Twig_Extension
{
    /**
     * @var TranslateMysql
     */
    protected $translator = null;

    /**
     * Construct
     */
    public function __construct()
    {
        $this->translator = Registry::get('translator');
    }

    /**
     * Retourne la liste des filtres à ajouter.
     *
     * @return array Un tableau de filtres
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('trans', [$this, 'transFilter']),
        ];
    }

    /**
     * Renvoie les instances d'analyseur de Token à ajouter.
     *
     * @return array Un tableau d'instances Twig_TokenParserInterface ou Twig_TokenParserBrokerInterface
     */
    public function getTokenParsers()
    {
        return [new TransTokenParser()];
    }

    /**
     * Filtre permettant de traduire.
     *
     * @param string $string Chaîne à traduire
     *
     * @return string The extension name
     */
    public function transFilter($string)
    {
        return $this->translator->trad($string);
    }

    /**
     * Retourne le nom de l'extension
     *
     * @return string Le nom de l'extension
     */
    public function getName()
    {
        return 'i18n';
    }
}
