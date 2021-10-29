<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Image;
use App\Models\Job;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class JobController extends Controller
{
    public function showJobs()
    {
        return response()->json(Job::with('album.images')->get());
    }

    public function showJobsById($id)
    {
        return response()->json(Job::with('album.images')->where('id', $id)->get());
    }
    
    public function showUserJobs()
    {
        $user = JWTAuth::user();
        return response()->json(Job::with('album.images')->where('user_id', $user->id)->get());
    }

    public function showJobsByUserId($id)
    {
        return response()->json(Job::with('album.images')->where('user_id', $id)->get());
    }

    public function create(Request $request)
    {
        $user = JWTAuth::user();

        $this->validate($request, [
            'title' => 'required',
            'description' => 'required',
            'image' => 'image',
        ]);

        $job = $user->jobs()->create([
            'user_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
        ]);

        if ($request->file('images')) {
            $album = Album::create(['title' => $request->title]);
            foreach ($request->file('images') as $i => $image) {
                $filename = $job->id . '_' . $i . '.' . $image->extension();
                $image->move('job', $filename);
                $album->images()->save(
                    new Image(['src' => 'public/job/' . $filename])
                );
            }
            $job->album()->save($album);
        }

        $job->refresh();
        return response()->json($job, 201);
    }

    public function update($id, Request $request)
    {
        $user = JWTAuth::user();

        $this->validate($request, [
            'title' => 'required',
            'description' => 'required',
            'image' => 'image',
        ]);

        $job = Job::findOrFail($id);

        if ($job->user_id != $user->id) {            
            return response()->json(['status' => 'error', 'message' => 'User ID mismatch']);
        }

        $job->update([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        if ($request->file('images')) {
            if ($job->album) {
                $job->album->product()->dissociate();
                $job->album->save();
            }

            $album = Album::create(['title' => $request->title]);
            foreach ($request->file('images') as $i => $image) {
                $filename = $job->id . '_' . $i . '.' . $image->extension();
                $image->move('job', $filename);
                $album->images()->save(
                    new Image(['src' => 'public/job/' . $filename])
                );
            }
            $job->album()->save($album);
        }

        $job->refresh();
        return response()->json($job, 201);
    }

    public function delete($id)
    {
        $user = JWTAuth::user();

        $job = Job::findOrFail($id);

        if ($job->user_id != $user->id) {            
            return response()->json(['status' => 'error', 'message' => 'User ID mismatch']);
        }

        $job->delete();

        return response()->json($job, 201);
    }
}
