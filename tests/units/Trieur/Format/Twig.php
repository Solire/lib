<?php

namespace Solire\Lib\tests\unit\Trieur\Format;

use mageekguy\atoum as Atoum;
use Solire\Conf\Loader;
use Solire\Lib\View\Filesystem\FileLocator;
use Solire\Lib\Registry;
use Solire\Lib\Trieur\Format\Twig as testedClass;

/**
 * Test class for path.
 */
class Twig extends Atoum
{
    const FILENAME = 'twigFile.twig';
    const FILENAME2 = 'twigFile2.twig';

    public function setUp()
    {
        mkdir(TEST_TMP_DIR . DIRECTORY_SEPARATOR . 'view');

        $content = 'rawName : {{ row.nom }}'
                 . PHP_EOL . 'rawFirstName : {{ row.prenom }}'
                 . PHP_EOL . 'formatedName : {{ cell }}';
        file_put_contents(TEST_TMP_DIR . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . self::FILENAME, $content);

        $content = '{{ titre }}'
                 . PHP_EOL .  'rawName : {{ row.nom }}'
                 . PHP_EOL . 'rawFirstName : {{ row.prenom }}'
                 . PHP_EOL . 'formatedName : {{ cell }}';
        file_put_contents(TEST_TMP_DIR . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . self::FILENAME2, $content);
    }

    public function tearDown()
    {
        unlink(TEST_TMP_DIR . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . self::FILENAME);
        unlink(TEST_TMP_DIR . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . self::FILENAME2);

        rmdir(TEST_TMP_DIR . DIRECTORY_SEPARATOR . 'view');
    }

    public function testConstructor01()
    {
        $viewFileLocator = new FileLocator([
            'Common' => [
              'name' => 'Common',
              'dir' => TEST_TMP_DIR,
              'namespace' => 'Common',
            ]
        ]);
        $viewFileLocator->setCurrentApplicationName('front');
        Registry::set('viewFileLocator', $viewFileLocator);

        $conf = Loader::load([]);
        $row = [
            'nom' => ' SOLIRE ',
            'prenom' => 'Développeur'
        ];
        $cell = 'Solire';

        $this
            ->exception(function()use($conf, $row, $cell){
                $t = $this->newTestedInstance($conf, $row, $cell);
            })
                ->hasMessage('Missing filename for the Solire\Trieur twig format conf')
        ;
    }

    public function testConstructor02()
    {
        $viewFileLocator = new FileLocator([
            'Common' => [
              'name' => 'Common',
              'dir' => TEST_TMP_DIR,
              'namespace' => 'Common',
            ]
        ]);
        $viewFileLocator->setCurrentApplicationName('front');
        Registry::set('viewFileLocator', $viewFileLocator);

        $conf = Loader::load([
            'fileName' => self::FILENAME,
        ]);
        $row = [
            'nom' => ' SOLIRE ',
            'prenom' => 'Développeur'
        ];
        $cell = 'Solire';

        $this
            ->if(
                $t = $this->newTestedInstance($conf, $row, $cell)
            )
        ;

        return $t;
    }

    public function testRender01()
    {
        $t = $this->testConstructor02();

        $this
            ->string(
                $t->render()
            )
                ->isEqualTo('rawName :  SOLIRE '
                . PHP_EOL . 'rawFirstName : Développeur'
                . PHP_EOL . 'formatedName : Solire'
            )
        ;

    }

    public function testConstructor03()
    {
        $viewFileLocator = new FileLocator([
            'Common' => [
              'name' => 'Common',
              'dir' => TEST_TMP_DIR,
              'namespace' => 'Common',
            ]
        ]);
        $viewFileLocator->setCurrentApplicationName('front');
        Registry::set('viewFileLocator', $viewFileLocator);

        $conf = Loader::load([
            'fileName' => self::FILENAME2,
            'context' => [
                'titre' => 'Hello World',
            ]
        ]);
        $row = [
            'nom' => ' SOLIRE ',
            'prenom' => 'Développeur'
        ];
        $cell = 'Solire';

        $this
            ->if(
                $t = $this->newTestedInstance($conf, $row, $cell)
            )
        ;

        return $t;
    }

    public function testRender02()
    {
        $t = $this->testConstructor03();

        $this
            ->string(
                $t->render()
            )
                ->isEqualTo('Hello World' . PHP_EOL . 'rawName :  SOLIRE '
                . PHP_EOL . 'rawFirstName : Développeur'
                . PHP_EOL . 'formatedName : Solire'
            )
        ;

    }
}
