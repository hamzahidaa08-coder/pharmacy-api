<?php

namespace App\Http\Controllers;

use App\Models\Prescription;
use Illuminate\Http\Request;

class PrescriptionController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // Max 5MB
            'notes' => 'nullable|string|max:1000',
            'address' => 'required|string|max:1000',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('prescriptions', 'public');
        } else {
            return response()->json(['message' => 'Image upload failed'], 400);
        }

        $prescription = Prescription::create([
            'user_id' => $request->user()->id,
            'file_path' => $path,
            'status' => 'pending',
            'notes' => $request->notes,
            'address' => $request->address,
        ]);

        return response()->json([
            'message' => 'Prescription uploaded successfully',
            'prescription' => $prescription
        ], 201);
    }
}
