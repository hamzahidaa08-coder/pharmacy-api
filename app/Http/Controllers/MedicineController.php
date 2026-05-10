<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use Illuminate\Http\Request;

class MedicineController extends Controller
{
    public function index(Request $request)
    {
        // Limit to 50 records by default or use a search query, because 20,000 records crash the server/browser
        $query = Medicine::query();
        
        if ($request->has('search')) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }
        
        if ($request->has('category') && $request->category !== 'All') {
            $query->where('category', $request->category);
        }
        
        return response()->json($query->latest()->limit(500)->get());
    }

    public function categories()
    {
        return response()->json(Medicine::select('category')->distinct()->pluck('category'));
    }

    public function show($id)
    {
        $medicine = Medicine::findOrFail($id);
        return response()->json($medicine);
    }

    public function store(Request $request)
    {
        if ($request->user()->role !== 'pharmacy') {
            return response()->json(['message' => 'Forbidden: Only pharmacy role can perform this action.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'category' => 'sometimes|string|max:255',
            'usage' => 'sometimes|string|max:255',
            'image' => 'sometimes|nullable|string',
            'available_date' => 'sometimes|nullable|date',
        ]);

        if ($validated['stock'] == 0) {
            $validated['status'] = 'out_of_stock';
        } else {
            $validated['status'] = 'in_stock';
        }

        $medicine = Medicine::create($validated);

        return response()->json($medicine, 201);
    }

    public function update(Request $request, $id)
    {
        if ($request->user()->role !== 'pharmacy') {
            return response()->json(['message' => 'Forbidden: Only pharmacy role can perform this action.'], 403);
        }

        $medicine = Medicine::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'stock' => 'sometimes|required|integer|min:0',
            'category' => 'sometimes|string|max:255',
            'usage' => 'sometimes|string|max:255',
            'image' => 'sometimes|nullable|string',
            'available_date' => 'sometimes|nullable|date',
        ]);

        if (isset($validated['stock'])) {
            if ($validated['stock'] == 0) {
                $validated['status'] = 'out_of_stock';
            } else {
                $validated['status'] = 'in_stock';
            }
        }

        $medicine->update($validated);

        return response()->json($medicine);
    }

    public function destroy(Request $request, $id)
    {
        if ($request->user()->role !== 'pharmacy') {
            return response()->json(['message' => 'Forbidden: Only pharmacy role can perform this action.'], 403);
        }

        $medicine = Medicine::findOrFail($id);
        $medicine->delete();

        return response()->json(['message' => 'Medicine deleted successfully']);
    }
}
