<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\User;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'delivery') {
            $deliveries = Delivery::with('order.user')->where('user_id', $user->id)->get();
        } elseif ($user->role === 'pharmacy') {
            $deliveries = Delivery::with(['order.user', 'user'])->get();
        } else {
            return response()->json(['message' => 'Forbidden: Not authorized to view deliveries'], 403);
        }

        return response()->json($deliveries);
    }

    public function assign(Request $request)
    {
        if ($request->user()->role !== 'pharmacy') {
            return response()->json(['message' => 'Forbidden: Only pharmacy can assign deliveries'], 403);
        }

        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'user_id' => 'required|exists:users,id',
            'address' => 'required|string|max:1000',
        ]);

        $deliveryAgent = User::findOrFail($validated['user_id']);
        if ($deliveryAgent->role !== 'delivery') {
            return response()->json(['message' => 'Validation error: User is not a delivery agent'], 422);
        }

        // Check if the order is already assigned
        if (Delivery::where('order_id', $validated['order_id'])->exists()) {
            return response()->json(['message' => 'Conflict: Order is already assigned to a delivery agent'], 409);
        }

        $delivery = Delivery::create([
            'order_id' => $validated['order_id'],
            'user_id' => $validated['user_id'],
            'status' => 'pending',
            'address' => $validated['address'],
        ]);

        return response()->json([
            'message' => 'Delivery assigned successfully',
            'delivery' => $delivery->load('user', 'order')
        ], 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $user = $request->user();
        $delivery = Delivery::findOrFail($id);

        if ($user->role === 'delivery' && $delivery->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden: You can only update your own assigned deliveries'], 403);
        }

        if ($user->role !== 'delivery' && $user->role !== 'pharmacy') {
            return response()->json(['message' => 'Forbidden: Not authorized to update deliveries'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:picked_up,in_transit,delivered,failed',
        ]);

        $delivery->update(['status' => $validated['status']]);

        // Auto-sync order status if delivered
        if ($validated['status'] === 'delivered') {
            $delivery->order()->update(['status' => 'delivered']);
        }

        return response()->json([
            'message' => "Delivery status successfully updated to {$validated['status']}",
            'delivery' => $delivery
        ]);
    }

    public function updateLocation(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $delivery = Delivery::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'picked_up', 'in_transit'])
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$delivery) {
            return response()->json(['message' => 'No active delivery found'], 404);
        }

        $delivery->update([
            'lat' => $validated['lat'],
            'lng' => $validated['lng'],
        ]);

        return response()->json(['message' => 'Location updated']);
    }

    public function getLocation(Request $request)
    {
        $orderId = $request->query('order_id');
        
        $delivery = Delivery::where('order_id', $orderId)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$delivery) {
            return response()->json(['message' => 'Delivery not found'], 404);
        }

        return response()->json([
            'lat' => (float)$delivery->lat,
            'lng' => (float)$delivery->lng
        ]);
    }
}
