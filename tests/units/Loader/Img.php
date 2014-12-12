<?php
/**
 * Test class for path.
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\tests\unit\Loader;

use mageekguy\atoum as Atoum;
use Solire\Lib\Loader\Img as TestClass;

/**
 * Test class for path.
 *
 * @author  Adrien <aimbert@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Img extends Atoum
{
    protected $dirRoot = TEST_TMP_DIR;

    protected $files = [
        [
            '01.jpg',

            '02.jpg',
            '04.jpg',
        ],
        [
            '01.jpg',

            '02.jpg',
            '03.jpg',
        ],
        [
            '01.jpg',

            '03.jpg',
            '04.jpg',
            '05.jpg',
        ],
    ];

    protected $dirs = [
        'a',
        'b',
        'c',
    ];

    public function setUp()
    {
        foreach ($this->dirs as $ii => $dir) {
            $dirPath = $this->dirRoot . DIRECTORY_SEPARATOR . $dir;
            mkdir($dirPath);

            foreach ($this->files[$ii] as $file) {
                $path = $dirPath . DIRECTORY_SEPARATOR . $file;
                touch($path);
            }
        }
    }

    public function tearDown()
    {
        foreach ($this->dirs as $ii => $dir) {
            $pathDir = $this->dirRoot . DIRECTORY_SEPARATOR . $dir;
            if (!file_exists($pathDir)) {
                continue;
            }

            foreach ($this->files[$ii] as $file) {
                $path = $pathDir . DIRECTORY_SEPARATOR . $file;
                if (!file_exists($path)) {
                    continue;
                }

                unlink($path);
            }

            rmdir($pathDir);
        }
    }

    /**
     * Instanciate a loader
     *
     * @return TestClass
     */
    protected function instanciate()
    {
        $loader = new TestClass($this->dirs, $this->dirRoot);
        return $loader;
    }

    /**
     * Contrôle du constructeur
     *
     * @return void
     */
    public function testConstruct()
    {
        $this
            ->exception(function(){
                new TestClass([]);
            })
                ->isInstanceOf('\Solire\Lib\Exception\Lib')
        ;

       $this
            ->object(new TestClass($this->dirs))
        ;

       $this
            ->object(new TestClass($this->dirs, $this->dirRoot . DIRECTORY_SEPARATOR))
        ;

        $this
            ->if($imgLoader = $this->instanciate())
            ->object($imgLoader)
                ->isInstanceOf('\Solire\Lib\Loader\Loader')
                ->isInstanceOf('\Solire\Lib\Loader\Img')
        ;
    }

    /**
     * Contrôle ajout d'une librairie
     *
     * @return void
     */
    public function testAdd()
    {
        $imgLoader = $this->instanciate();

        $this
            ->exception(function()use($imgLoader){
                $imgLoader->addLibrary('');
            })
                ->isInstanceOf('\Solire\Lib\Exception\Lib')
        ;

        $this
            ->if($imgLoader->addLibrary('01.jpg'))
            ->array($imgLoader->getLibrairies())
                ->hasKey('01.jpg')
                ->isEqualTo(['01.jpg' => []])
        ;

        $this
            ->if($imgLoader->addLibrary('02.jpg', ['class' => 'css-img']))
            ->array($imgLoader->getLibrairies())
                ->hasKey('01.jpg')
                ->hasKey('02.jpg')
                ->isEqualTo([
                    '01.jpg' => [],
                    '02.jpg' => [
                        'class' => 'css-img'
                    ],
                ])
        ;
    }

    public function testOutput()
    {
        $imgLoader = $this->instanciate();

        $this
            ->string($imgLoader->output('03.jpg', [
                'class' => 'css-img'
            ]))
                ->isEqualTo('<img class="css-img" src="b/03.jpg">')
        ;

        $this
            ->exception(function()use($imgLoader){
                $s = $imgLoader->output('100.jpg', [
                    'class' => 'css-img'
                ]);
            })
                ->isInstanceOf('Solire\Lib\Exception\Lib')
        ;

        $this
            ->if($s = $imgLoader->output('00.jpg', [
                'class' => 'css-img'
            ], true))
            ->string($s)
                ->isEqualTo('<img class="css-img" src="00.jpg">')
        ;

        $this
            ->if($imgLoader->addLibrary('01.jpg'))
            ->string($s = $imgLoader->outputAll())
                ->isEqualTo('<img src="a/01.jpg">')
        ;

        $this
            ->if($imgLoader->addLibrary('01.jpg'))
            ->and($imgLoader->addLibrary('02.jpg', [
                'class' => 'css-img'
            ]))
            ->and($imgLoader->addLibrary('03.jpg', [
                'class' => 'css-img'
            ]))
            ->and($imgLoader->addLibrary('05.jpg', [
                'class' => 'css-img',
                'alt' => 'oho',
            ]))
            ->string($imgLoader->outputAll())
                ->isEqualTo(
                    '<img src="a/01.jpg">'
                    . '<img class="css-img" src="a/02.jpg">'
                    . '<img class="css-img" src="b/03.jpg">'
                    . '<img class="css-img" alt="oho" src="c/05.jpg">'
                )
            ->string((string) $imgLoader)
                ->isEqualTo(
                    '<img src="a/01.jpg">'
                    . '<img class="css-img" src="a/02.jpg">'
                    . '<img class="css-img" src="b/03.jpg">'
                    . '<img class="css-img" alt="oho" src="c/05.jpg">'
                )
        ;
    }
}
