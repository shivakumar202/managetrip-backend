<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class QueryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'source' => 'required|string',
            'destination' => 'required|string',
            // Add other validation rules as needed
        ]);
        $query = new \App\Models\Query();
        $query->source = $request->input('source');
        $query->destination = $request->input('destination');
        $query->reference_id = $request->input('reference_id');     
        $query->sales_team_id = $request->input('sales_team_id');
        $query->tag_id = $request->input('tag_id');
        $query->source_contact_person = $request->input('source_contact_person');
        $query->start_date = $request->input('start_date');
        $query->nights = $request->input('nights');
        $query->adults = $request->input('adults'); 
        $query->children = $request->input('children');
        $query->children_ages = $request->input('children_ages');
        $query->salutation = $request->input('salutation');
        $query->name = $request->input('name');
        $query->email = $request->input('email');
        $query->phone = $request->input('phone');   
        $query->origin = $request->input('origin');
        $query->nationality = $request->input('nationality');
        $query->comments = $request->input('comments');
        $query->created_by = auth()->id(); // Assuming you have authentication set up
        $query->updated_by = auth()->id();
        $query->save();
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
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
