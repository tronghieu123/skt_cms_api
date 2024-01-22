<?php

namespace App\Http\Controllers\V1;

use App\Http\Resources\V1\WardCollection;
use App\Http\Resources\V1\WardResource;
use App\Models\V1\Ward;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class WardController extends BaseController
{
    public function index()
    {
        return new WardCollection(
            QueryBuilder::for(Ward::class)
                ->allowedFilters([
                    AllowedFilter::exact('province_code'),
                    AllowedFilter::scope('title'),
                ])
                ->allowedSorts('title')
                ->paginate()
        );
    }
    public function create()
    {
        //
    }
    public function store(Request $request)
    {
        //
    }
    public function show(string $code)
    {
        return new WardResource(
            QueryBuilder::for(Ward::class)
                ->where('code', $code)
                ->allowedIncludes('district')
                ->firstOrFail()
        );
    }
    public function edit(Ward $Ward)
    {
        //
    }
    public function update(Request $request, Ward $Ward)
    {
        //
    }
    public function destroy(Ward $Ward)
    {
        //
    }
}
