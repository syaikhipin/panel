<?php

namespace App\Http\Controllers\Api;

use App\Models\Package;
use App\Http\Controllers\Controller;
use App\DataTransferObjects\PackageData;
use Spatie\LaravelData\PaginatedDataCollection;

class PackageController extends Controller
{
    public function index(): mixed
    {
        return PackageData::collect(Package::paginate(), PaginatedDataCollection::class);
    }
}
