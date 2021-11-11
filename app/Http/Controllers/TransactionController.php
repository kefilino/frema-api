<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class TransactionController extends Controller
{
    public function showUserTransactions()
    {
        return response()->json(
            Transaction::with([
                'product',
                'seller:id,name',
                'buyer:id,name',
                'product.album.images:id,album_id,src',
                'seller.image:id,user_id,src',
                'buyer.image:id,user_id,src'
            ])
            ->where('buyer_id', JWTAuth::user()->id)
            ->orWhere('seller_id', JWTAuth::user()->id)
            ->get(), 200
        );
    }

    public function showUserPurchases()
    {
        return response()->json(
            Transaction::with([
                'product',
                'seller:id,name',
                'product.album.images:id,album_id,src',
                'seller.image:id,user_id,src'
            ])
            ->where('buyer_id', JWTAuth::user()->id)
            ->get(), 200
        );
    }
    
    public function showUserSales()
    {
        return response()->json(
            Transaction::with([
                'product',
                'buyer:id,name',
                'product.album.images:id,album_id,src',
                'buyer.image:id,user_id,src'
            ])
            ->where('seller_id', JWTAuth::user()->id)
            ->get(), 200
        );
    }

    public function showUserTransactionsById($id)
    {
        $transaction = Transaction::with([
            'product',
            'seller:id,name',
            'buyer:id,name',
            'product.album.images:id,album_id,src',
            'seller.image:id,user_id,src',
            'buyer.image:id,user_id,src'
        ])->find($id);
        if (!$transaction) {
            return response()->json(['status' => 'error', 'message' => 'Transaction not found'], 404);
        }
        if ($transaction->buyer_id != JWTAuth::user()->id && $transaction->seller_id != JWTAuth::user()->id) {
            return response()->json(['status' => 'error', 'message' => 'User ID mismatch - Unauthorized'], 401);
        }
        return response()->json($transaction, 200);
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

        $transaction->notifications()->create([
            'user_id' => $transaction->seller_id,
            'message' => "Ada pesanan baru untuk produk \"" . $product->title . "\"."
        ]);

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
            return response()->json(['status' => 'error', 'message' => 'User ID mismatch'], 401);
        }

        if ($transaction->payment_status) {            
            return response()->json(['status' => 'error', 'message' => 'Transaction is already paid'], 409);
        }
        
        $filename = $transaction->id . '_' . $user->id . '.' . $request->file('payment_file')->extension();
        $request->file('payment_file')->move(storage_path('transactions/payments'), $filename);
        $transaction->update([
            'payment_file' => storage_path('transactions/payments') . '/' . $filename
        ]);        
        $transaction->refresh();

        $transaction->notifications()->create([
            'user_id' => $transaction->seller_id,
            'message' => "Pembeli telah mengunggah bukti pembayaran untuk produk \"" . $transaction->product->title .
                "\" dengan ID transaksi: " . $transaction->id
        ]);
        
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
            return response()->json(['status' => 'error', 'message' => 'User ID mismatch'], 401);
        }

        if ($transaction->status) {            
            return response()->json(['status' => 'error', 'message' => 'Transaction is already complete'], 409);
        }
        
        $filename = $transaction->id . '_' . $user->id . '.' . $request->file('product_file')->extension();
        $request->file('product_file')->move(storage_path('transactions/products'), $filename);
        $transaction->update([
            'product_file' => storage_path('transactions/products') . '/' . $filename
        ]);
        $transaction->refresh();

        $transaction->notifications()->create([
            'user_id' => $transaction->buyer_id,
            'message' => "Penjual telah mengunggah produk anda untuk transaksi: " . $transaction->id
        ]);

        return response()->json($transaction, 201);
    }

    public function completeTransaction($id)
    {
        $user = JWTAuth::user();

        $transaction = Transaction::findOrFail($id);

        if ($transaction->buyer_id != $user->id) {            
            return response()->json(['status' => 'error', 'message' => 'User do not have permission to complete this transaction. Action denied.'], 401);
        }

        if ($transaction->status) {            
            return response()->json(['status' => 'error', 'message' => 'Transaction is already complete.'], 409);
        }

        if (!$transaction->payment_file || !$transaction->product_file) {
            return response()->json(['status' => 'error', 'message' => 'Transaction is still in progress.'], 403);
        }

        $transaction->update(['status' => true]);

        $transaction->notifications()->create([
            'user_id' => $transaction->buyer_id,
            'message' => "Pembelian produk \"" . $transaction->product->title . "\" dengan ID transaksi: " . $transaction->id . " telah selesai."
        ]);
        $transaction->notifications()->create([
            'user_id' => $transaction->seller_id,
            'message' => "Penjualan produk \"" . $transaction->product->title . "\" dengan ID transaksi: " . $transaction->id . " telah selesai."
        ]);

        $transaction->refresh();

        return response()->json($transaction, 200);
    }
}
