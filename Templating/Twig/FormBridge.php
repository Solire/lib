<?php

namespace Solire\Lib\Templating\Twig;

use ReflectionClass;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Form\TwigRenderer;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Twig_Environment;

/**
 * Description of Form.
 *
 * @author thansen
 */
class FormBridge
{
    public function __construct(Twig_Environment $twig, array $templatePaths)
    {
        $appVariableReflection = new ReflectionClass(AppVariable::class);
        $vendorTwigBridgeDir = dirname($appVariableReflection->getFileName());
        $twig->getLoader()->addPath($vendorTwigBridgeDir . '/Resources/views/Form');

        $formEngine = new TwigRendererEngine($templatePaths);
        $formEngine->setEnvironment($twig);
        // add the FormExtension to Twig
        $twig->addExtension(
            new FormExtension(new TwigRenderer($formEngine))
        );
    }
}
