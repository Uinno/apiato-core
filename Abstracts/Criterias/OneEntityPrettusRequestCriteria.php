<?php

declare(strict_types=1);

namespace Apiato\Core\Abstracts\Criterias;

use Apiato\Core\Repository\Interfaces\OneEntityRequestCriteriaInterface;
use Apiato\Core\Traits\HashIdTrait;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Prettus\Repository\Contracts\RepositoryInterface;
use Prettus\Repository\Criteria\RequestCriteria as PrettusCriteria;


class OneEntityPrettusRequestCriteria extends PrettusCriteria implements OneEntityRequestCriteriaInterface
{
    use HashIdTrait;

    /**
     * Apply criteria in query repository.
     *
     * @psalm-param Builder|Model $model
     * @throws Exception
     */
    public function apply($model, RepositoryInterface $repository)
    {
        $filter = $this->request->get(config('repository.criteria.params.filter', 'filter'), null);
        $with = $this->request->get(config('repository.criteria.params.with', 'with'), null);
        $withCount = $this->request->get(config('repository.criteria.params.withCount', 'withCount'), null);


        if ($withCount) {
            $withCount = explode(';', $withCount);
        }

        if (isset($filter) && !empty($filter)) {
            if (\is_string($filter)) {
                $filter = explode(';', $filter);
            }

            $model = $model->select($filter);
        }

        if (isset($with) && !empty($with)) {
            $with = explode(';', $with);
            $model = $model->with($with);
        }

        if (!empty($withCount)) {
            $model = $model->withCount($withCount);
        }

        return $model;
    }
}
