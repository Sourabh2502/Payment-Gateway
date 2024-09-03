<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Razorpay\Api\Api;

class RazorpayController extends Controller
{
    public function index(): View
    {
        try {
            return view('razorpay.index');
        } catch (\Throwable $th) {
            Log::error('PAYMENT_INDEX_ERROR'.$th->getMessage());
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $paymentResponse = $request->input('response', []);
            if (count($paymentResponse) > 0 && empty($paymentResponse['razorpay_payment_id'])) {
                Session::put('error', 'No Payment ID Found');

                return redirect()->back();
            }

            $api = new Api(env('RAZORPAY_API_KEY'), env('RAZORPAY_API_SECRET'));
            $payment = $api->payment->fetch($paymentResponse['razorpay_payment_id']);
            $response = $payment->capture(['amount' => $payment['amount']]);

            // Store payment details
            Payment::create([
                'r_payment_id' => $response->id,
                'method' => $response->method,
                'currency' => $response->currency,
                'email' => $response->email,
                'phone' => $response->contact,
                'amount' => $response->amount / 100,
                'status' => 'success',
                'json_response' => json_encode((array) $response)
            ]);

            Session::put('success', 'Payment Successful');

            return response()->json(['success' => true, 'message' => 'Payment successfully recorded']);

        } catch (\Throwable $th) {
            Log::error('PAYMENT_STORE_ERROR'.$th->getMessage());
            Session::put('error', $th->getMessage());

            return response()->json(['success' => false, 'error' => 'Internal Server Error'], 500);
        }
    }

    public function failure(Request $request): JsonResponse
    {
        try {
            $responseData = $request->input('response', []);
            $errorData = $responseData['error'] ?? [];

            // Store failure payment details
            Payment::create([
                'r_payment_id' => $errorData['metadata']['payment_id'] ?? null,
                'method' => $errorData['source'] ?? null,
                'currency' => 'INR',
                'email' => '', // Email ID for the user
                'phone' => '', // Mobile number for the user
                'amount' => 100, // Amount for the payment process
                'status' => 'failed',
                'json_response' => json_encode($responseData)
            ]);

            return response()->json(['success' => true, 'message' => 'Payment failure recorded']);

        } catch (\Throwable $th) {
            Log::error('PAYMENT_FAILURE_ERROR: '.$th->getMessage());
            return response()->json(['success' => false, 'error' => 'Internal Server Error'], 500);
        }
    }
}
