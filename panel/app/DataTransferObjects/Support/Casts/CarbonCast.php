<?php

namespace App\DataTransferObjects\Support\Casts;

use Illuminate\Support\Carbon;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Support\DataProperty;
use Spatie\LaravelData\Support\Creation\CreationContext;

class CarbonCast implements Cast
{
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): Carbon
    {
        return Carbon::parse($value);
    }
}
