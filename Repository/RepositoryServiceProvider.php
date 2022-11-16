<?php
declare (strict_types=1);

namespace Apiato\Core\Repository;

use Apiato\Core\Abstracts\Criterias\PrettusRequestCriteria;
use Apiato\Core\Repository\Interfaces\RequestCriteriaInterface;
use Prettus\Repository\Providers\RepositoryServiceProvider as ParentRepositoryServiceProvider;

class RepositoryServiceProvider extends ParentRepositoryServiceProvider
{

    public function register()
    {
        parent::register();
        $this->app->bind(RequestCriteriaInterface::class, PrettusRequestCriteria::class);
    }
}
