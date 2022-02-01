<?php

declare(strict_types=1);

namespace Apiato\Core\Abstracts\Seeders;

use Illuminate\Database\Seeder as LaravelSeeder;

abstract class Seeder extends LaravelSeeder
{
    /**
     * @var bool
     */
    public const WITH_TRANSACTIONS = false;
}
