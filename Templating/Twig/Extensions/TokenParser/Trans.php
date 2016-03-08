<?php
/**
 * Analyseur de token pour la traduction de texte.
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\Templating\Twig\Extensions\TokenParser;

use Solire\Lib\Templating\Twig\Extensions\Node\Trans as TransNode;

/**
 * Analyseur de token pour la traduction de texte.
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Trans extends \Twig_TokenParser
{
    /**
     * Analyse un token et retourne un noeud.
     *
     * @param \Twig_Token $token Une instance Twig_Token
     *
     * @return \Twig_NodeInterface Une instance Twig_NodeInterface
     *
     * @throws \Twig_Error_Syntax
     */
    public function parse(\Twig_Token $token)
    {
        $lineno = $token->getLine();

        $this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideForFork'], true);
        $this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);

        return new TransNode($body, $lineno, $this->getTag());
    }

    /**
     * Test si la balise de fermeture a été atteinte.
     *
     * @param \Twig_Token $token Le token
     *
     * @return bool Vrai si la balise de fermeture a été atteinte
     */
    public function decideForFork(\Twig_Token $token)
    {
        return $token->test(['endtrans']);
    }

    /**
     * Your tag name: if the parsed tag match the one you put here, your parse()
     * method will be called.
     *
     * @return string
     */
    public function getTag()
    {
        return 'trans';
    }
}
