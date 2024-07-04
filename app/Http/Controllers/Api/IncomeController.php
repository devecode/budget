<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Income;
use Illuminate\Http\Request;
use Carbon\Carbon;

class IncomeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/incomes",
     *     summary="List all incomes",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="A list of incomes"
     *     )
     * )
     */
    public function index()
    {
        $incomes = Income::all();
        return response()->json($incomes);
    }

    /**
     * @OA\Post(
     *     path="/api/incomes",
     *     summary="Store a new income",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "amount", "date_ingreso", "type", "is_fixed"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="frequency", type="string", nullable=true),
     *             @OA\Property(property="amount", type="number"),
     *             @OA\Property(property="date_ingreso", type="string", format="date"),
     *             @OA\Property(property="type", type="string"),
     *             @OA\Property(property="is_fixed", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Income created successfully"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate($this->validationRules());

        $income = Income::create($validatedData);

        if ($validatedData['is_fixed']) {
            $this->createRecurringIncomes($income, $validatedData['frequency']);
        }

        return response()->json($income, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/incomes/{id}",
     *     summary="Show a specific income",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Income details"
     *     )
     * )
     */
    public function show($id)
    {
        $income = Income::findOrFail($id);
        return response()->json($income);
    }

    /**
     * @OA\Put(
     *     path="/api/incomes/{id}",
     *     summary="Update a specific income",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "amount", "date_ingreso", "type", "is_fixed"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="frequency", type="string", nullable=true),
     *             @OA\Property(property="amount", type="number"),
     *             @OA\Property(property="date_ingreso", type="string", format="date"),
     *             @OA\Property(property="type", type="string"),
     *             @OA\Property(property="is_fixed", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Income updated successfully"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $income = Income::findOrFail($id);

        $validatedData = $request->validate($this->validationRules());

        $income->update($validatedData);

        if ($validatedData['is_fixed']) {
            $this->createRecurringIncomes($income, $validatedData['frequency']);
        }

        return response()->json($income);
    }

    /**
     * @OA\Delete(
     *     path="/api/incomes/{id}",
     *     summary="Delete a specific income",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Income deleted successfully"
     *     )
     * )
     */
    public function destroy($id)
    {
        $income = Income::findOrFail($id);
        $income->delete();

        return response()->json(['message' => 'Income deleted successfully']);
    }

    // Método privado para crear ingresos recurrentes
    private function createRecurringIncomes(Income $income, $frequency)
    {
        $startDate = Carbon::parse($income->date_ingreso);
        $endDate = Carbon::now()->addYear();

        while ($startDate->lessThanOrEqualTo($endDate)) {
            $startDate = $this->getNextDate($startDate, $frequency);

            if ($startDate->lessThanOrEqualTo($endDate)) {
                Income::create([
                    'name' => $income->name,
                    'frequency' => $income->frequency,
                    'amount' => $income->amount,
                    'type' => $income->type,
                    'is_fixed' => $income->is_fixed,
                    'date_ingreso' => $startDate,
                ]);
            }
        }
    }

    // Método privado para obtener la próxima fecha de acuerdo a la frecuencia
    private function getNextDate(Carbon $date, $frequency)
    {
        return match ($frequency) {
            'weekly' => $date->addWeek(),
            'biweekly' => $date->addWeeks(2),
            'monthly' => $date->addMonth(),
            default => $date,
        };
    }

    // Método privado para obtener las reglas de validación
    private function validationRules()
    {
        return [
            'name' => 'required|string|max:255',
            'frequency' => 'nullable|string|in:weekly,biweekly,monthly',
            'amount' => 'required|numeric',
            'date_ingreso' => 'required|date',
            'type' => 'required|string|max:255',
            'is_fixed' => 'required|boolean',
        ];
    }
}
