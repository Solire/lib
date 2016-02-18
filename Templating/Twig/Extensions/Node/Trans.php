<?php
/**
 * Représente un noeud trans.
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\Templating\Twig\Extensions\Node;

/**
 * Représente un noeud trans.
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Trans extends \Twig_Node
{
    /**
     * Constructeur.
     *
     * @param \Twig_NodeInterface $body   Contenu du noeud
     * @param int                 $lineno Numéro de ligne
     * @param string              $tag    Nom du tag
     */
    public function __construct($body, $lineno, $tag = 'trans')
    {
        parent::__construct(['body' => $body], [], $lineno, $tag);
    }

    /**
     * Compile le noeud en PHP.
     *
     * @param \Twig_Compiler $compiler Une instance de Twig_Compiler
     *
     * @return void
     */
    public function compile(\Twig_Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write("ob_start();\n")
            ->subcompile($this->getNode('body'))
            ->write(
                "echo Solire\\Lib\\Registry::get('translator')"
                . "->trad(trim(preg_replace('/\s+/', ' ', ob_get_clean())));\n"
            );
    }
}
