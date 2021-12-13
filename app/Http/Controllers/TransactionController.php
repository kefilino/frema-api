<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
                ->get(),
            200
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
                ->get(),
            200
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
                ->get(),
            200
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

    public function getProductFile($id)
    {
        $transaction = Transaction::with([
            'product',
            'seller:id,name',
            'buyer:id,name',
            'product.album.images:id,album_id,src',
            'seller.image:id,user_id,src',
            'buyer.image:id,user_id,src'
        ])->find($id);

        $error = [];

        if (!$transaction) {
            $error['message'] = 'Transaction not found';
            $error['code'] = 404;
        } else if ($transaction->buyer_id != JWTAuth::user()->id && $transaction->seller_id != JWTAuth::user()->id) {
            $error['message'] = 'User ID mismatch - Unauthorized';
            $error['code'] = 401;
        } else if (!file_exists($transaction->product_file)) {
            $error['message'] = 'File does not exist';
            $error['code'] = 404;
        }

        if (!empty($error)) {
            return response()->json(['status' => 'error', 'message' => $error['message']], $error['code']);
        }

        $file = file_get_contents($transaction->product_file);
        return response($file, 200)->header('Content-Type', 'image/jpeg');
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

        $error = [];

        if ($transaction->buyer_id != $user->id) {
            $error['message'] = 'User do not have permission to complete this transaction. Action denied.';
            $error['code'] = 401;
        } else if ($transaction->status) {
            $error['message'] = 'Transaction is already complete.';
            $error['code'] = 409;
        } else if (!$transaction->payment_file || !$transaction->product_file || !$transaction->payment_status) {
            $error['message'] = 'Transaction is still in progress.';
            $error['code'] = 403;
        }

        if (!empty($error)) {
            return response()->json(['status' => 'error', 'message' => $error['message']], $error['code']);
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

    public function confirmPayment($id)
    {
        $user = JWTAuth::user();

        if ($user->id != 1) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 401);
        }

        $transaction = Transaction::findOrFail($id);
        $transaction->update(['payment_status' => true]);

        $transaction->notifications()->create([
            'user_id' => $transaction->buyer_id,
            'message' => "Pembayaran produk \"" . $transaction->product->title . "\" dengan ID transaksi: " . $transaction->id . " telah dikonfirmasi."
        ]);
        $transaction->notifications()->create([
            'user_id' => $transaction->seller_id,
            'message' => "Pembayaran produk \"" . $transaction->product->title . "\" dengan ID transaksi: " . $transaction->id . " telah dikonfirmasi."
        ]);

        $transaction->refresh();

        return response()->json($transaction, 200);
    }
}
