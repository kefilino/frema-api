<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Image;
use App\Models\Review;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class ReviewController extends Controller
{
    public function showReviewsAsClient()
    {
        $reviews = Review::with('album.images')
            ->where('client_id', JWTAuth::user()->id)
            ->get();
        return response()->json($reviews);
    }

    public function showReviewsAsFreelancer()
    {
        $reviews = Review::with('album.images')
            ->where('freelancer_id', JWTAuth::user()->id)
            ->get();
        return response()->json($reviews);
    }

    public function showReviewById($id)
    {
        return response()->json(Review::find($id));
    }

    public function showReviewByProductId($id)
    {
        return response()->json(Review::where('product_id', $id)->get());
    }

    public function showReviewByUserId($id)
    {
        return response()->json(Review::where('client_id', $id)->orWhere('freelancer_id', $id)->get());
    }

    public function create($id, Request $request)
    {
        $user = JWTAuth::user();

        $this->validate($request, [
            'rating' => 'required|integer',
            'review' => 'required',
            'images' => 'required',
            'images.*' => 'image',
        ]);

        $transaction = Transaction::find($id);
        $product = $transaction->product;

        $review = $transaction->review()->create([
            'rating' => $request->rating,
            'review' => $request->review,
        ]);

        if ($request->file('images')) {
            $images = [];
            $album = Album::create(['title' => $product->title]);
            foreach ($request->file('images') as $i => $image) {
                $filename = $review->id . '_' . $i . '.' . $image->extension();
                $image->move('product', $filename);
                array_push($images, new Image(['src' => 'public/review/' . $filename]));
            }
            $album->images()->saveMany(
                $images
            );
            $review->album()->save($album);
        }

        $user->reviewsAsClient()->save($review);
        $product->user->reviewsAsFreelancer()->save($review);
        $product->reviews()->save($review);

        $review->refresh();
        return response()->json($review, 201);
    }
}
