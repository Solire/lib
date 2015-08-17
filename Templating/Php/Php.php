<?php
/**
 * Classe de rendu des templates Php
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\Templating\Php;

use Solire\Lib\Templating\Templating;

/**
 * Classe de rendu des templates Php
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Php extends Templating
{

    /**
     * Chemin vers la vue "main"
     *
     * @var boolean|string
     */
    private $mainPath = false;

    /**
     * Chemin vers la vue "content"
     *
     * @var boolean|string
     */
    private $contentPath = false;

    /**
     * @return bool|string
     */
    public function getMainPath()
    {
        return $this->mainPath;
    }

    /**
     * @param bool|string $mainPath Chemin vers le template de base
     * ou false pour le désactiver
     *
     * @return mixed|void
     */
    public function setMainPath($mainPath)
    {
        $this->mainPath = $mainPath;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $templatingFilePath Chemin du template
     * @param array  $variables          Variables à inclure dans le scope du template
     *
     * @return void
     */
    public function render($templatingFilePath, $variables = [])
    {
        $this->contentPath = $templatingFilePath;

        foreach ($variables as $variableName => $variable) {
            if (!property_exists($this, $variableName)) {
                $this->$variableName = $variable;
            }
        }

        if ($this->mainPath !== false) {
            include $this->mainPath;
        } else {
            $this->content();
        }
    }

    /**
     * Inclus le template demandé dans le template de base
     *
     * @return void
     */
    public function content()
    {
        include $this->contentPath;
    }

    /**
     * Inclus un fichier de template
     *
     * @param string $templatingFilePath Chemin du fichier de template
     *
     * @return self
     */
    public function add($templatingFilePath)
    {
        $resolvedPath = $this->fileLocator->locate('view/' . $templatingFilePath);

        if ($resolvedPath !== false) {
            include $resolvedPath;
        }

        return $this;
    }
}
