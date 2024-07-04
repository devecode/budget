<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/payment-methods",
     *     summary="List all payment methods",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="A list of payment methods"
     *     )
     * )
     */
    public function index()
    {
        $paymentMethods = PaymentMethod::all();
        return response()->json($paymentMethods);
    }
}
