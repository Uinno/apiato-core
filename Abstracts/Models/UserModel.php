<?php

declare(strict_types=1);

namespace Apiato\Core\Abstracts\Models;

use Apiato\Core\Traits\FactoryLocatorTrait;
use Apiato\Core\Traits\HashedRouteBindingTrait;
use Apiato\Core\Traits\HashIdTrait;
use Apiato\Core\Traits\HasResourceKeyTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as LaravelAuthenticatableUser;

abstract class UserModel extends LaravelAuthenticatableUser
{
    use FactoryLocatorTrait, HasFactory {
        FactoryLocatorTrait::newFactory insteadof HasFactory;
    }
    use HashIdTrait;
    use HashedRouteBindingTrait;
    use HasResourceKeyTrait;
}
