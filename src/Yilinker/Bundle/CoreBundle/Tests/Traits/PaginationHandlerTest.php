<?php

namespace Yilinker\Bundle\CoreBundle\Tests\Traits;

use Yilinker\Bundle\CoreBundle\Tests\YilinkerCoreWebTestCase;
use Yilinker\Bundle\CoreBundle\Traits\PaginationHandler;

class PaginationHandlerTest extends YilinkerCoreWebTestCase
{
	use PaginationHandler;

    public function testGetOffset()
    {
        //zero must be zero
        $offset1 = $this->getOffset(10, 0);
        $this->assertEquals(0, $offset1);

        //one must be zero
        $offset2 = $this->getOffset(10, 1);
        $this->assertEquals(0, $offset2);

        //two must be 10
        $offset2 = $this->getOffset(10, 2);
        $this->assertEquals(10, $offset2);

        //three must be 20
        $offset3 = $this->getOffset(10, 3);
        $this->assertNotEquals(20, $offset2);
    }
}

