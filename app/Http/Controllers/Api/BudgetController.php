<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $budgets = Budget::all();
        return response()->json($budgets);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'monthly_budget' => 'required|numeric',
        ]);

        $budget = Budget::create($validated);
        return response()->json($budget, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Budget $budget)
    {
        return response()->json($budget);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Budget $budget)
    {
        $validated = $request->validate([
            'monthly_budget' => 'required|numeric',
        ]);

        $budget->update($validated);
        return response()->json($budget);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Budget $budget)
    {
        $budget->delete();
        return response()->json(null, 204);
    }
}
