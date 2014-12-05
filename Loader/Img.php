<?php
namespace Solire\Lib\Loader;

/**
 * Gestionnaire des images pour le html
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Img extends Loader
{
    /**
     * Template du code html pour une image
     *
     * @param string $url     Url de la librairie
     * @param string $realUrl Vraie url de la librairie
     * @param array  $options Options de la librairie, ici attribut html de la
     * baslie img
     *
     * @return string
     */
    protected function template($url, $realUrl = null, array $options = [])
    {
        if ($realUrl === null) {
            $options['src'] = $url;
        } else {
            $options['src'] = $realUrl;
        }

        $attr = '';
        foreach ($options as $key => $value) {
            $value = str_replace('"', '&quot;', $value);
            $attr .= ' ' . $key . '="' . $value . '"';
        }

        $html = '<img' . $attr . '>';

        return $html;
    }
}
