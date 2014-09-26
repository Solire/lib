<?php
/**
 * Test class for path.
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\tests\unit;

use mageekguy\atoum as Atoum;

/**
 * Test class for path.
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Path extends Atoum
{
    /**
     * Contrôle ajout de dossiers dans l'include_path
     *
     * @return void
     */
    public function testAddPath()
    {
        $this
            ->boolean(\Solire\Lib\Path::addPath(__DIR__))
                ->isTrue()
            ->string(get_include_path())
                ->contains(PATH_SEPARATOR . __DIR__)
            ->boolean(\Solire\Lib\Path::addPath(__DIR__))
                ->isTrue()
            ->exception(function () {
                \Solire\Lib\Path::addPath('skldfjghsdlkfjghzieb');
            })
                ->hasMessage('Fichier introuvable : skldfjghsdlkfjghzieb')
                ->isInstanceOf('\Solire\Lib\Exception\Lib')
            ->if(touch(TEST_TMP_DIR . 'toto.txt'))
            ->boolean(\Solire\Lib\Path::addPath(TEST_TMP_DIR . 'toto.txt'))
                ->isTrue()
            ->and(unlink(TEST_TMP_DIR . 'toto.txt'))
            ->exception(function () {
                \Solire\Lib\Path::addPath(TEST_TMP_DIR . 'toto.txt');
            })
                ->hasMessage('Fichier introuvable : ' . TEST_TMP_DIR . 'toto.txt')
                ->isInstanceOf('\Solire\Lib\Exception\Lib')

        ;
    }

    /**
     * Contrôle test présence fichier
     *
     * @return void
     */
    public function testConstruct()
    {
        $this
            ->exception(function () {
                $path = new \Solire\Lib\Path('sdjfsl');
            })
                ->hasMessage('Fichier introuvable : sdjfsl')
                ->isInstanceOf('\Solire\Lib\Exception\Lib')
            ->if($path = new \Solire\Lib\Path(__FILE__))
            ->string($path->get())
                ->isEqualTo(__FILE__)
            ->if($path = new \Solire\Lib\Path(__DIR__))
            ->string($path->get())
                ->isEqualTo(__DIR__ . DIRECTORY_SEPARATOR)
            ->if($path = new \Solire\Lib\Path('sdjfsl', \Solire\Lib\Path::SILENT))
            ->boolean($path->get())
                ->isFalse()
        ;
    }

    /**
     * Contrôle convertion chaine de la classe
     *
     * @return void
     */
    public function testToString()
    {
        $this
            ->if($path = new \Solire\Lib\Path(__FILE__))
            ->string((string) $path)
                ->isEqualTo(__FILE__)
        ;
    }
}
