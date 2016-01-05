<?php
/**
 * Champ File
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\Model\Gabarit\Field\File;

use Solire\Lib\Model\FileManager;
use Solire\Lib\Model\Gabarit\Field\GabaritField;
use Solire\Lib\Registry;

/**
 * Champ File
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class FileField extends GabaritField
{
    /**
     * Création du champ
     *
     * @return void
     */
    public function start()
    {
        $this->uploadConfig = Registry::get('mainconfig')->get('upload');

        parent::start();
        $this->isImage = false;
        if ((isset($this->params['CROP.WIDTH.MIN']) && intval($this->params['CROP.WIDTH.MIN']) > 0) ||
            (isset($this->params['CROP.HEIGHT.MIN']) && intval($this->params['CROP.HEIGHT.MIN']) > 0)
        ) {
            $this->champ['aide'] .= '<div>';
            if (
                isset($this->params['CROP.WIDTH.MIN'])
                && intval($this->params['CROP.WIDTH.MIN']) > 0
            ) {
                $this->champ['aide'] .= '<dl class="dl-horizontal expected-width">
                                    <dt>Largeur</dt>
                                    <dd><span id="">'
                                     . $this->params['CROP.WIDTH.MIN'] . '</span>px</dd>
                                </dl>';
            }
            if (isset($this->params['CROP.HEIGHT.MIN'])
                && intval($this->params['CROP.HEIGHT.MIN']) > 0
            ) {
                $this->champ['aide'] .= '<dl class="dl-horizontal expected-height">
                                    <dt>Hauteur</dt>
                                    <dd><span id="">'
                                    . $this->params['CROP.HEIGHT.MIN'] . '</span>px</dd>
                                </dl>';
            }

            $this->champ['aide'] .= '</div>';
        }

        $ext = strtolower(array_pop(explode('.', $this->value)));
        if (array_key_exists($ext, FileManager::$extensions['image'])) {
            $this->isImage = true;
        }

        // On met la valeur à vide si le fichier n'existe pas
        $href   = $this->idGabPage . '/' . $this->value;
        $path = 'upload/' . $href;
        if (!file_exists($path)) {
            $this->value = null;
        }
    }
}
