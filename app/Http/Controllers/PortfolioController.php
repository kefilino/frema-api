<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\Portfolio;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class PortfolioController extends Controller
{
    public function showPortfolio()
    {
        $user = JWTAuth::user();
        return response()->json(Portfolio::where('id_user', $user->id)->get());
    }

    public function showPortfolioByUserId($id)
    {
        return response()->json(Portfolio::find($id));
    }

    public function create(Request $request)
    {
        $user = JWTAuth::user();

        $this->validate($request, [
            'title' => 'required',
            'description' => 'required',
            'image' => 'image',
        ]);

        $portfolio = Portfolio::create([
            'id_user' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
        ]);

        if ($request->file('image')) {
            $filename = $portfolio->id . '.' . $request->file('image')->extension();
            $request->file('image')->move(storage_path('portfolio'), $filename);
            $image = Image::create([
                'src' => storage_path('portfolio') . '/' . $filename
            ]);
            $portfolio->update([
                'id_image' => $image->id
            ]);
        }

        return response()->json($portfolio, 201);
    }

    public function update($id, Request $request)
    {
        $user = JWTAuth::user();

        $this->validate($request, [
            'title' => 'required',
            'description' => 'required',
            'image' => 'image',
        ]);

        $portfolio = Portfolio::findOrFail($id);

        if ($portfolio->id_user != $user->id) {            
            return response()->json(['status' => 'error', 'message' => 'User ID mismatch']);
        }

        $portfolio->update([
            'id_user' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
        ]);

        if ($request->file('image')) {
            $filename = $portfolio->id . '.' . $request->file('image')->extension();
            $request->file('image')->move(storage_path('portfolio'), $filename);
        }

        return response()->json($portfolio, 201);
    }

    public function delete($id)
    {
        $user = JWTAuth::user();

        $portfolio = Portfolio::findOrFail($id);

        if ($portfolio->id_user != $user->id) {            
            return response()->json(['status' => 'error', 'message' => 'User ID mismatch']);
        }

        $portfolio->delete();

        return response()->json($portfolio, 201);
    }
}
