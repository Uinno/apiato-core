<?php

declare(strict_types=1);

namespace Apiato\Core\Traits\TestsTraits\PhpUnit;

use Illuminate\Support\Facades\App;
use Mockery;
use Mockery\MockInterface;

trait TestsMockHelperTrait
{
    public function mockIt($class): MockInterface
    {
        $mock = Mockery::mock($class);
        App::instance($class, $mock);

        return $mock;
    }
}
