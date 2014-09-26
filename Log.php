<?php
/**
 * Log : Classe de log par fichier ou mysql
 *
 * @package    Library
 * @subpackage Core
 * @author     Benoit Raux <rauxbenoit@free.fr>
 * @license    Open Source (GPL)
 */

namespace Solire\Lib;

/**
 * Log : Classe de log par fichier ou mysql
 *
 * @package    Library
 * @subpackage Core
 * @author     Benoit Raux <rauxbenoit@free.fr>
 * @license    Open Source (GPL)
 *
 *
 * Par defaut et en mode fichier, la classe op?re une rotation sur les logs.
 * D?s que le fichier courant d?passe 5 Mo, il est compress? (gz).
 * La visualization des logs parcours ces archives.
 * La purge efface ces archives.
 *
 * Requis pour la rotation par archivage : gzencode, gzopen, gzread, gzclose ...
 *
 *
 * Documentation Technique :
 * -------------------------
 * Instanciation : ($strToFile, $strToLog='', $strTable='logs', $booArch=true)
 * 	- Mode fichier
 * 	$log = new Log('Chemin vers fichier','En option une chaine ? logger')
 * 	- Mode MySql
 * 	$log = new Log('mysql','En option une chaine ? loguer','En option le nom de la table')
 *    - $booArch= true ou false : Active/Desactive le systeme de roullement en archive
 * Purge des Log :  purgeLog($strDateTime='0000-00-00 00:00:00')
 * 	$log->purgeLog(); => vas purger tous les logs
 * 	$log->purgeLog('dateTime); => vas purger tous les logs avant datetime
 * Visionner les logs :
 *  visuLog($strDateTim:eIni='0000-00-00 00:00:00',$strDateTimeEnd='0000-00-00 00:00:00',
 * 			$strContentToSearch='',$booDetail=true,$strIp='')
 * 	$log->visuLog('strDateTimeIni') => vas montrer tous les logs depuis cette date
 * 	$log->visuLog('strDateTimeIni','strDateTimeEnd') => vas montrer tous les logs entre les 2 dates
 * 	$strContentToSearch : permet de faire une recherche sur les logs contenant $strContentToSearch
 * 	$booDetail=true or false : Active / desactive l'affichage des d?tails (Date / Heure / Ip)
 * 	$strIp : permet de faire une recherche sur les log de l'ip $strIp
 *
 */
class Log
{
    /**
     * Pointeur vers le fichier ouvert
     *
     * @var object
     */
    private $objFile;

    /**
     * Pointeur vers la base de donnée
     *
     * @var MyPDO
     */
    private $db;

    /**
     * L'ip executant le log
     *
     * @var string
     */
    private $strIp;

    /**
     * Le mode du log
     *
     * @var string
     */
    private $strLogMode;

    /**
     * Le nom de la table de log
     *
     * @var string
     */
    private $strLogTable;

    /**
     * la taille de fichier maximum
     *
     * @var int
     */
    private $intFileSizeMax;

    /**
     * Archivage actif ou non
     *
     * @var bool
     */
    private $booArch;

    /**
     * Constructeur de la classe log
     *
     * @param string $strToFileOrDbConnect Chemin vers le fichier de log ( ? cr?er ou ? suivre).
     * 	Si type db, on vas loguer dans la table $strTable
     * @param string $strToLog             Une chaine à logguer dès
     * l'intanciation (optionelle).
     * @param int    $idUser               identifiant utilisateur ??
     * @param string $strTable             Un nom de table mysql
     * pour les logs (optionelle, par defaut : logs).
     * @param bool   $booArch              Active ou Desactive
     * le système de rotation par archivage.
     */
    public function __construct(
        $strToFileOrDbConnect,
        $strToLog = '',
        $idUser = 0,
        $strTable = 'logs',
        $booArch = true
    ) {
        //test si on a une IP (en php-cli par exemple on a pas l'ip)
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $this->strIp = $_SERVER['REMOTE_ADDR'];
        } else {
            $this->strIp = 'NO IP';
        }
        //On test si on doit ouvrir un fichier en écriture, sinon on test existance de la table)
        if (!is_string($strToFileOrDbConnect)) {
            $this->strLogMode = 'mysql';
            $this->strLogTable = $strTable;
            $this->db = $strToFileOrDbConnect;
            //Si table n'existe pas on la cr?er
            $this->createTable();
        } else {
            $this->booArch = $booArch;
            $this->strLogMode = 'file';
            $this->strToFile = $strToFile;
            $this->intFileSizeMax = 5 * 1024 * 1024;
        }
        //test si la chaine ?logguer est pr?sente
        if ($strToLog != '') {
            //On log
            $this->logThis($strToLog, $idUser);
        }
    }

    /**
     * mysqlQuery : Execute une requette
     *
     * @param string $strReq le ficher
     *
     * @return ressource resultat de la requête sql
     */
    private function mysqlQuery($strReq)
    {
        $objResult = $this->db->query($strReq);
        try {
            if ($objResult == false) {
                $strError = $reqTable . ' : ' . mysql_error() . "\n";
                throw new \Solire\Lib\Exception\Log($strError);
            }
        } catch (Exception $objExpetion) {
            $objExpetion->makeLogExeption();
        }
        return $objResult;
    }

    /**
     * createTable : Créé la table de log si elle existe pas
     *
     * @return void
     */
    private function createTable()
    {
        $reqTable = '
				CREATE TABLE IF NOT EXISTS `' . $this->strLogTable . '` (
				`logId` INT( 100 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`logDate` DATE NOT NULL ,
				`logTime` TIME NOT NULL ,
				`logIp` VARCHAR( 100 ) NOT NULL ,
				`logString` VARCHAR( 1000 ) NOT NULL,
				`logStrOpt` VARCHAR( 1000 ) NOT NULL,
				`logIdUser` INT(100 ) NOT NULL
			)';

        $objResult = $this->mysqlQuery($reqTable);
    }

    /**
     * openLogFile : Ouvre le fichier de Log
     *
     * @return void
     */
    private function openLogFile()
    {
        //On vide la cache sur le syst?me de fichier
        clearstatcache();
        //Si le fichier existe -> Verifie la taile avant
        if (file_exists($this->strToFile)) {
            if ($this->booArch === true) {
                $intFileSize = filesize($this->strToFile);
                if ($intFileSize >= $this->intFileSizeMax) {
                    //On archive le fichier
                    $strFileContent = file_get_contents($this->strToFile);
                    $strToFileGz = $this->strToFile . '.' . date('Ymd_His') . '.gz';
                    $strFileContentGz = gzencode($strFileContent, 9);
                    $objFileGz = $this->fopenLogFile($strToFileGz, 'w+');
                    fwrite($objFileGz, $strFileContentGz);
                    fclose($objFileGz);
                    //On suprime le fichier
                    unlink($this->strToFile);
                    //On l'ouvre en ecriture
                    $this->objFile = $this->fopenLogFile($this->strToFile, 'w+');
                } else {
                    //On l'ouvre en append
                    $this->objFile = $this->fopenLogFile($this->strToFile, 'a+');
                }
            } else {
                //On l'ouvre en append
                $this->objFile = $this->fopenLogFile($this->strToFile, 'a+');
            }
        } else {
            $this->objFile = $this->fopenLogFile($this->strToFile, 'w+');
        }
    }

    /**
     * openLogFile : Ouvre le fichier de Log
     *
     * @param string $strFile : le ficher
     * @param string $strMode : le mode d'ouverture
     *
     * @return resource file pointer
     */
    private function fopenLogFile($strFile, $strMode)
    {
        $objFile = fopen($strFile, $strMode);
        try {
            if ($objFile == false) {
                $strError = 'Erreur d\'ouverture du fichier ' . $strFile
                          . ' en mode ' . $strMode . '!' . "\n"
                          . 'Vérifiez l\'existance et les droits sur vos fichiers...';
                throw new \Solire\Lib\Exception\Log($strError);
            }
        } catch (Exception $objExpetion) {
            $objExpetion->makeLogExeption();
        }
        return $objFile;
    }

    /**
     * logThis : Ecrit la chaine $strToLog
     *
     * @param string $strToLog  chaîne a ajouter dans le log
     * @param int    $idUser    identifiant utilisateur ???
     * @param string $logStrOpt informations suplémentaires ???
     *
     * @return void
     */
    public function logThis($strToLog, $idUser = 0, $logStrOpt = '')
    {
        switch ($this->strLogMode) {
            case 'mysql':
                $this->logThisInTable($strToLog, $idUser, $logStrOpt);
                break;
            case 'file':
                $strToLog = str_replace(array("\n", "\r"), array(' ', ' '), $strToLog);
                $this->openLogFile();
                $this->logThisInFile($strToLog);
                $this->closeLogFile();
                break;
        }
    }

    /**
     * logThisInFile : Ecrit la chaine $strToLog dans le fichier
     *
     * @param string $strToLog chaîne a ajouter dans le log
     *
     * @return void
     */
    private function logThisInFile($strToLog)
    {
        fwrite(
            $this->objFile,
            date('Y-m-d') . "\t" . date('H:i:s') . "\t" . $this->strIp . "\t" . $strToLog . "\n"
        );
    }

    /**
     * logThisInTable : Ecrit la chaine $strToLog dans la table mysql
     *
     * @param string $strToLog cha?ne a ajouter dans le log
     * @param int    $idUser   identifiant utilisateur ???
     * @param string $strOpt   informations suplémentaires ???
     *
     * @return void
     */
    private function logThisInTable($strToLog, $idUser, $strOpt)
    {
        $reqLog = 'INSERT INTO `' . $this->strLogTable . '` '
                . '(`logDate`,`logTime`,`logIp`,`logString`,`logStrOpt`, `logIdUser`) '
                . 'VALUES('
                . '\'' . date('Y-m-d') . '\','
                . '\'' . date('H:i:s') . '\','
                . '\'' . $this->strIp . '\','
                . '' . $this->db->quote($strToLog) . ','
                . '' . $this->db->quote($strOpt) . ','
                . '' . $idUser . ''
                . '); ';
        $objResult = $this->mysqlQuery($reqLog);
    }

    /**
     * closeLogFile : Ferme le fichier de log en cours
     *
     * @return void
     */
    private function closeLogFile()
    {
        fclose($this->objFile);
    }

    /**
     * purgeLog : Purge le log selon une date / heure
     * (supprime tous les enregistrement ant?rieurs ? la date pass?e en param?tre)
     *
     * @param string $strDateTime : chaine au format datetime (Y-m-d H:i:s)
     * 	limite pour la suppr?ssion des logs
     *
     * @return void
     */
    public function purgeLog($strDateTime = '0000-00-00 00:00:00')
    {
        //Si par d?faut
        if ($strDateTime == '0000-00-00 00:00:00') {
            $strDateTime = date('Y-m-d H:i:s');
        }
        //Controle datetime
        if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/', $strDateTime)) {
            switch ($this->strLogMode) {
                case 'mysql':
                    $this->purgeLogTable($strDateTime);
                    break;
                case 'file':
                    $this->purgeLogFiles($strDateTime);
                    $this->purgeLogCurentFile($strDateTime);
                    break;
            }
        } else {
            try {
                throw new \Solire\Lib\Exception\Log($strDateTime . ' N\'est pas au bon format');
            } catch (Exception $objExpetion) {
                $objExpetion->makeLogExeption();
            }
        }
    }

    /**
     * purgeLogTable : Purge la table de log selon une date / heure
     * (supprime tous les enregistrement antérieurs à la date passée en paramètre
     *  dans la table mysql de log)
     *
     * @param string $strDateTime chaine au format datetime (Y-m-d H:i:s)
     * 	limite pour la suppression des logs
     *
     * @return void
     */
    private function purgeLogTable($strDateTime)
    {
        $strDate = substr($strDateTime, 0, 10);
        $strTime = substr($strDateTime, 11, 8);
        $reqPurge = 'DELETE FROM `' . $db->quote($this->strLogTable) . '` '
                  . 'WHERE `logDate` < \'' . $strDate . '\' '
                  . 'OR (`logDate` = \'' . $strDate . '\' AND `logTime` <= \'' . $strTime . '\') ;';
        $objResult = $this->mysqlQuery($reqPurge);
    }

    /**
     * dateToTimestamp : Renvoit le timestamp d'une date MYSQL.
     *
     * @param string $strDateTime : chaine au format datetime MYSQL
     *
     * @return int
     */
    private function dateToTimestamp($strDateTime)
    {
        return strtotime($strDateTime);
    }

    /**
     * purgeLogCurentFile : Purge le fichier log en cours selon une date / heure
     * (supprime tous les enregistrement ant?rieurs ? la date pass?e en param?tre)
     *
     * @param string $strDateTime : chaine au format datetime (Y-m-d H:i:s)
     * 	limite pour la suppression des logs
     *
     * @return void
     */
    private function purgeLogCurentFile($strDateTime)
    {
        $intTimeStampeDate = $this->dateToTimestamp($strDateTime);
        $strContent = '';
        $arrLineContent = array();
        //Ouverture du fichier en lecture
        $this->objFile = $this->fopenLogFile($this->strToFile, 'r+');
        //Parcour tous le fichier
        while (!feof($this->objFile)) {
            $strLineContent = fgets($this->objFile);
            $arrLineContent = explode("\t", $strLineContent);
            if (sizeof($arrLineContent) > 1) {
                $strDateLine = $arrLineContent[0];
                $strTimeLine = $arrLineContent[1];
                $strDateTimeLine = $strDateLine . ' ' . $strTimeLine;
                $intTimeStampLine = $this->dateToTimestamp($strDateTimeLine);
                //si la ligne respecte les crit?res de date on la stock
                if ($intTimeStampLine > $intTimeStampeDate) {
                    $strContent .= $strLineContent;
                }
            }
        }
        //On ferme le fichier
        $this->closeLogFile();
        //On suprime le fichier
        unlink($this->strToFile);
        //On l'ouvre en ecriture
        $this->objFile = $this->fopenLogFile($this->strToFile, 'w+');
        fwrite($this->objFile, $strContent);
        $this->closeLogFile();
    }

    /**
     * purgeLogFiles : Purge les fichier log archivés selon une date / heure
     * (supprime tous les enregistrement antérieurs à la date passée en paramètre
     * dans les fichier de log archivés)
     *
     * @param string $strDateTime chaine au format datetime (Y-m-d H:i:s)
     * 	limite pour la suppression des logs
     *
     * @return void
     */
    public function purgeLogFiles($strDateTime)
    {
        $intTimeStampeDate = $this->dateToTimestamp($strDateTime);
        //vas chercher la listes des fichiers archiv?s
        $arrArchFiles = glob($this->strToFile . '.*.gz');
        foreach ($arrArchFiles as $strPathToArch) {
            $strDateArch = str_replace(
                array($this->strToFile . '.', '.gz'), array(''), $strPathToArch
            );
            $strDateTimeArch = substr($strDateArch, 0, 4) . '-'
                             . substr($strDateArch, 4, 2) . '-'
                             . substr($strDateArch, 6, 2) . ' '
                             . substr($strDateArch, 9, 2) . ':'
                             . substr($strDateArch, 11, 2) . ':'
                             . substr($strDateArch, 13, 2);
            $intTimeStampArch = $this->dateToTimestamp($strDateTimeArch);
            if ($intTimeStampArch <= $intTimeStampeDate) {
                unlink($strPathToArch);
            }
        }
    }

    /**
     * visuLog : Fonction pour afficher les logs dans la page
     *
     * @param string $strDateTimeIni     dateTime (AAAA-MM-DD HH-MM-SS) de debut
     * @param string $strDateTimeEnd     dateTime (AAAA-MM-DD HH-MM-SS) de fin,
     * si = 0000-00-00 00:00:00 : pas de date de fin
     * @param string $strContentToSearch un contenu recherch? (optionel)
     * @param bool   $booDetail          affichage des detail actif ou non (Date / Heure / Ip)
     * @param string $strIp              une ip recherch?e (optionel)
     *
     * @return void
     */
    public function visuLog(
        $strDateTimeIni = '0000-00-00 00:00:00',
        $strDateTimeEnd = '0000-00-00 00:00:00',
        $strContentToSearch = '',
        $booDetail = true,
        $strIp = ''
    ) {
        //Controle datetime
        $pattern = '/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/';
        if (preg_match($pattern, $strDateTimeIni)) {

        } else {
            try {
                throw new \Solire\Lib\Exception\Log(
                    'Date Ini : ' . $strDateTimeIni . ' N\'est pas au bon format'
                );
            } catch (Exception $objExpetion) {
                $objExpetion->makeLogExeption();
            }
        }
        if (preg_match($pattern, $strDateTimeEnd)) {

        } else {
            try {
                throw new \Solire\Lib\Exception\Log(
                    'Date End : ' . $strDateTimeEnd . ' N\'est pas au bon format'
                );
            } catch (Exception $objExpetion) {
                $objExpetion->makeLogExeption();
            }
        }
        //Si pas de date de fin -> on met la date actuel
        if ('0000-00-00 00:00:00' == $strDateTimeEnd) {
            $strDateTimeEnd = date('Y-m-d H:i:s');
        }
        $strContent = $this->returnLog(
            $strDateTimeIni, $strDateTimeEnd, $strContentToSearch, $booDetail, $strIp
        );
        echo '<pre>' . $strContent . '</pre>';
    }

    /**
     * returnLog : Fonction pour r?cup?rer le contenu des log dans une chaine
     *
     * @param string $strDateTimeIni     dateTime (AAAA-MM-DD HH-MM-SS) de debut
     * @param string $strDateTimeEnd     dateTime (AAAA-MM-DD HH-MM-SS) de fin,
     * si = 0000-00-00 00:00:00 : pas de date de fin
     * @param string $strContentToSearch un contenu recherch? (optionel)
     * @param bool   $booDetail          affichage des detail actif ou non (Date / Heure / Ip)
     * @param string $strIp              une ip recherch?e (optionel)
     *
     * @return string : contenu des logs
     */
    private function returnLog(
        $strDateTimeIni = '0000-00-00 00:00:00',
        $strDateTimeEnd = '0000-00-00 00:00:00',
        $strContentToSearch = '',
        $booDetail = true,
        $strIp = ''
    ) {
        $pattern = '/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/';
        //Controle datetime
        if (preg_match($pattern, $strDateTimeIni)) {

        } else {
            try {
                throw new \Solire\Lib\Exception\Log(
                    'Date Ini : ' . $strDateTimeIni . ' N\'est pas au bon format'
                );
            } catch (Exception $objExpetion) {
                $objExpetion->makeLogExeption();
            }
        }

        if (preg_match($pattern, $strDateTimeEnd)) {

        } else {
            try {
                throw new \Solire\Lib\Exception\Log(
                    'Date End : ' . $strDateTimeEnd . ' N\'est pas au bon format'
                );
            } catch (Exception $objExpetion) {
                $objExpetion->makeLogExeption();
            }
        }
        //Si pas de date de fin -> on met la date actuel
        if ('0000-00-00 00:00:00' == $strDateTimeEnd) {
            $strDateTimeEnd = date('Y-m-d H:i:s');
        }
        $strContent = '';
        //Switch du mode
        switch ($this->strLogMode) {
            case 'mysql':
                $strContent = $this->returnLogMysqlContent(
                    $strDateTimeIni, $strDateTimeEnd,
                    $strContentToSearch, $booDetail, $strIp
                );
                break;
            case 'file':
                $strContent .= $this->returnLogArchFilesContent(
                    $strDateTimeIni, $strDateTimeEnd,
                    $strContentToSearch, $booDetail, $strIp
                );
                $strContent .= $this->returnLogFileContent(
                    $strDateTimeIni, $strDateTimeEnd,
                    $strContentToSearch, $booDetail, $strIp
                );
                break;
        }
        return $strContent;
    }

    /**
     * returnLogArchFilesContent : Fonction qui retourne le contenu des fichiers de log Archivés
     *
     * @param string $strDateTimeIni     dateTime (AAAA-MM-DD HH-MM-SS) de debut
     * @param string $strDateTimeEnd     dateTime (AAAA-MM-DD HH-MM-SS) de fin,
     * si = 0000-00-00 00:00:00 : pas de date de fin
     * @param string $strContentToSearch un contenu recherch? (optionel)
     * @param bool   $booDetail          affichage des detail actif ou non (Date / Heure / Ip)
     * @param string $strIp              une ip recherch?e (optionel)
     *
     * @return string : contenu des logs
     */
    private function returnLogArchFilesContent(
        $strDateTimeIni,
        $strDateTimeEnd,
        $strContentToSearch,
        $booDetail,
        $strIp
    ) {
        $strContentRequired = '';
        $intTimeStampDateIni = $this->dateToTimestamp($strDateTimeIni);
        $intTimeStampDateEnd = $this->dateToTimestamp($strDateTimeEnd);
        //Parcour des fichiers de log archiv?s
        $arrArchFiles = glob($this->strToFile . '.*.gz');
        //Declaration du tableau des fichiers archive ? parser
        $arrFilesToParse = array();
        foreach ($arrArchFiles as $strPathToArch) {
            $strDateArch = str_replace(
                array($this->strToFile . '.', '.gz'), array(''), $strPathToArch
            );
            $strDateTimeArch = substr($strDateArch, 0, 4) . '-'
                             . substr($strDateArch, 4, 2) . '-'
                             . substr($strDateArch, 6, 2) . ' '
                             . substr($strDateArch, 9, 2) . ':'
                             . substr($strDateArch, 11, 2) . ':'
                             . substr($strDateArch, 13, 2);
            $intTimeStampArch = $this->dateToTimestamp($strDateTimeArch);
            if ($intTimeStampArch >= $intTimeStampDateIni) {
                $arrFilesToParse[] = $strPathToArch;
            }
        }
        //Lecture des fichiers à parser
        foreach ($arrFilesToParse as $strPathToArch) {
            $objFileArch = gzopen($strPathToArch, 'r');
            $strArchContent = '';
            while (!feof($objFileArch)) {
                $strArchContent .= gzread($objFileArch, 10000);
            }
            gzclose($objFileArch);
            //Creation d'un tableau contenant lignes et du log archiv?
            $arrArchLineContent = array();
            $arrArchLineContent = explode("\n", $strArchContent);
            foreach ($arrArchLineContent as $strLineArch) {
                $arrArchColContent = array();
                $arrArchColContent = explode("\t", $strLineArch);
                //Si la ligne est pas vide
                if (sizeof($arrArchColContent) > 1) {
                    $intTimeStampLine = $this->dateToTimestamp(
                            $arrArchColContent[0] . ' ' . $arrArchColContent[1], 'MYSQL'
                    );
                    if ($intTimeStampLine >= $intTimeStampDateIni
                        && $intTimeStampLine <= $intTimeStampDateEnd
                    ) {
                        $booLineCheck = true;
                        //Test notre crit?re de contenu
                        if ($strContentToSearch != '') {
                            $strLineContentToCheck = str_replace(
                                            $arrArchColContent[0] . "\t"
                                            . $arrArchColContent[1] . "\t"
                                            . $arrArchColContent[2] . "\t", '', $strLineArch
                                    ) . "\n";
                            if (!strstr($strLineContentToCheck, $strContentToSearch)) {
                                $booLineCheck = false;
                            }
                        }
                        //Test crit?re d'ip
                        if ($strIp != '' && !strstr($arrArchColContent[2], $strIp)) {
                            $booLineCheck = false;
                        }
                        if ($booLineCheck === true) {
                            //La ligne corespond a notre recherhe
                            if ($booDetail === true) {
                                $strContentRequired .= $strLineArch . "\n";
                            } else {
                                $strContentRequired .= str_replace(
                                                $arrArchColContent[0] . "\t"
                                                . $arrArchColContent[1] . "\t"
                                                . $arrArchColContent[2] . "\t", '', $strLineArch
                                        ) . "\n";
                            }
                        }
                    } elseif ($intTimeStampLine > $intTimeStampDateEnd) {
                        //On a d?pass? notre crit?re de date de fin
                        //=> on sort de la fonction pas la peine de continuer
                        return $strContentRequired;
                    }
                }
            }
        }
        //Retourne le contenu
        return $strContentRequired;
    }

    /**
     * returnLogFileContent : Fonction qui retourne le contenu du fichier de log en cours
     *
     * @param string $strDateTimeIni     dateTime (AAAA-MM-DD HH-MM-SS) de debut
     * @param string $strDateTimeEnd     dateTime (AAAA-MM-DD HH-MM-SS) de fin,
     * si = 0000-00-00 00:00:00 : pas de date de fin
     * @param string $strContentToSearch un contenu recherch? (optionel)
     * @param bool   $booDetail          affichage des detail actif ou non (Date / Heure / Ip)
     * @param string $strIp              une ip recherch?e (optionel)
     *
     * @return string contenu de la table de log
     */
    private function returnLogFileContent(
        $strDateTimeIni,
        $strDateTimeEnd,
        $strContentToSearch,
        $booDetail,
        $strIp
    ) {
        $intTimeStampDateIni = $this->dateToTimestamp($strDateTimeIni);
        $intTimeStampDateEnd = $this->dateToTimestamp($strDateTimeEnd);
        $strContentRequired = '';
        $arrLineContent = array();
        //Ouverture du fichier en lecture
        $this->objFile = $this->fopenLogFile($this->strToFile, 'r');
        //Parcour tous le fichier
        while (!feof($this->objFile)) {
            $strLineContent = fgets($this->objFile);
            $arrLineContent = explode("\t", $strLineContent);
            if (sizeof($arrLineContent) > 1) {
                $strDateLine = $arrLineContent[0];
                $strTimeLine = $arrLineContent[1];
                $strDateTimeLine = $strDateLine . ' ' . $strTimeLine;
                $intTimeStampLine = $this->dateToTimestamp($strDateTimeLine);
                if ($intTimeStampLine >= $intTimeStampDateIni
                    && $intTimeStampLine <= $intTimeStampDateEnd
                ) {
                    $booLineCheck = true;
                    //Test notre crit?re de contenu
                    if ($strContentToSearch != '') {
                        $strLineContentToCheck = str_replace(
                                $arrLineContent[0] . "\t"
                                . $arrLineContent[1] . "\t"
                                . $arrLineContent[2] . "\t", '', $strLineContent
                        );
                        if (!strstr($strLineContentToCheck, $strContentToSearch)) {
                            $booLineCheck = false;
                        }
                    }
                    //Test crit?re d'ip
                    if ($strIp != '' && !strstr($arrLineContent[2], $strIp)) {
                        $booLineCheck = false;
                    }
                    if ($booLineCheck === true) {
                        //La ligne corespond a notre recherhe
                        if ($booDetail === true) {
                            $strContentRequired .= $strLineContent;
                        } else {
                            $strContentRequired .= str_replace(
                                    $arrLineContent[0] . "\t"
                                    . $arrLineContent[1] . "\t"
                                    . $arrLineContent[2] . "\t", '', $strLineContent
                            );
                        }
                    }
                } elseif ($intTimeStampLine > $intTimeStampDateEnd) {
                    //On a d?pass? notre crit?re de date de fin
                    //=> on sort de la fonction pas la peine de continuer
                    $this->closeLogFile();
                    return $strContentRequired;
                }
            }
        }
        //On ferme le fichier
        $this->closeLogFile();
        return $strContentRequired;
    }

    /**
     * returnLogMysqlContent : Fonction qui retourne le contenu de la table log
     *
     * @param string $strDateTimeIni     dateTime (AAAA-MM-DD HH-MM-SS) de debut
     * @param string $strDateTimeEnd     dateTime (AAAA-MM-DD HH-MM-SS) de fin,
     * si = 0000-00-00 00:00:00 : pas de date de fin
     * @param string $strContentToSearch un contenu recherch? (optionel)
     * @param bool   $booDetail          affichage des detail actif ou non (Date / Heure / Ip)
     * @param string $strIp              une ip recherch?e (optionel)
     *
     * @return string contenu de la table de log
     */
    private function returnLogMysqlContent(
        $strDateTimeIni,
        $strDateTimeEnd,
        $strContentToSearch,
        $booDetail,
        $strIp
    ) {
        $strContentRequired = '';
        $strDateIni = substr($strDateTimeIni, 0, 10);
        $strTimeIni = substr($strDateTimeIni, 11, 8);
        $strDateEnd = substr($strDateTimeEnd, 0, 10);
        $strTimeEnd = substr($strDateTimeEnd, 11, 8);
        $reqLogContent = 'SELECT * FROM `' . $this->db->quote($this->strLogTable) . '` '
                       . 'WHERE ( '
                       . '`logDate` < \'' . $strDateEnd . '\' '
                       . 'OR ( '
                       . '`logDate` = \'' . $strDateEnd . '\' '
                       . 'AND `logTime` <= \'' . $strTimeEnd . '\' '
                       . ') '
                       . ') '
                       . 'AND ( '
                       . '`logDate` > \'' . $strDateIni . '\' '
                       . 'OR ('
                       . '	`logDate` = \'' . $strDateIni . '\' '
                       . 'AND `logTime` >= \'' . $strTimeIni . '\''
                       . ') '
                       . ') ';
        if ($strIp != '') {
            $reqLogContent .= 'AND `logIp` LIKE \'%'
                            . $this->db->quote($strIp) . '%\' ';
        }
        if ($strContentToSearch != '') {
            $reqLogContent .= 'AND `logString` LIKE '
                           . '\'%' . $this->db->quote($strContentToSearch)
                           . '%\' ';
        }
        $recLogContent = $this->mysqlQuery($reqLogContent);
        if (mysql_num_rows($recLogContent)) {
            while ($ojbLogContent = mysql_fetch_object($recLogContent)) {
                //La ligne corespond a notre recherhe
                if ($booDetail === true) {
                    $strContentRequired .= $ojbLogContent->logDate . "\t"
                            . $ojbLogContent->logTime . "\t"
                            . $ojbLogContent->logIp . "\t"
                            . $ojbLogContent->logString . "\n";
                } else {
                    $strContentRequired .= $ojbLogContent->logString . "\n";
                }
            }
        }
        return $strContentRequired;
    }

    /**
     * _toString : Fonction "magic" qui retourne l'objet sous forme de chaine
     *
     * @return string
     */
    public function __toString()
    {
        return '<pre>' . print_r($this, true) . '</pre>';
    }
}

