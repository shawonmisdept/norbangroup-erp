<?php

namespace Tests\Unit;

use App\Support\PortalNumber;
use PHPUnit\Framework\TestCase;

class PortalNumberTest extends TestCase
{
    public function test_quantity_strips_trailing_zeros(): void
    {
        $this->assertSame('200', PortalNumber::quantity(200));
        $this->assertSame('200', PortalNumber::quantity('200.000'));
        $this->assertSame('10.5', PortalNumber::quantity(10.5));
        $this->assertSame('10.125', PortalNumber::quantity(10.125));
        $this->assertSame('0', PortalNumber::quantity(null));
    }
}
