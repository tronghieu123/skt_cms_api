<?php

namespace App\Http\Controllers\V1;

use App\Http\Resources\V1\DistrictCollection;
use App\Http\Resources\V1\DistrictResource;
use App\Models\V1\District;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class DistrictController extends BaseController
{
    public function index()
    {
        // dd(District::with('province')->first());
        return new DistrictCollection(
            QueryBuilder::for(District::class)
                ->allowedFilters([
                    AllowedFilter::exact('province_code'),
                ])
                ->allowedIncludes('province', 'ward')
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
    public function show(string $district)
    {
        // dd($district);
        return new DistrictResource(
            QueryBuilder::for(District::class)
                ->where('code', $district)
                ->allowedIncludes('province', 'ward')
                ->firstOrFail()
        );
    }
    public function edit(District $district)
    {
        //
    }
    public function update(Request $request, District $district)
    {
        //
    }
    public function destroy(District $district)
    {
        //
    }
}
