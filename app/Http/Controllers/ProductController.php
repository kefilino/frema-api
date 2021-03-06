<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Image;
use App\Models\Product;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProductController extends Controller
{
    public function showAllProducts()
    {
        return response()->json(Product::with([
            'album.images:id,album_id,src',
            'user:id,name,university',
            'user.image:id,user_id,src',
            'reviews:id,client_id,freelancer_id,product_id,rating,review'
        ])->get(), 200);
    }

    public function showUserProducts()
    {
        return response()->json(Product::with([
            'album.images:id,album_id,src',
            'user:id,name,university',
            'user.image:id,user_id,src',
            'reviews:id,client_id,freelancer_id,product_id,rating,review'
        ])->where('user_id', JWTAuth::user()->id)->get(), 200);
    }

    public function showProductById($id)
    {
        return response()->json(Product::with([
            'album.images:id,album_id,src',
            'user:id,name,university',
            'user.image:id,user_id,src',
            'reviews:id,client_id,freelancer_id,product_id,rating,review'
        ])->find($id), 200);
    }

    public function showProductsByUserId($id)
    {
        return response()->json(Product::with([
            'album.images:id,album_id,src',
            'user:id,name,university',
            'user.image:id,user_id,src',
            'reviews:id,client_id,freelancer_id,product_id,rating,review'
        ])->where('user_id', $id)->get(), 200);
    }

    public function create(Request $request)
    {
        $user = JWTAuth::user();

        $this->validate($request, [
            'title' => 'required',
            'description' => 'required',
            'price' => 'required',
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
            $images = [];
            $album = Album::create(['title' => $request->title]);
            foreach ($request->file('images') as $i => $image) {
                $filename = $product->id . '_' . $i . '.' . $image->extension();
                $image->move('product', $filename);
                array_push($images, new Image(['src' => 'product/' . $filename]));
            }
            $album->images()->saveMany(
                $images
            );
            $product->album()->save($album);
        }

        $product->refresh();
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
            return response()->json(['status' => 'error', 'message' => 'User ID mismatch'], 401);
        }

        $product->update([
            'user_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
        ]);

        if ($request->hasfile('images')) {
            if ($product->album) {
                $product->album->product()->dissociate();
                $product->album->save();
            }
            
            $album = $product->album;
            foreach ($request->file('images') as $i => $image) {
                $filename = $product->id . '_' . $i . '.' . $image->extension();
                $image->move('product', $filename);
                $album->images()->save(
                    new Image(['src' => 'product/' . $filename])
                );
            }
            $product->album()->save($album);
        }

        $product->refresh();
        return response()->json($product, 200);
    }

    public function delete($id)
    {
        $user = JWTAuth::user();

        $product = Product::findOrFail($id);

        if ($product->user_id != $user->id) {            
            return response()->json(['status' => 'error', 'message' => 'User ID mismatch'], 401);
        }

        $product->delete();

        return response()->json($product, 201);
    }

    public function deleteProductImage($id, $index)
    {
        $user = JWTAuth::user();

        $product = Product::findOrFail($id);

        if ($product->user_id != $user->id) {
            return response()->json(['status' => 'error', 'message' => 'User ID mismatch'], 401);
        }

        if (sizeof($product->album->images) == 1) {
            return response()->json(['status' => 'error', 'message' => 'Product has to have at least one image'], 403);
        }

        if ($index > sizeof($product->album->images) || $index < 1) {
            return response()->json(['status' => 'error', 'message' => 'Index out of bounds'], 400);
        }

        $product->album->images[$index-1]->album()->dissociate();
        $product->album->images[$index-1]->save();

        return response()->json($product, 200);
    }
}
