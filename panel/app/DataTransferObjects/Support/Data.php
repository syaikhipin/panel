<?php

namespace App\DataTransferObjects\Support;

class Data extends \Spatie\LaravelData\Data
{
    /**
     * When working with paginated data, we want to include pagination details in JSON
     * responses from the API. However, due to legacy requirements Ploi Core is using
     * a different structure than this package assumes. Therefore, we bind a custom
     * TransformedDataCollectableResolver in the AppServiceProvider that outputs
     * the legacy structure for paginated collections.
     */
}
