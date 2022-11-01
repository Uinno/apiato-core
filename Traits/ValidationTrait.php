<?php

declare(strict_types=1);

namespace Apiato\Core\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

trait ValidationTrait
{
    /**
     * Extend the default Laravel validation rules.
     */
    public function extendValidationRules(): void
    {
        // Validate String contains no space.
        Validator::extend('no_spaces', static fn ($attribute, $value, $parameters, $validator): bool => (bool)preg_match('/^\S*$/u', $value), 'String :attribute should not contain space.');

        // Validate composite unique ID.
        // Usage: unique_composite:table,this-attribute-column,the-other-attribute-column,?the-other-attribute-value
        // Example:    'values'               => 'required|unique_composite:item_variant_values,value,item_variant_name_id,?item_variant_name_value',
        //             'item_variant_name_id' => 'required',
        Validator::extend('unique_composite', function ($attribute, $value, $parameters, $validator): bool {
            $queryBuilder = DB::table($parameters[0]);

            $queryBuilder = \is_array($value) ? $queryBuilder->whereIn(
                $parameters[1],
                $value
            ) : $queryBuilder->where($parameters[1], $value);

            $queryBuilder->where($parameters[2], $parameters[3] ?? $validator->getData()[$parameters[2]]);

            return $queryBuilder->get()->isEmpty();
        }, 'The :attribute field must be unique.');
    }
}
