<?php
namespace Solire\Lib\Loader;

/**
 * Gestionnaire des scripts css pour le html
 *
 * @author  thansen <thansen@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Css extends Loader
{
    /**
     * Template du code html pour un script css
     *
     * @param string $url     Url de la librairie
     * @param string $realUrl Vraie url de la librairie
     * @param array  $options Options de la librairie, ici attribut html de la
     * baslie img
     *
     * @return string
     */
    protected function template($url, $realUrl, array $options = [])
    {
        if ($realUrl === null) {
            $options['href'] = $url;
        } elseif (isset($options['cache']) && $options['cache']) {
            $mask = '#\.css$#';
            $time = filemtime($realUrl);
            $options['href'] = preg_replace($mask, '.' . $time . '.css', $realUrl);

            unset($options['cache']);
        } else {
            $options['href'] = $realUrl;
        }

        if (!isset($options['type'])) {
            $options['type'] = 'text/css';
        }

        if (!isset($options['rel'])) {
            $options['rel'] = 'stylesheet';
        }

        $attr = '';
        foreach ($options as $key => $value) {
            $value = str_replace('"', '&quot;', $value);
            $attr .= ' ' . $key . '="' . $value . '"';
        }

        $html = '<link' . $attr . '>';

        return $html;
    }
}
