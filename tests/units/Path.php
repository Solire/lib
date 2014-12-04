<?php
/**
 * Test class for path.
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\tests\unit;

use mageekguy\atoum as Atoum;
use Solire\Lib\Path as TestClass;

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
            ->boolean(TestClass::addPath(__DIR__))
                ->isTrue()
            ->string(get_include_path())
                ->contains(PATH_SEPARATOR . __DIR__)
            ->boolean(TestClass::addPath(__DIR__))
                ->isTrue()
            ->exception(function () {
                TestClass::addPath('skldfjghsdlkfjghzieb');
            })
                ->hasMessage('Fichier introuvable : skldfjghsdlkfjghzieb')
                ->isInstanceOf('\Solire\Lib\Exception\Lib')
            ->if(touch(TEST_TMP_DIR . 'toto.txt'))
            ->boolean(TestClass::addPath(TEST_TMP_DIR . 'toto.txt'))
                ->isTrue()
            ->and(unlink(TEST_TMP_DIR . 'toto.txt'))
            ->exception(function () {
                TestClass::addPath(TEST_TMP_DIR . 'toto.txt');
            })
                ->hasMessage('Fichier introuvable : ' . TEST_TMP_DIR . 'toto.txt')
                ->isInstanceOf('\Solire\Lib\Exception\Lib')
        ;
    }

    public function testRealPath()
    {
        $this
            ->string(TestClass::realPath(__DIR__))
                ->isEqualTo(__DIR__)
        ;

        $tempDir = sys_get_temp_dir() . TestClass::DS . 'atoum-solire-lib';
        mkdir($tempDir);

        $target = $tempDir . TestClass::DS . 'target';
        mkdir($target);

        $link = $tempDir . TestClass::DS . 'link';
        symlink($target, $link);

        $this
            ->string(TestClass::realPath($link))
                ->isEqualTo($link)
        ;

        $this
            ->string(TestClass::realPath($link, true))
                ->isEqualTo($target)
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
                $path = new TestClass('sdjfsl');
            })
                ->hasMessage('Fichier introuvable : sdjfsl')
                ->isInstanceOf('\Solire\Lib\Exception\Lib')
            ->if($path = new TestClass(__FILE__))
            ->string($path->get())
                ->isEqualTo(__FILE__)
            ->if($path = new TestClass(__DIR__))
            ->string($path->get())
                ->isEqualTo(__DIR__)
            ->if($path = new TestClass('sdjfsl', TestClass::SILENT))
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
            ->if($path = new TestClass(__FILE__))
            ->string((string) $path)
                ->isEqualTo(__FILE__)
        ;
    }
}
