<?php
namespace Solire\Lib\Loader;

/**
 * Gestionnaire des scripts js pour le html
 *
 * @author  thansen <thansen@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Javascript extends Loader
{
    /**
     * Template du code html pour un script js
     *
     * @param string $url     Url de la librairie
     * @param string $realUrl Vraie url de la librairie
     * @param array  $options Options de la librairie, ici attribut html de la
     * balise script
     *
     * @return string
     */
    protected function template($url, $realUrl, array $options = [])
    {
        if ($realUrl === null) {
            $options['src'] = $url;
        } elseif (isset($options['cache']) && $options['cache']) {
            $mask = '#\.js$#';
            $time = filemtime($realUrl);
            $options['src'] = preg_replace($mask, '.' . $time . '.js', $realUrl);

            unset($options['cache']);
        } else {
            $options['src'] = $realUrl;
        }

        if (!isset($options['type'])) {
            $options['type'] = 'text/javascript';
        }

        $attr = '';
        foreach ($options as $key => $value) {
            $value = str_replace('"', '&quot;', $value);
            $attr .= ' ' . $key . '="' . $value . '"';
        }

        $html = '<script' . $attr . '></script>';

        return $html;
    }
}
