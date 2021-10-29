<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Image;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class TransactionController extends Controller
{
    public function showUserTransactions()
    {
        return response()->json(Transaction::with('product')->where('buyer_id', JWTAuth::user()->id)->orWhere('seller_id', JWTAuth::user()->id)->get());
    }

    public function showUserPurchases()
    {
        return response()->json(Transaction::with('product')->where('buyer_id', JWTAuth::user()->id)->get());
    }
    
    public function showUserSales()
    {
        return response()->json(Transaction::with('product')->where('seller_id', JWTAuth::user()->id)->get());
    }

    public function showUserTransactionsById($id)
    {
        $transaction = Transaction::with('product')->find($id);
        if (!$transaction) {
            return response()->json(['status' => 'error', 'message' => 'Transaction not found']);
        }
        if ($transaction->buyer_id != JWTAuth::user()->id || $transaction->seller_id != JWTAuth::user()->id) {
            return response()->json(['status' => 'error', 'message' => 'User ID mismatch - Unauthorized']);
        }
        return response()->json($transaction);
    }

    public function create(Request $request)
    {
        $user = JWTAuth::user();

        $this->validate($request, [
            'product_id' => 'required',
            'qty' => 'required',
        ]);

        $product = Product::findOrFail($request->product_id);
        $transaction = $user->purchases()->create([
            'qty' => $request->qty,
            'price' => $product->price,
            'total' => $request->qty * $product->price,
            'seller_id' => $product->user_id
        ]);
        $product->transactions()->save($transaction);

        return response()->json($transaction, 201);
    }

    public function insertPaymentProof($id, Request $request)
    {
        $user = JWTAuth::user();

        $this->validate($request, [
            'payment_file' => 'required|mimes:jpg,png,pdf'
        ]);

        $transaction = Transaction::findOrFail($id);

        if ($transaction->buyer_id != $user->id) {            
            return response()->json(['status' => 'error', 'message' => 'User ID mismatch']);
        }

        if ($transaction->payment_status) {            
            return response()->json(['status' => 'error', 'message' => 'Transaction is already paid']);
        }
        
        $filename = $transaction->id . '_' . $user->id . '.' . $request->file('payment_file')->extension();
        $request->file('payment_file')->move(storage_path('transactions/payments'), $filename);
        $transaction->update([
            'payment_file' => storage_path('transactions/payments') . '/' . $filename
        ]);
        
        $transaction->refresh();
        return response()->json($transaction, 201);
    }

    public function insertProductProof($id, Request $request)
    {
        $user = JWTAuth::user();

        $this->validate($request, [
            'product_file' => 'required'
        ]);

        $transaction = Transaction::findOrFail($id);

        if ($transaction->seller_id != $user->id) {            
            return response()->json(['status' => 'error', 'message' => 'User ID mismatch']);
        }

        if ($transaction->status) {            
            return response()->json(['status' => 'error', 'message' => 'Transaction is already complete']);
        }
        
        $filename = $transaction->id . '_' . $user->id . '.' . $request->file('product_file')->extension();
        $request->file('product_file')->move(storage_path('transactions/products'), $filename);
        $transaction->update([
            'product_file' => storage_path('transactions/products') . '/' . $filename
        ]);
        
        $transaction->refresh();
        return response()->json($transaction, 201);
    }
}
