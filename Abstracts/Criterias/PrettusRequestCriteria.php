<?php

declare(strict_types=1);

namespace Apiato\Core\Abstracts\Criterias;

use Apiato\Core\Traits\HashIdTrait;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Prettus\Repository\Contracts\RepositoryInterface;
use Prettus\Repository\Criteria\RequestCriteria as PrettusCriteria;

class PrettusRequestCriteria extends PrettusCriteria
{
    use HashIdTrait;

    /**
     * Apply criteria in query repository.
     *
     * @psalm-param Builder|Model $model
     *
     * @throws Exception
     */
    public function apply($model, RepositoryInterface $repository)
    {
        $fieldsSearchable = $repository->getFieldsSearchable();
        $search           = $this->request->get(config('repository.criteria.params.search', 'search'), null);
        $searchFields     = $this->request->get(config('repository.criteria.params.searchFields', 'searchFields'), null);
        $filter           = $this->request->get(config('repository.criteria.params.filter', 'filter'), null);
        $orderBy          = $this->request->get(config('repository.criteria.params.orderBy', 'orderBy'), null);
        $sortedBy         = $this->request->get(config('repository.criteria.params.sortedBy', 'sortedBy'), 'asc');
        $with             = $this->request->get(config('repository.criteria.params.with', 'with'), null);
        $withCount        = $this->request->get(config('repository.criteria.params.withCount', 'withCount'), null);
        $searchJoin       = $this->request->get(config('repository.criteria.params.searchJoin', 'searchJoin'), null);
        $sortedBy         = empty($sortedBy) ? 'asc' : $sortedBy;
        $search           = $this->decodeRepositorySearch($search);

        if ($search && \is_array($fieldsSearchable) && \count($fieldsSearchable)) {
            $searchFields       = \is_array($searchFields) || \is_null($searchFields) ? $searchFields : explode(';', $searchFields);
            $isFirstField       = true;
            $searchData         = $this->parserSearchData($search);
            $fields             = $this->parserFieldsSearch($fieldsSearchable, $searchFields, array_keys($searchData));
            $search             = $this->parserSearchValue($search);
            $modelForceAndWhere = isset($searchJoin) && strtolower($searchJoin) === 'and';

            $model = $model->where(function ($query) use (
                $fields,
                $search,
                $searchData,
                $isFirstField,
                $modelForceAndWhere
            ): void {
                /** @var Builder $query */
                foreach ($fields as $field => $condition) {
                    if (is_numeric($field)) {
                        $field     = $condition;
                        $condition = '=';
                    }

                    $value = null;

                    $condition = trim(strtolower($condition));

                    if (isset($searchData[$field])) {
                        $value = ($condition === 'like' || $condition === 'ilike') ? sprintf('%%%s%%', $searchData[$field]) : $searchData[$field];
                    } elseif (!\is_null($search) && !\in_array($condition, ['in', 'between'], true)) {
                        $value = ($condition === 'like' || $condition === 'ilike') ? sprintf('%%%s%%', $search) : $search;
                    }

                    $relation = null;

                    if (stripos($field, '.')) {
                        $explode  = explode('.', $field);
                        $field    = array_pop($explode);
                        $relation = implode('.', $explode);
                    }

                    if ($condition === 'in') {
                        $value = explode(',', $value);

                        if (trim($value[0]) === '' || $field === $value[0]) {
                            $value = null;
                        }
                    }

                    if ($condition === 'between') {
                        $value = explode(',', $value);

                        if (\count($value) < 2) {
                            $value = null;
                        }
                    }

                    $modelTableName = $query->getModel()->getTable();

                    if ($isFirstField || $modelForceAndWhere) {
                        if (!\is_null($value)) {
                            if (!\is_null($relation)) {
                                $query->whereHas($relation, function ($query) use ($field, $condition, $value): void {
                                    if ($condition === 'in') {
                                        $query->whereIn($field, $value);
                                    } elseif ($condition === 'between') {
                                        $query->whereBetween($field, $value);
                                    } else {
                                        $query->where($field, $condition, $value);
                                    }
                                });
                            } elseif ($condition === 'in') {
                                $query->whereIn($modelTableName . '.' . $field, $value);
                            } elseif ($condition === 'between') {
                                $query->whereBetween($modelTableName . '.' . $field, $value);
                            } else {
                                $query->where($modelTableName . '.' . $field, $condition, $value);
                            }

                            $isFirstField = false;
                        }
                    } elseif (!\is_null($value)) {
                        if (!\is_null($relation)) {
                            $query->orWhereHas($relation, function ($query) use ($field, $condition, $value): void {
                                if ($condition === 'in') {
                                    $query->whereIn($field, $value);
                                } elseif ($condition === 'between') {
                                    $query->whereBetween($field, $value);
                                } else {
                                    $query->where($field, $condition, $value);
                                }
                            });
                        } elseif ($condition === 'in') {
                            $query->orWhereIn($modelTableName . '.' . $field, $value);
                        } elseif ($condition === 'between') {
                            $query->whereBetween($modelTableName . '.' . $field, $value);
                        } else {
                            $query->orWhere($modelTableName . '.' . $field, $condition, $value);
                        }
                    }
                }
            });
        }

        if (isset($orderBy) && !empty($orderBy)) {
            $orderBySplit = explode(';', $orderBy);

            if (\count($orderBySplit) > 1) {
                $sortedBySplit = explode(';', $sortedBy);
                foreach ($orderBySplit as $orderBySplitItemKey => $orderBySplitItem) {
                    $sortedBy = $sortedBySplit[$orderBySplitItemKey] ?? $sortedBySplit[0];
                    $model    = $this->parserFieldsOrderBy($model, $orderBySplitItem, $sortedBy);
                }
            } else {
                $model = $this->parserFieldsOrderBy($model, $orderBySplit[0], $sortedBy);
            }
        }

        if (isset($filter) && !empty($filter)) {
            if (\is_string($filter)) {
                $filter = explode(';', $filter);
            }

            $model = $model->select($filter);
        }

        if (isset($with) && !empty($with)) {
            $with  = explode(';', $with);
            $model = $model->with($with);
        }

        if ($withCount) {
            $withCount = explode(';', $withCount);
            $model     = $model->withCount($withCount);
        }

        return $model;
    }

    /**
     * Without decoding the encoded ID's you won't be able to use
     * repository search features like `?search=user_id:id;other_id:id`.
     */
    protected function decodeRepositorySearch(?string $search = null): ?string
    {
        // The hash ID feature must be enabled to use this decoder feature.
        if ($search && $parseSearch = $this->parserSearchData($search)) {
            foreach ($parseSearch as $name => $value) {
                $isBool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                if (isset($isBool)) {
                    $value = (int)$isBool;
                }

                $parseSearch[$name] = $value;
            }

            if (config('apiato.hash-id')) {
                $parseSearch = $this->decodeSearchHashedIds($parseSearch);
            }

            $search = $this->unParserSearchData($parseSearch);
        }

        return $search;
    }

    protected function decodeSearchHashedIds(array $search): array
    {
        // Iterate over each key (ID that needs to be decoded) and call keys locator to decode them
        foreach ($search as $key => $value) {
            if (!is_numeric($value) && !\is_bool($value)) {
                $search[$key] = $this->decode($value) ?? $value;
            }
        }

        return $search;
    }

    protected function unParserSearchData(?array $search): string
    {
        $searchData = [];
        foreach ((array)$search as $field => $value) {
            $searchData[] = sprintf('%s:%s', $field, $value);
        }

        return implode(';', $searchData);
    }
}
