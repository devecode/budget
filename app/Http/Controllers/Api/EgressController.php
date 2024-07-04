<?php

namespace App\Http\Controllers\Api;

use App\Exports\EgresosExport;
use App\Exports\PayMethodExport;
use App\Http\Controllers\Controller;
use App\Models\Egress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\PurifyService;

class EgressController extends Controller
{
    protected $purifyService;

    public function __construct(PurifyService $purifyService)
    {
        $this->purifyService = $purifyService;
    }

    /**
     * @OA\Get(
     *     path="/api/egresses",
     *     summary="List all egresses",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="A list of egresses"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Egress::with('category', 'paymentMethod');

        $filters = [
            'category_id' => $request->category_id,
            'payment_method_id' => $request->payment_method_id,
        ];

        foreach ($filters as $key => $value) {
            if ($request->has($key)) {
                $query->where($key, $value);
            }
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%$search%")
                  ->orWhereHas('category', fn($q) => $q->where('name', 'LIKE', "%$search%"))
                  ->orWhereHas('paymentMethod', fn($q) => $q->where('name', 'LIKE', "%$search%"));
            });
        }

        return response()->json($query->get());
    }

    /**
     * @OA\Post(
     *     path="/api/egresses",
     *     summary="Store a new egress",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","category_id","amount","is_fixed","date_egreso","status","payment_method_id"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="category_id", type="integer"),
     *             @OA\Property(property="amount", type="number"),
     *             @OA\Property(property="is_fixed", type="boolean"),
     *             @OA\Property(property="date_egreso", type="string", format="date"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="payment_method_id", type="integer"),
     *             @OA\Property(property="frequency", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Egress created successfully"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate($this->validationRules());

        $validatedData['name'] = $this->purifyService->purify($validatedData['name']);
        $validatedData['status'] = $this->purifyService->purify($validatedData['status']);

        $egress = Egress::create($validatedData);

        if ($validatedData['is_fixed']) {
            $this->createRecurringEgresses($egress, $validatedData['frequency']);
        }

        return response()->json($egress, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/egresses/{id}",
     *     summary="Show a specific egress",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Egress details"
     *     )
     * )
     */
    public function show($id)
    {
        $egress = Egress::with('category', 'paymentMethod')->findOrFail($id);
        return response()->json($egress);
    }

    /**
     * @OA\Put(
     *     path="/api/egresses/{id}",
     *     summary="Update a specific egress",
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
     *             required={"name","category_id","amount","is_fixed","date_egreso","status","payment_method_id"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="category_id", type="integer"),
     *             @OA\Property(property="amount", type="number"),
     *             @OA\Property(property="is_fixed", type="boolean"),
     *             @OA\Property(property="date_egreso", type="string", format="date"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="payment_method_id", type="integer"),
     *             @OA\Property(property="frequency", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Egress updated successfully"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $egress = Egress::findOrFail($id);
        $validatedData = $request->validate($this->validationRules());

        $validatedData['name'] = $this->purifyService->purify($validatedData['name']);
        $validatedData['status'] = $this->purifyService->purify($validatedData['status']);

        $egress->update($validatedData);

        if ($validatedData['is_fixed']) {
            $this->createRecurringEgresses($egress, $validatedData['frequency']);
        }

        return response()->json($egress);
    }

    /**
     * @OA\Delete(
     *     path="/api/egresses/{id}",
     *     summary="Delete a specific egress",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Egress deleted successfully"
     *     )
     * )
     */
    public function destroy(Request $request, $id)
    {
        $egress = Egress::findOrFail($id);
        $deleteAll = filter_var($request->query('delete_all', false), FILTER_VALIDATE_BOOLEAN);

        if ($deleteAll) {
            Egress::where('name', $egress->name)
                ->where('category_id', $egress->category_id)
                ->where('amount', $egress->amount)
                ->where('payment_method_id', $egress->payment_method_id)
                ->where('date_egreso', '>=', $egress->date_egreso)
                ->delete();

            return response()->json(['message' => 'Todos los egresos futuros relacionados fueron eliminados correctamente']);
        } else {
            $egress->delete();
            return response()->json(['message' => 'Egreso eliminado correctamente']);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/top-categories",
     *     summary="Get top categories",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Top categories with total spent and remaining budget"
     *     )
     * )
     */
    public function topCategories()
    {
        $topCategories = DB::table('egresses')
            ->select('categories.name', 'categories.limit_spending', DB::raw('SUM(egresses.amount) as total_spent'))
            ->join('categories', 'egresses.category_id', '=', 'categories.id')
            ->groupBy('categories.name', 'categories.limit_spending')
            ->orderByDesc('total_spent')
            ->limit(3)
            ->get()
            ->transform(fn($category) => (object) [
                'name' => $category->name,
                'limit_spending' => $category->limit_spending,
                'total_spent' => $category->total_spent,
                'remaining_budget' => $category->limit_spending - $category->total_spent,
            ]);

        return response()->json($topCategories);
    }

    /**
     * @OA\Get(
     *     path="/api/categories-with-spent",
     *     summary="Get all categories with spent amount",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of categories with spent amount"
     *     )
     * )
     */
    public function allCategoriesWithSpent()
    {
        $categories = DB::table('categories')
            ->leftJoin('egresses', 'categories.id', '=', 'egresses.category_id')
            ->select('categories.id', 'categories.name', 'categories.limit_spending', DB::raw('COALESCE(SUM(egresses.amount), 0) as total_spent'))
            ->groupBy('categories.id', 'categories.name', 'categories.limit_spending')
            ->get();

        return response()->json($categories);
    }

    /**
     * @OA\Get(
     *     path="/api/cuentas-por-pagar",
     *     summary="Get cuentas por pagar",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of cuentas por pagar"
     *     )
     * )
     */
    public function cuentasPorPagar(Request $request)
    {
        $query = Egress::with('category', 'paymentMethod')
            ->where('status', 'pendiente');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date_egreso', [$request->start_date, $request->end_date]);
        }

        return response()->json($query->get());
    }

    /**
     * @OA\Get(
     *     path="/api/export-cuentas-por-pagar",
     *     summary="Export cuentas por pagar",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Exported file"
     *     )
     * )
     */
    public function exportCuentasPorPagar(Request $request)
    {
        return Excel::download(new EgresosExport($request->start_date, $request->end_date), 'cuentas_por_pagar.xlsx');
    }

    /**
     * @OA\Get(
     *     path="/api/export-payment-method-view",
     *     summary="Export payment method view",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="payment_method",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Exported file"
     *     )
     * )
     */
    public function exportPayMethodView(Request $request)
    {
        return Excel::download(new PayMethodExport($request->start_date, $request->end_date, $request->payment_method), 'pay_methods_view.xlsx');
    }

    /**
     * @OA\Get(
     *     path="/api/get-egresses",
     *     summary="Get list of egresses",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of egresses"
     *     )
     * )
     */
    public function getEgresses()
    {
        return response()->json(Egress::select('name', 'amount')->get());
    }

    /**
     * @OA\Get(
     *     path="/api/spending-by-payment-method",
     *     summary="Get spending by payment method",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="payment_method",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="month",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Spending by payment method"
     *     )
     * )
     */
    public function getSpendingByPaymentMethod(Request $request)
    {
        $paymentMethods = ['Credit Card', 'Debit Card', 'Transfer', 'Cash'];

        $query = Egress::query()
            ->when($request->has('payment_method') && in_array($request->payment_method, $paymentMethods), function ($q) use ($request) {
                $q->whereHas('paymentMethod', fn($q) => $q->where('name', $request->payment_method));
            })
            ->when($request->has('year') && $request->has('month'), function ($q) use ($request) {
                $q->whereYear('date_egreso', $request->year)
                  ->whereMonth('date_egreso', $request->month);
            });

        $spending = $query->select('payment_method_id', DB::raw('SUM(amount) as total_spent'))
            ->groupBy('payment_method_id')
            ->with('paymentMethod')
            ->get()
            ->map(fn($item) => [
                'payment_method' => $item->paymentMethod->name,
                'total_spent' => $item->total_spent,
            ]);

        return response()->json($spending);
    }

    /**
     * @OA\Get(
     *     path="/api/payment-method-view",
     *     summary="Get payment method view",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="payment_method",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment method view"
     *     )
     * )
     */
    public function getPayMethodView(Request $request)
    {
        $paymentMethods = ['Credit Card', 'Debit Card', 'Transfer', 'Cash'];

        $query = Egress::query()
            ->join('categories', 'egresses.category_id', '=', 'categories.id')
            ->join('payment_methods', 'egresses.payment_method_id', '=', 'payment_methods.id')
            ->select('payment_methods.name as payment_method', 'categories.name as category', 'categories.limit_spending', DB::raw('SUM(egresses.amount) as total_spent'))
            ->groupBy('payment_methods.name', 'categories.name', 'categories.limit_spending')
            ->when($request->has('payment_method') && in_array($request->payment_method, $paymentMethods), function ($q) use ($request) {
                $q->where('payment_methods.name', $request->payment_method);
            })
            ->when($request->has('start_date') && $request->has('end_date'), function ($q) use ($request) {
                $q->whereBetween('date_egreso', [$request->start_date, $request->end_date]);
            });

        return response()->json($query->get());
    }

    /**
     * @OA\Get(
     *     path="/api/accounts-payable",
     *     summary="Get accounts payable",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="month",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Accounts payable"
     *     )
     * )
     */
    public function accountsPayable(Request $request)
    {
        $query = Egress::where('status', 'pendiente');

        if ($request->has('year') && $request->has('month')) {
            $query->whereYear('date_egreso', $request->year)
                  ->whereMonth('date_egreso', $request->month);
        }

        $accounts = $query->select('date_egreso', 'amount', 'name')->get();
        $total = $accounts->sum('amount');

        return response()->json([
            'total' => $total,
            'accounts' => $accounts,
        ]);
    }

    private function createRecurringEgresses(Egress $egress, $frequency)
    {
        $startDate = Carbon::parse($egress->date_egreso);
        $endDate = Carbon::now()->addYear();

        while ($startDate->lessThanOrEqualTo($endDate)) {
            $startDate = $this->getNextDate($startDate, $frequency);

            if ($startDate->lessThanOrEqualTo($endDate)) {
                Egress::create([
                    'name' => $egress->name,
                    'category_id' => $egress->category_id,
                    'amount' => $egress->amount,
                    'is_fixed' => $egress->is_fixed,
                    'date_egreso' => $startDate,
                    'status' => $egress->status,
                    'payment_method_id' => $egress->payment_method_id,
                ]);
            }
        }
    }

    private function getNextDate(Carbon $date, $frequency)
    {
        return match ($frequency) {
            'weekly' => $date->addWeek(),
            'biweekly' => $date->addWeeks(2),
            'monthly' => $date->addMonth(),
            default => $date,
        };
    }

    private function validationRules()
    {
        return [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric',
            'is_fixed' => 'required|boolean',
            'date_egreso' => 'required|date',
            'status' => 'required|string|max:255',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'frequency' => 'nullable|string|in:weekly,biweekly,monthly',
        ];
    }
}
