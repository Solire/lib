<?php
/**
 * Gestion des fichiers
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\Model;

/**
 * Gestion des fichiers
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class FileManager extends manager
{

    /**
     *
     * @var Nom de la table media
     */
    protected $mediaTableName = 'media_fichier';

    /**
     *
     * @var array
     */
    public static $extensions = array(
        'image' => array(
            'jpg' => 'jpeg',
            'jpeg' => 'jpeg',
            'gif' => 'gif',
            'png' => 'png',
        )
    );

    /**
     *
     * @var array
     */
    public static $vignette = array(
        'max-width' => 200,
        'max-height' => 50,
    );

    /**
     *
     * @var array
     */
    public static $apercu = array(
        'max-width' => 200,
        'max-height' => 90,
    );

    /**
     * Renvoi l'extension de l'image (utilisé dans le nom des fonctions de
     * traitement des images, ou false si l'extension du fichier n'est pas
     * une image.
     *
     * @param string $fileName Nom du fichier
     *
     * @return false|string
     */
    public static function isImage($fileName)
    {
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);

        if (isset(self::$extensions['image'][$ext])) {
            return self::$extensions['image'][$ext];
        }

        return false;
    }

    /**
     * Crée un dossier avec les droits 777
     *
     * @param string $chemin Chemin du dossier à créer
     *
     * @return bool
     */
    public function createFolder($chemin)
    {
        umask(0000);
        return mkdir($chemin, 0777);
    }

    /**
     * Renvoi un tableau de fichiers lié à une page.
     *
     * @param int    $idGabPage Identifiant de la page
     * @param int    $idTemp    Identifiant temporaire (page en création)
     * @param string $search    Chaîne cherchée
     * @param string $orderby   Colonne de tri
     * @param string $sens      Sens du tri (ASC|DESC)
     *
     * @return array
     */
    public function getList(
        $idGabPage = 0,
        $idTemp = 0,
        $search = null,
        $orderby = null,
        $sens = null
    ) {
        $query = 'SELECT `' . $this->mediaTableName . '`.*, IF(id_version IS NULL, 0, 1) utilise '
                . 'FROM `' . $this->mediaTableName . '` '
                . 'LEFT JOIN media_fichier_utilise '
                . 'ON `' . $this->mediaTableName . '`.rewriting = media_fichier_utilise.rewriting '
                . 'WHERE `suppr` = 0 ';

        if ($idGabPage) {
            $query .= ' AND `' . $this->mediaTableName . '`.`id_gab_page` = ' . $idGabPage;
        }

        if ($idTemp) {
            $query .= ' AND `id_temp` = ' . $idTemp;
        }

        if ($search) {
            $search = '%' . $search . '%';
            $query .= ' AND `' . $this->mediaTableName . '`.`rewriting` LIKE ' . $this->db->quote($search);
        }

        $query .= ' GROUP BY `' . $this->mediaTableName . '`.rewriting';

        if ($orderby) {
            $query .= ' ORDER BY `' . $orderby . '` ';
            if ($sens) {
                $query .= $sens;
            }
        }

        $files = $this->db->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        return $files;
    }

    /**
     * Renvoi un tableau de fichiers lié à une page.
     *
     * @param string   $term       Chaîne cherchée
     * @param int      $idGabPage  Identifiant de la page
     * @param int      $idTemp     Identifiant temporaire (page en création)
     * @param string[] $extensions Tableau d'extension permise
     *
     * @return array
     */
    public function getSearch(
        $term,
        $idGabPage = 0,
        $idTemp = 0,
        $extensions = false
    ) {
        $query = 'SELECT `' . $this->mediaTableName . '`.*, IF(id_version IS NULL, 0, 1) utilise '
               . 'FROM `' . $this->mediaTableName . '` '
               . 'LEFT JOIN media_fichier_utilise '
               . 'ON `' . $this->mediaTableName . '`.rewriting = media_fichier_utilise.rewriting '
               . 'WHERE `suppr` = 0'
        ;

        if ($idGabPage) {
            $query .= ' AND `' . $this->mediaTableName . '`.`id_gab_page` = ' . $idGabPage;
        }

        if ($idTemp) {
            $query .= ' AND `' . $this->mediaTableName . '`.`id_temp` = ' . $idTemp;
        }

        $term = '%' . $term . '%';
        $query .= ' AND `' . $this->mediaTableName . '`.`rewriting` LIKE ' . $this->db->quote($term);

        $files = $this->db->query($query)->fetchAll(\PDO::FETCH_ASSOC);

        if (is_array($extensions)) {
            $files2 = array();

            foreach ($files as $file) {
                $ext = pathinfo($file['rewriting'], PATHINFO_EXTENSION);
                if (in_array($ext, $extensions)) {
                    $files2[] = $file;
                }
            }

            return $files2;
        }

        return $files;
    }

    /**
     * Upload un fichier et crée des vignettes
     *
     * @param string $uploadDir   Dossier principal d'upload (exemple : 'projet/upload')
     * @param string $targetTmp   Dossier de téléchargement temporaire (exemple : 'temp')
     * @param string $targetDir   Dossier de téléchargement final (exemple :
     * identifiant d'une page ou 'temp-' + l'identifiant temporaire d'une page en création)
     * @param string $vignetteDir Dossier contenant les vignettes (exemple : 'mini')
     * @param string $apercuDir   Dossier contenant les apercus (exemple :
     * 'apercu')
     *
     * @return array
     */
    public function upload(
        $uploadDir,
        $targetTmp,
        $targetDir,
        $vignetteDir,
        $apercuDir
    ) {
        /** HTTP headers for no cache etc. */
        header('Content-type: text/plain; charset=UTF-8');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');

        /** 5 minutes execution time */
        @set_time_limit(5 * 60);

        /** Get parameters */
        $chunk      = isset($REQUEST['chunk']) ? $REQUEST['chunk'] : 0;
        $chunks     = isset($REQUEST['chunks']) ? $REQUEST['chunks'] : 1;
        $fileName   = isset($REQUEST['name']) ? $REQUEST['name'] : '';
        $fileName = strtolower($fileName);
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        $name = pathinfo($fileName, PATHINFO_FILENAME);

        if ($chunk == $chunks - 1) {
            if (!file_exists($uploadDir . DS . $targetDir)) {
                $this->createFolder($uploadDir . DS . $targetDir);
            }

            if (!file_exists($uploadDir . DS . $vignetteDir)) {
                $this->createFolder($uploadDir . DS . $vignetteDir);
            }

            if (!file_exists($uploadDir . DS . $apercuDir)) {
                $this->createFolder($uploadDir . DS . $apercuDir);
            }
        }

        /** Clean the fileName for security reasons */
        $name = $this->db->rewrit($name);
        $fileName = $name . '.' . $ext;

        /** Look for the content type header */
        if (isset($SERVER['HTTP_CONTENT_TYPE'])) {
            $contentType = $SERVER['HTTP_CONTENT_TYPE'];
        }

        if (isset($SERVER['CONTENT_TYPE'])) {
            $contentType = $SERVER['CONTENT_TYPE'];
        }

        /**
         * Handle non multipart uploads older WebKit
         * versions didn't support multipart in HTML5
         */
        if (strpos($contentType, 'multipart') !== false) {
            if (isset($FILES['file']['tmp_name'])
                && is_uploaded_file($FILES['file']['tmp_name'])
            ) {
                /** Open temp file */

                if ($chunk == 0) {
                    $mode = 'wb';
                } else {
                    $mode = 'ab';
                }

                $out = fopen(
                    $uploadDir . DS . $targetTmp . DS . $fileName,
                    $mode
                );

                if ($out) {
                    /** Read binary input stream and append it to temp file */
                    $in = fopen($FILES['file']['tmp_name'], 'rb');

                    if ($in) {
                        while ($buff = fread($in, 4096)) {
                            fwrite($out, $buff);
                        }
                    } else {
                        return array(
                            'jsonrpc' => '2.0',
                            'status' => 'error',
                            'error' => array(
                                'code' => 101,
                                'message' => 'Failed to open input stream.'
                            ),
                            'id' => 'id'
                        );
                    }

                    fclose($in);
                    fclose($out);
                    @unlink($FILES['file']['tmp_name']);
                } else {
                    return array(
                        'jsonrpc' => '2.0',
                        'status' => 'error',
                        'error' => array(
                            'code' => 102,
                            'message' => 'Failed to open output stream.'
                        ),
                        'id' => 'id'
                    );
                }
            } else {
                return array(
                    'jsonrpc' => '2.0',
                    'status' => 'error',
                    'error' => array(
                        'code' => 103,
                        'message' => 'Failed to move uploaded file.'
                    ),
                    'id' => 'id'
                );
            }
        } else {
            /** Open temp file */
            if ($chunk == 0) {
                $mode = 'wb';
            } else {
                $mode = 'ab';
            }

            $out = fopen($uploadDir . DS . $targetTmp . DS . $fileName, $mode);

            if ($out) {
                /** Read binary input stream and append it to temp file */
                $in = fopen('php://input', 'rb');

                if ($in) {
                    while ($buff = fread($in, 4096)) {
                        fwrite($out, $buff);
                    }
                } else {
                    return array(
                        'jsonrpc' => '2.0',
                        'status' => 'error',
                        'error' => array(
                            'code' => 101,
                            'message' => 'Failed to open input stream.'
                        ),
                        'id' => 'id'
                    );
                }

                fclose($in);
                fclose($out);
            } else {
                return array(
                    'jsonrpc' => '2.0',
                    'status' => 'error',
                    'error' => array(
                        'code' => 102,
                        'message' => 'Failed to open output stream.'
                    ),
                    'id' => 'id'
                );
            }
        }

        /** Construct JSON-RPC response */
        $jsonrpc = array(
            'jsonrpc' => '2.0',
            'status' => 'success',
            'result' => $fileName,
        );

        /** Dernière partie. */
        if ($chunk == $chunks - 1) {
            $fileNameNew = $fileName;

            /** On renomme pour éviter d'écraser un fichier existant */
            if (file_exists($uploadDir . DS . $targetDir . DS . $fileName)) {
                $fileName_a = pathinfo($fileName, PATHINFO_FILENAME);
                $fileName_b = pathinfo($fileName, PATHINFO_EXTENSION);

                $count = 1;
                $path   = $uploadDir . DS . $targetDir . DS . $fileName_a . '-'
                        . $count . '.' . $fileName_b;
                while (file_exists($path)) {
                    $count++;
                    $path   = $uploadDir . DS . $targetDir . DS . $fileName_a
                            . '-' . $count . '.' . $fileName_b;
                }

                $fileNameNew = $fileName_a . '-' . $count . '.' . $fileName_b;
            }

            /** On déplace le fichier temporaire */
            rename(
                $uploadDir . DS . $targetTmp . DS . $fileName,
                $uploadDir . DS . $targetDir . DS . $fileNameNew
            );

            $size = filesize($uploadDir . DS . $targetDir . DS . $fileNameNew);

            /** Création de la miniature. */
            $ext = pathinfo($fileNameNew, PATHINFO_EXTENSION);
            if (array_key_exists($ext, self::$extensions['image'])) {
                $filePath = $uploadDir . DS . $targetDir . DS . $fileNameNew;
                $sizes = getimagesize($filePath);
                $width = $sizes[0];
                $height = $sizes[1];
                $jsonrpc['taille'] = $sizes[0] . ' x ' . $sizes[1];

                /** Création de la vignette  */
                $largeurmax = self::$vignette['max-width'];
                $hauteurmax = self::$vignette['max-height'];
                $this->vignette(
                    $filePath,
                    $ext,
                    $uploadDir . DS . $vignetteDir . DS . $fileNameNew,
                    $largeurmax,
                    $hauteurmax
                );
                $jsonrpc['mini_path'] = $uploadDir . DS . $vignetteDir . DS
                                      . $fileNameNew;
                $jsonrpc['mini_url']  = $vignetteDir . DS . $fileNameNew;

                /** Création de l'apercu  */
                $largeurmax = self::$apercu['max-width'];
                $hauteurmax = self::$apercu['max-height'];
                $this->vignette(
                    $filePath,
                    $ext,
                    $uploadDir . DS . $apercuDir . DS . $fileNameNew,
                    $largeurmax,
                    $hauteurmax
                );
            } else {
                $width = 0;
                $height = 0;
                $jsonrpc['taille'] = \Solire\Lib\Format\Number::formatSize($size);
            }

            /** Ajout d'informations utiles (ou pas) */
            $jsonrpc['filename'] = $fileNameNew;
            $jsonrpc['size'] = $size;
            $jsonrpc['width'] = $width;
            $jsonrpc['height'] = $height;
            $jsonrpc['path'] = $uploadDir . DS . $targetDir . DS
                             . $fileNameNew;
            $jsonrpc['url'] = $targetDir . DS . $fileNameNew;
            $jsonrpc['date'] = date('d/m/Y H:i:s');
        }

        return $jsonrpc;
    }

    /**
     * Upload un média lié à une page
     *
     * @param string $uploadDir   Dossier principal d'upload (exemple : 'projet/upload')
     * @param int    $idGabPage   Identifiant de la page (si elle est déjà créée)
     * @param int    $idTemp      Identifiant temporaire de la page (si elle est en cours de création)
     * @param string $targetTmp   Dossier de téléchargement temporaire (exemple : 'temp')
     * @param string $targetDir   Dossier de téléchargement final
     * (exemple : identifiant d'une page ou 'temp-' + l'identifiant temporaire d'une page en création)
     * @param string $vignetteDir Dossier contenant les vignettes (exemple : 'mini')
     * @param string $apercuDir   Dossier contenant les apercus (exemple : 'apercu')
     *
     * @return array
     */
    public function uploadGabPage(
        $uploadDir,
        $idGabPage,
        $idTemp,
        $targetTmp,
        $targetDir,
        $vignetteDir,
        $apercuDir
    ) {
        $json = $this->upload(
            $uploadDir,
            $targetTmp,
            $targetDir,
            $vignetteDir,
            $apercuDir
        );

        if (isset($json['filename'])) {
            $json['id'] = $this->insertToMediaFile(
                $json['filename'],
                $idGabPage,
                $idTemp,
                $json['size'],
                $json['width'],
                $json['height']
            );
        }

        return $json;
    }

    /**
     * Redimensionne, recadre et insert une image liée à une page.
     *
     * @param string    $uploadDir   Dossier principal d'upload (exemple : 'projet/upload')
     * @param string    $fileSource  Fichier a recadrer (exemple : '11/image.jpg', 'temp-12/picture.png')
     * @param string    $ext         Extension du fichier
     * @param string    $targetDir   Dossier où l'image recadrée sera enregistrée.
     * @param string    $target      Nom à donner au fichier recadré
     * @param int       $idGabPage   Identifiant de la page (si elle est en cours d'édition)
     * @param int       $idTemp      Identifiant temporaire de la page (si elle est en cours de création)
     * @param string    $vignetteDir Dossier ou enregistré la vignette de l'image recadrée
     * @param string    $apercuDir   Dossier ou enregistré l'apercu de l'image recadrée
     * @param int       $x           Abscisse du coin en haut à gauche
     * @param int       $y           Ordonnée du coin en haut à gauche
     * @param int       $w           Largeur du recadrage
     * @param int       $h           Hauteur du recadrage
     * @param false|int $targ_w      Largeur de l'image redimensionné ou false si pas de redimensionnement
     * @param false|int $targ_h      Hauteur de l'image redimensionné ou false si pas de redimensionnement
     *
     * @return array
     */
    public function crop(
        $uploadDir,
        $fileSource,
        $ext,
        $targetDir,
        $target,
        $idGabPage,
        $idTemp,
        $vignetteDir,
        $apercuDir,
        $x,
        $y,
        $w,
        $h,
        $targ_w = false,
        $targ_h = false
    ) {
        $destinationName = $uploadDir . DS . $targetDir . DS . $target;
        $fileNameNew     = $target;
        $ext             = pathinfo($fileNameNew, PATHINFO_EXTENSION);

        /** On créé et on enregistre l'image recadrée */
        if ($targ_w == false) {
            $targ_w = $w;
        }

        if ($targ_h == false) {
            $targ_h = $h;
        }

        $src = $uploadDir . DS . $fileSource;
        $img_r = call_user_func(
            'imagecreatefrom' . self::$extensions['image'][$ext],
            $src
        );
        $dstR = imagecreatetruecolor($targ_w, $targ_h);

        /** Transparence */
        if ($ext == 'png' || $ext == 'gif') {
            imagecolortransparent(
                $dstR,
                imagecolorallocatealpha(
                    $dstR,
                    0,
                    0,
                    0,
                    127
                )
            );
            imagealphablending($dstR, false);
            imagesavealpha($dstR, true);
        }

        imagecopyresampled($dstR, $img_r, 0, 0, $x, $y, $targ_w, $targ_h, $w, $h);

        if ($ext == 'png') {
            call_user_func(
                'image' . self::$extensions['image'][$ext],
                $dstR,
                $destinationName,
                0
            );
        } elseif ($ext == 'gif') {
            call_user_func(
                'image' . self::$extensions['image'][$ext],
                $dstR,
                $destinationName
            );
        } else {
            call_user_func(
                'image' . self::$extensions['image'][$ext],
                $dstR,
                $destinationName,
                95
            );
        }

        imagedestroy($dstR);

        $size   = filesize($destinationName);
        $sizes  = getimagesize($destinationName);
        $width  = $sizes[0];
        $height = $sizes[1];

        $json = array(
            'taille' => $sizes[0] . ' x ' . $sizes[1],
            'filename' => $fileNameNew,
            'size' => $size,
            'width' => $width,
            'height' => $height,
            'path' => $targetDir . DS . $fileNameNew,
            'date' => date('d/m/Y H:i:s'),
        );

        /** On créé la vignette */
        $largeurmax = self::$vignette['max-width'];
        $hauteurmax = self::$vignette['max-height'];
        $this->vignette(
            $uploadDir . DS . $targetDir . DS . $fileNameNew,
            $ext,
            $uploadDir . DS . $vignetteDir . DS . $fileNameNew,
            $largeurmax,
            $hauteurmax
        );
        $jsonrpc['minipath'] = $vignetteDir . DS . $fileNameNew;

        /** On créé l'apercu */
        $largeurmax = self::$apercu['max-width'];
        $hauteurmax = self::$apercu['max-height'];
        $this->vignette(
            $uploadDir . DS . $targetDir . DS . $fileNameNew,
            $ext,
            $uploadDir . DS . $apercuDir . DS . $fileNameNew,
            $largeurmax,
            $hauteurmax
        );

        /** On insert la ressource en base */
        $json['id'] = $this->insertToMediaFile(
            $json['filename'],
            $idGabPage,
            $idTemp,
            $json['size'],
            $json['width'],
            $json['height']
        );

        return $json;
    }

    /**
     * Insert un fichier (lié à une page) en base de donnée
     *
     * @param string $fileName  Nom du fichier
     * @param int    $idGabPage Identifiant de la page (si elle est en cours d'édition)
     * @param int    $idTemp    Identifiant temporaire de la page (si elle est en cours de création)
     * @param int    $size      Taille (en octets) du fichier
     * @param int    $width     Si le fichier est une image alors ceci est la largeur de l'image
     * @param int    $height    Si le fichier est une image alors ceci est la hauteur de l'image
     *
     * @return int
     */
    public function insertToMediaFile(
        $fileName,
        $idGabPage,
        $idTemp,
        $size,
        $width,
        $height
    ) {
        $query  = 'INSERT INTO `' . $this->mediaTableName . '` SET'
                . ' `rewriting` = ' . $this->db->quote($fileName) . ','
                . ' `id_gab_page` = ' . $idGabPage . ','
                . ' `id_temp` = ' . $idTemp . ','
                . ' `taille` = ' . $this->db->quote($size) . ','
                . ' `width` = ' . $width . ','
                . ' `height` = ' . $height . ','
                . ' `vignette` = ' . $this->db->quote($fileName) . ','
                . ' `date_crea` = NOW()';
        $this->db->exec($query);
        return $this->db->lastInsertId();
    }

    /**
     * Crée une version redimenssionnée d'une image
     *
     * @param string $fileSource      Fichier à redimensionner
     * @param string $ext             Extension du fichier à redimensionner
     * @param string $destinationName Nom du fichier redimensionné
     * @param int    $largeurmax      Largeur maximum de l'image redimensionnée
     * @param int    $hauteurmax      Hauteur maximum de l'image redimensionnée
     *
     * @return bool
     */
    public function vignette(
        $fileSource,
        $ext,
        $destinationName,
        $largeurmax,
        $hauteurmax
    ) {
        if (!array_key_exists($ext, self::$extensions['image'])) {
            return false;
        }

        $source = call_user_func(
            'imagecreatefrom' . self::$extensions['image'][$ext],
            $fileSource
        );

        /**
         * Les fonctions imagesx et imagesy renvoient la largeur et la hauteur
         * d'une image
         */
        $largeurSource = imagesx($source);
        $hauteurSource = imagesy($source);

        if (
            $largeurmax != '*' && $largeurSource > $largeurmax
            || $hauteurmax != '*' && $hauteurSource > $hauteurmax
        ) {
            if ($largeurmax == '*') {
                $ratioL = 1;
            } else {
                $ratioL = $largeurSource / $largeurmax;
            }

            if ($hauteurmax == '*') {
                $ratioH = 1;
            } else {
                $ratioH = $hauteurSource / $hauteurmax;
            }

            $ratio = max(array($ratioH, $ratioL));

            $largeurDestination = $largeurSource / $ratio;
            $hauteurDestination = $hauteurSource / $ratio;

            $destination = imagecreatetruecolor(
                $largeurDestination,
                $hauteurDestination
            );

            /** Transparence */
            if ($ext == 'png' || $ext == 'gif') {
                imagealphablending($destination, false);
                imagesavealpha($destination, true);
            }

            /** On crée la miniature */
            imagecopyresampled(
                $destination,
                $source,
                0,
                0,
                0,
                0,
                $largeurDestination,
                $hauteurDestination,
                $largeurSource,
                $hauteurSource
            );

            if ($ext == 'png') {
                call_user_func(
                    'image' . self::$extensions['image'][$ext],
                    $destination,
                    $destinationName,
                    0
                );
            } elseif ($ext == 'gif') {
                call_user_func(
                    'image' . self::$extensions['image'][$ext],
                    $destination,
                    $destinationName
                );
            } else {
                call_user_func(
                    'image' . self::$extensions['image'][$ext],
                    $destination,
                    $destinationName,
                    95
                );
            }
        } else {
            copy($fileSource, $destinationName);
        }

        return true;
    }

    /**
     * Enregistre la table média
     *
     * @param string $mediaTableName Nom de la table média
     *
     * @return void
     */
    public function setMediaTableName($mediaTableName)
    {
        $this->mediaTableName = $mediaTableName;
    }
}
