<?php

namespace Solire\Lib\tests\unit\Loader;

use mageekguy\atoum as Atoum;

/**
 * Test class for RequireJs loader.
 *
 * @author  thansen <thansen@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class RequireJs extends Atoum
{
    private $dirRoot = TEST_TMP_DIR;

    private $fileSystem = [
        'a' => [
            '01.js',
            '02.js',
            '04.js',

            'back' => [
                'js' => [
                    'modules02.js',
                ],
            ],
        ],
        'b' => [
            '01.js',
            '02.js',
            '03.js',

            'back' => [
                'js' => [
                    'modules01.js',
                    'modules02.js',
                    'modules03.js',
                ],
            ],
        ],
        'c' => [
            'd' => [
                '01.js',
                '03.js',
                '04.js',
                '05.js',

                'back' => [
                    'js' => [
                        'modules01.js',
                        'modules02.js',
                        'modules04.js',
                    ],
                ],
            ],
        ],
    ];

    private $dirs = [
        'a',
        'b',
        'c/d',
    ];

    private static $createdFiles = [];
    private static $createdDirs = [];

    private static function createFileSystem($dirRoot, $fileSystem)
    {
        foreach ($fileSystem as $name => $content) {
            if (is_int($name)) {
                touch($dirRoot . DIRECTORY_SEPARATOR . $content);
                self::$createdFiles[] = $dirRoot . DIRECTORY_SEPARATOR . $content;

                continue;
            }

            mkdir($dirRoot . DIRECTORY_SEPARATOR . $name);
            array_unshift(self::$createdDirs, $dirRoot . DIRECTORY_SEPARATOR . $name);

            self::createFileSystem($dirRoot . DIRECTORY_SEPARATOR . $name, $content);
        }
    }

    public function setUp()
    {
        self::createFileSystem($this->dirRoot, $this->fileSystem);
    }

    public function tearDown()
    {
        foreach (self::$createdFiles as $file) {
            unlink($file);
        }

        foreach (self::$createdDirs as $dir) {
            rmdir($dir);
        }
    }

    public function testConstruct1()
    {
        $this
            ->exception(function () {
                $this->newTestedInstance([]);
            })
                ->isInstanceOf('\Solire\Lib\Exception\Lib')
                ->hasMessage('Le loader ne doit pas être instancié avec une liste de dossier vide')
        ;
    }

    /**
     * @return \Solire\Lib\Loader\RequireJs
     */
    public function testConstruct2()
    {
        $this
            ->object($t = $this->newTestedInstance($this->dirs, $this->dirRoot))
        ;

        return $t;
    }

    public function testAdd1()
    {
        $t = $this->testConstruct2();

        $this
            ->exception(function () use ($t) {
                $t->addLibrary('', []);
            })
                ->hasMessage('L\'option "name" est obligatoire.')
        ;

        $this
            ->exception(function () use ($t) {
                $t->addLibrary('', [
                    'name' => '',
                ]);
            })
                ->hasMessage('L\'option "name" est obligatoire.')
        ;

        $this
            ->exception(function () use ($t) {
                $t->addLibrary('', [
                    'name' => '01',
                ]);
            })
                ->hasMessage('L\'url de la librairie ajouté doit être une chaîne non vide')
        ;
    }

    public function testAdd2()
    {
        $t = $this->testConstruct2();

        $this
            ->if(
                $t->addLibrary('yyy', [
                    'name' => 'xxx',
                ])
            )
            ->exception(function () use ($t) {
                $t->outputAll();
            })
                ->hasMessage(
                    'La librairie "yyy" n\'a pas été trouvée dans [Array' . PHP_EOL
                    . '(' . PHP_EOL
                    . '    [0] => a' . PHP_EOL
                    . '    [1] => b' . PHP_EOL
                    . '    [2] => c/d' . PHP_EOL
                    . ')' . PHP_EOL
                    . ']'
                )
        ;

        $this
            ->string(
                $t->outputAll(true)
            )
                ->isEqualTo(
                    '<script type="text/javascript">var requireJsConfig = { paths : {' . PHP_EOL
                    . '    "xxx": "yyy"' . PHP_EOL
                    . '}, shim : []}</script>'
                )
        ;
    }

    public function testAdd3()
    {
        $t = $this->testConstruct2();
        $this
            ->if(
                $t->addLibrary('01.js', [
                    'name' => '01',
                ])
            )
            ->string(
                $t->outputAll()
            )
                ->isEqualTo(
                    '<script type="text/javascript">var requireJsConfig = { paths : {' . PHP_EOL
                    . '    "01": "a\/01"' . PHP_EOL
                    . '}, shim : []}</script>'
                )
        ;

        $this
            ->if(
                $t->addLibrary('03.js', [
                    'name' => '03',
                    'deps' => [
                        '01',
                    ],
                ])
            )
            ->string(
                $t->outputAll()
            )
                ->isEqualTo(
                    '<script type="text/javascript">var requireJsConfig = { paths : {' . PHP_EOL
                    . '    "01": "a\/01",' . PHP_EOL
                    . '    "03": "b\/03"' . PHP_EOL
                    . '}, shim : {' . PHP_EOL
                    . '    "03": {' . PHP_EOL
                    . '        "deps": [' . PHP_EOL
                    . '            "01"' . PHP_EOL
                    . '        ]' . PHP_EOL
                    . '    }' . PHP_EOL
                    . '}}</script>'
                )
        ;
    }

    public function testAddModule()
    {
        $t = $this->testConstruct2();
        $this
            ->if(
                $t->setModuleDir('back/js')
            )
            ->and(
                $t->addModule('modules01')
            )
            ->string(
                $t->outputAll()
            )
                ->isEqualTo(
                    '<script type="text/javascript">var requireJsConfig = { paths : {' . PHP_EOL
                    . '    "modules01": "b\/back\/js\/modules01"' . PHP_EOL
                    . '}, shim : []}</script>'
                )
        ;

        $this
            ->if(
                $t->addModules([
                    'modules02',
                    'modules03',
                    'modules04',
                ])
            )
            ->string(
                $t->outputAll()
            )
                ->isEqualTo(
                    '<script type="text/javascript">var requireJsConfig = { paths : {' . PHP_EOL
                    . '    "modules01": "b\/back\/js\/modules01",' . PHP_EOL
                    . '    "modules02": "a\/back\/js\/modules02",' . PHP_EOL
                    . '    "modules03": "b\/back\/js\/modules03",' . PHP_EOL
                    . '    "modules04": "c\/d\/back\/js\/modules04"' . PHP_EOL
                    . '}, shim : []}</script>'
                )
        ;

        $this
            ->array(
                $t->getLibrairies()
            )
                ->isEqualTo(
                    [
                        'back/js/modules01.js' => [
                            'name' => 'modules01',
                        ],
                        'back/js/modules02.js' => [
                            'name' => 'modules02',
                        ],
                        'back/js/modules03.js' => [
                            'name' => 'modules03',
                        ],
                        'back/js/modules04.js' => [
                            'name' => 'modules04',
                        ],
                    ]
                )
        ;
    }
}
