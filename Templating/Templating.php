<?php
/**
 * Classe abstraite des rendus.
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\Templating;

use Solire\Lib\View\Filesystem\FileLocator;

/**
 * Classe abstraite des rendus.
 *
 * @author  Stéphane <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
abstract class Templating implements TemplatingInterface
{
    /**
     * @var FileLocator
     */
    protected $fileLocator;

    /**
     * {@inheritdoc}
     *
     * @param FileLocator $viewFileLocator Résolveur de chemin de fichier
     */
    public function __construct(FileLocator $viewFileLocator)
    {
        $this->fileLocator = $viewFileLocator;
    }
}
