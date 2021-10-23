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
        return response()->json(Portfolio::with('image')->where('user_id', $user->id)->get());
    }

    public function showPortfolioByUserId($id)
    {
        return response()->json(Portfolio::with('image')->where('user_id', $id)->get());
    }

    public function create(Request $request)
    {
        $user = JWTAuth::user();

        $this->validate($request, [
            'title' => 'required',
            'description' => 'required',
            'image' => 'image',
        ]);

        $portfolio = $user->portfolios()->create([
            'user_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
        ]);

        if ($request->file('image')) {
            $filename = $portfolio->id . '.' . $request->file('image')->extension();
            $request->file('image')->move('portfolio', $filename);
            $portfolio->image()->save(
                new Image(['src' => 'public/portfolio/' . $filename])
            );
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

        if ($portfolio->user_id != $user->id) {            
            return response()->json(['status' => 'error', 'message' => 'User ID mismatch']);
        }

        $portfolio->update([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        if ($request->file('image')) {
            $filename = $portfolio->id . '.' . $request->file('image')->extension();
            $request->file('image')->move('portfolio', $filename);
            $portfolio->image()->save(
                new Image(['src' => 'public/portfolio/' . $filename])
            );
        }

        return response()->json($portfolio, 201);
    }

    public function delete($id)
    {
        $user = JWTAuth::user();

        $portfolio = Portfolio::findOrFail($id);

        if ($portfolio->user_id != $user->id) {            
            return response()->json(['status' => 'error', 'message' => 'User ID mismatch']);
        }

        $portfolio->delete();

        return response()->json($portfolio, 201);
    }
}
