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
        parent::start();
        $this->isImage = false;
        if ((isset($this->params['CROP.WIDTH.MIN']) && intval($this->params['CROP.WIDTH.MIN']) > 0) ||
            (isset($this->params['CROP.HEIGHT.MIN']) && intval($this->params['CROP.HEIGHT.MIN']) > 0)
        ) {
            $this->champ['aide'] .= '<div style="display:inline-block">';
            if (isset($this->params['CROP.WIDTH.MIN'])
                && intval($this->params['CROP.WIDTH.MIN']) > 0
            ) {
                $this->champ['aide'] .= '<dl class="dl-horizontal expected-width">
                                    <dt style="width: 180px;">Largeur</dt>
                                    <dd style="margin-left: 190px;"><span id="">'
                    . $this->params['CROP.WIDTH.MIN'] . '</span>px</dd>
                                </dl>';
            }
            if (isset($this->params['CROP.HEIGHT.MIN'])
                && intval($this->params['CROP.HEIGHT.MIN']) > 0
            ) {
                $this->champ['aide'] .= '<dl class="dl-horizontal expected-height">
                                    <dt style="width: 180px;">Hauteur</dt>
                                    <dd style="margin-left: 190px;"><span id="">'
                    . $this->params['CROP.HEIGHT.MIN'] . '</span>px</dd>
                                </dl>';
            }

            $this->champ['aide'] .= '</div>';
        }

        $ext = strtolower(array_pop(explode('.', $this->value)));
        if (array_key_exists($ext, FileManager::$extensions['image'])) {
            $this->isImage = true;
        }
    }
}
