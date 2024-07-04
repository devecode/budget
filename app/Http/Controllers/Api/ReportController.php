<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/reports/income-vs-egress",
     *     summary="Get income vs egress report",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="month",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", format="int32"),
     *         description="Month to filter the report"
     *     ),
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", format="int32"),
     *         description="Year to filter the report"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Income vs Egress report",
     *         @OA\JsonContent(
     *             @OA\Property(property="total_income", type="number"),
     *             @OA\Property(property="total_egress", type="number")
     *         )
     *     )
     * )
     */
    public function incomeVsEgress(Request $request)
    {
        $month = $request->input('month');
        $year = $request->input('year');

        $incomes = $this->getTotalIncome($month, $year);
        $egresses = $this->getTotalEgress($month, $year);

        return response()->json([
            'total_income' => $incomes,
            'total_egress' => $egresses,
        ]);
    }

    private function getTotalIncome($month, $year)
    {
        $query = DB::table('incomes')
            ->select(DB::raw('SUM(amount) as total_income'));

        if ($month && $year) {
            $query->whereMonth('date_ingreso', $month)
                  ->whereYear('date_ingreso', $year);
        }

        $result = $query->first();
        return $result ? $result->total_income : 0;
    }

    private function getTotalEgress($month, $year)
    {
        $query = DB::table('egresses')
            ->select(DB::raw('SUM(amount) as total_egress'));

        if ($month && $year) {
            $query->whereMonth('date_egreso', $month)
                  ->whereYear('date_egreso', $year);
        }

        $result = $query->first();
        return $result ? $result->total_egress : 0;
    }
}
