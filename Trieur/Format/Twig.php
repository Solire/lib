<?php

namespace Solire\Lib\Trieur\Format;

use Exception;
use Solire\Lib\Registry;
use Solire\Lib\Templating\Twig\Twig as TemplatingTwig;
use Solire\Trieur\AbstractFormat;

/**
 * Description of TwigSolire.
 *
 * @author thansen
 */
class Twig extends AbstractFormat
{
    private $context = [];

    private $twig;

    protected function init()
    {
        if (!isset($this->conf->fileName)) {
            throw new Exception(
                'Missing filename for the Solire\Trieur twig format conf'
            );
        }

        if (isset($this->conf->context)) {
            $this->context = (array) $this->conf->context;
        }

        $this->context = array_merge([
            'row' => $this->row,
            'cell' => $this->cell,
        ], $this->context);

        $this->twig = new TemplatingTwig(Registry::get('viewFileLocator'));
    }

    public function render()
    {
        return $this->twig->render(
            $this->conf->fileName,
            $this->context
        );
    }
}
