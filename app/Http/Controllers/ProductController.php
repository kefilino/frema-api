<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Image;
use App\Models\Product;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProductController extends Controller
{
    public function showProducts()
    {
        $user = JWTAuth::user();
        return response()->json(Product::where('user_id', $user->id)->get());
    }

    public function showProductById($id)
    {
        return response()->json(Product::find($id));
    }

    public function showProductsByUserId($id)
    {
        return response()->json(Product::where('user_id', $id)->get());
    }

    public function create(Request $request)
    {
        $user = JWTAuth::user();

        $this->validate($request, [
            'title' => 'required',
            'description' => 'required',
            'images' => 'required',
            'images.*' => 'image',
        ]);

        $product = $user->products()->create([
            'title' => $request->title,
            'description' => $request->description,
            'price' => $request->price,
            'availability' => true,
        ]);

        if ($request->file('images')) {
            $album = Album::create(['title' => $request->title]);
            foreach ($request->file('images') as $i => $image) {
                $filename = $product->id . '_' . $i . '.' . $image->extension();
                $image->move('product', $filename);
                $album->images()->save(
                    new Image(['src' => 'public/product/' . $filename])
                );
            }
            $product->album()->save($album);
        }

        return response()->json($product, 201);
    }

    public function update($id, Request $request)
    {
        $user = JWTAuth::user();

        $this->validate($request, [
            'title' => 'required',
            'description' => 'required',
            'images' => 'required',
            'images.*' => 'image',
        ]);

        $product = Product::findOrFail($id);

        if ($product->user_id != $user->id) {            
            return response()->json(['status' => 'error', 'message' => 'User ID mismatch']);
        }

        $product->update([
            'user_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
        ]);

        if ($request->file('images')) {
            $album = Album::create(['title' => $request->title]);
            foreach ($request->file('images') as $i => $image) {
                $filename = $product->id . '_' . $i . '.' . $image->extension();
                $image->move('product', $filename);
                $album->images()->save(
                    new Image(['src' => 'public/product/' . $filename])
                );
            }
            $product->album()->save($album);
        }

        return response()->json($product, 201);
    }

    public function delete($id)
    {
        $user = JWTAuth::user();

        $product = Product::findOrFail($id);

        if ($product->user_id != $user->id) {            
            return response()->json(['status' => 'error', 'message' => 'User ID mismatch']);
        }

        $product->delete();

        return response()->json($product, 201);
    }
}
