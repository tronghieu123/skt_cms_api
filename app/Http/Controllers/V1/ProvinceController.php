<?php

namespace App\Http\Controllers\V1;

use App\Http\Resources\V1\ProvinceCollection;
use App\Http\Resources\V1\ProvinceResource;
use App\Models\V1\Province;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Spatie\QueryBuilder\QueryBuilder;

class ProvinceController extends BaseController
{
    public function index()
    {
        return new ProvinceCollection(
            QueryBuilder::for(Province::class)
                ->allowedIncludes('district')
                ->get()
        );
    }
    public function store(Request $request)
    {
        //
    }
    public function show(string $id)
    {
        // return new ProvinceResource(Province::firstWhere('code', $id));
        return new ProvinceResource(
            QueryBuilder::for(Province::class)
                ->allowedIncludes('district')
                ->where('code', $id)
                ->firstOrFail()
        );
    }
    public function update(Request $request, string $id)
    {
        //
    }
    public function destroy(string $id)
    {
        //
    }
}
