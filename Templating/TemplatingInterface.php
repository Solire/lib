<?php
/**
 * Interface des rendus
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\Templating;

use Solire\Lib\View\Filesystem\FileLocator;

/**
 * Interface des rendus
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
interface TemplatingInterface
{
    /**
     * Constructeur
     *
     * @param FileLocator $viewFileLocator Résolveur de chemin de fichier
     *
     */
    public function __construct(FileLocator $viewFileLocator);

    /**
     * Traitement et affichage du template
     *
     * @param string $templatingFilePath Chemin du template
     * @param array  $variables          Variables à inclure dans le scope du template
     *
     * @return void
     */
    public function display($templatingFilePath, $variables = []);

    /**
     * Permet de définir le chemin du template de base à utiliser
     *
     * @param string $mainPath Chemin du template de base à utiliser
     *
     * @return mixed
     */
    public function setMainPath($mainPath);
}
