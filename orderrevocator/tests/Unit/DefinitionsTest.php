<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PrestaShop\Module\OrderRevocator\Entity\Definitions;

class DefinitionsTest extends TestCase
{
    public function testConstants(): void
    {
        $this->assertSame('orderrevocator', Definitions::MODULE_NAME);
        $this->assertSame('Modules.Orderrevocator.Orderrevocator', Definitions::TRANS_ADMIN);
        $this->assertSame('Modules.Orderrevocator.Shop', Definitions::TRANS_SHOP);
    }

    public function testRateLimitConstantsAreSane(): void
    {
        // A human needs at least a couple of seconds to fill the form,
        // but the cooldown between two submissions must be clearly longer
        // than that, or it would never actually apply.
        $this->assertGreaterThan(0, Definitions::RATE_LIMIT_MIN_FILL_SECONDS);
        $this->assertGreaterThan(
            Definitions::RATE_LIMIT_MIN_FILL_SECONDS,
            Definitions::RATE_LIMIT_COOLDOWN_SECONDS
        );
    }
}
