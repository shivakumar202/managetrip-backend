<?php

namespace App\Http\Controllers\Admin\Updates;

use App\Http\Controllers\Controller;
use App\Models\Drive;
use App\Models\Updates;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $updates = Updates::with(['images', 'comments'])->orderBy('created_at', 'desc')->get();
        return response()->json(['updates' => $updates], 200);
    }

    public function addComment(Request $request, string $id)
    {
        $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        $update = Updates::findOrFail($id);
        $comment = $update->comments()->create([
            'comment' => $request->comment,
        ]);

        return response()->json(['message' => 'Comment added', 'comment' => $comment], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Handle file uploads
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('', 'frontend');
                $imagePaths[] = $path;
            }
        }

       $update =  Updates::create([
            'title' => $request->title,
            'description' => $request->description,
        ]);

        foreach ($imagePaths as $path) {
            $update->images()->create([
                'table_name' => 'updates',
                'file' => $path,
            ]);
        }


        return response()->json(['message' => 'Update created successfully', 'images' => $imagePaths], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
