<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\HousingUnit;
use Illuminate\Http\Request;

class HousingUnitController extends Controller
{
    public function index(Request $request)
    {
        // بحث ذكي لو فيه keyword
        $query = HousingUnit::query();

        if ($request->has('search')) {
            $query->where('name', 'LIKE', "%{$request->search}%")
                ->orWhere('location', 'LIKE', "%{$request->search}%");
        }

        return response()->json($query->get());
    }
}
