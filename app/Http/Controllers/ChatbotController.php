<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function query(Request $request)
    {
        $message = $request->query('message');

        if (!$message) {
            return response()->json([
                'text' => 'How can I help you today?'
            ]);
        }

        // Search for medicines where name, category, description, or usage contains the keyword
        $medicines = Medicine::where('name', 'LIKE', "%{$message}%")
            ->orWhere('category', 'LIKE', "%{$message}%")
            ->orWhere('description', 'LIKE', "%{$message}%")
            ->orWhere('usage', 'LIKE', "%{$message}%")
            ->take(5) // Limit to top 5 results for chat
            ->get();

        if ($medicines->isEmpty()) {
            return response()->json([
                'text' => "I'm sorry, I couldn't find any information about '{$message}'. Try searching for something else like 'Pain Relief' or a specific medicine name.",
                'medicines' => []
            ]);
        }

        return response()->json([
            'text' => "I found several items related to '{$message}'. Here are the top matches:",
            'medicines' => $medicines
        ]);
    }
}
