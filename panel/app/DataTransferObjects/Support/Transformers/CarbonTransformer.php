<?php

namespace App\DataTransferObjects\Support\Transformers;

use Illuminate\Support\Carbon;
use Spatie\LaravelData\Support\DataProperty;
use Spatie\LaravelData\Transformers\Transformer;
use Spatie\LaravelData\Support\Transformation\TransformationContext;

class CarbonTransformer implements Transformer
{
    public function transform(DataProperty $property, mixed $value, TransformationContext $context): string
    {
        /** @var Carbon $value */
        return $value->toISOString();
    }
}
