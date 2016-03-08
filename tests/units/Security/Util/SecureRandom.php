<?php
/**
 * Test class for Secure random.
 *
 * @author  Stéphane Monnot <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Lib\tests\unit\Security\Util;

use mageekguy\atoum as Atoum;
use Solire\Lib\Security\Util\SecureRandom as testedClass;

/**
 * Test class for Secure random.
 *
 * @author  Stéphane Monnot <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class SecureRandom extends Atoum
{
    /**
     * Test construct.
     *
     * @return void
     */
    public function testConstruct()
    {
        $this
            ->if($this->newTestedInstance)
            ->then
                ->object($this->testedInstance)
                    ->isTestedInstance()
        ;
    }

    /**
     * Test generate method.
     *
     * @return void
     */
    public function testGenerate()
    {
        $this
            ->if($this->newTestedInstance)

            ->if($this->function->rand = 9)
            ->string($this->testedInstance->generate(10, testedClass::RANDOM_NUMERIC))
                ->hasLength(10)
                ->isEqualTo(9999999999)

            ->if($this->function->rand = 9)
            ->string($this->testedInstance->generate(10, testedClass::RANDOM_ALL))
                ->isEqualTo(9999999999)

            ->if($this->function->rand = 50)
            ->string($this->testedInstance->generate(10, testedClass::RANDOM_ALL))
                ->isEqualTo('oooooooooo')

            ->if($this->function->rand = 75)
            ->string($this->testedInstance->generate(10, testedClass::RANDOM_ALL))
                ->isEqualTo('[[[[[[[[[[')
            ->string($this->testedInstance->generate(25, testedClass::RANDOM_ALL))
                ->isEqualTo('[[[[[[[[[[[[[[[[[[[[[[[[[')

            ->if($this->function->rand = 10)
            ->string($this->testedInstance->generate(5, testedClass::RANDOM_SYMBOL))
                ->isEqualTo(')))))')

            ->exception(
                function () {
                    $this->testedInstance->generate(5, 0);
                }
            )
                ->IsInstanceOf('Solire\Lib\Security\Util\Exception\InvalidRandomRangeException')
        ;
    }
}
