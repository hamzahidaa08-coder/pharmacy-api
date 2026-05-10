<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Medicine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'pharmacy') {
            $orders = Order::with(['orderItems.medicine', 'user'])->get();
            
            // Fetch prescriptions and map them to look like orders
            $prescriptions = \App\Models\Prescription::with('user')->get()->map(function($p) {
                return [
                    'id' => $p->id,
                    'user_id' => $p->user_id,
                    'status' => $p->status,
                    'total_amount' => 0,
                    'created_at' => $p->created_at,
                    'user' => $p->user,
                    'type' => 'Prescription',
                    'prescription_file' => $p->file_path,
                    'address' => $p->address,
                    'order_items' => []
                ];
            });

            // Merge and sort by date
            $all = $orders->map(function($o) {
                $o->type = 'Direct';
                return $o;
            })->concat($prescriptions)->sortByDesc('created_at')->values();

            return response()->json($all);
        } else {
            $orders = Order::with(['orderItems.medicine', 'user', 'delivery.user'])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json($orders);
        }
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $order = Order::with(['orderItems.medicine', 'user', 'delivery.user'])->findOrFail($id);

        if ($user->role !== 'pharmacy' && $user->role !== 'delivery' && $order->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($order);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.medicine_id' => 'required|exists:medicines,id',
            'items.*.quantity' => 'required|integer|min:1',
            'address' => 'required|string|max:500',
        ]);

        $totalAmount = 0;
        $orderItemsData = [];

        DB::beginTransaction();

        try {
            foreach ($validated['items'] as $item) {
                $medicine = Medicine::findOrFail($item['medicine_id']);
                
                if ($medicine->stock < $item['quantity']) {
                    return response()->json([
                        'message' => "Insufficient stock for medicine: {$medicine->name}"
                    ], 422);
                }

                $price = $medicine->price;
                $quantity = $item['quantity'];
                
                $totalAmount += ($price * $quantity);
                
                $orderItemsData[] = [
                    'medicine_id' => $medicine->id,
                    'quantity' => $quantity,
                    'price' => $price,
                ];
                
                $medicine->stock -= $quantity;
                if ($medicine->stock == 0) {
                    $medicine->status = 'out_of_stock';
                }
                $medicine->save();
            }

            $order = Order::create([
                'user_id' => $request->user()->id,
                'status' => 'pending',
                'total_amount' => $totalAmount,
                'address' => $validated['address'],
            ]);

            foreach ($orderItemsData as $itemData) {
                $order->orderItems()->create($itemData);
            }

            DB::commit();

            return response()->json($order->load(['orderItems.medicine', 'user']), 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Order processing failed', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $user = $request->user();
        $order = Order::find($id);
        $isPrescription = false;

        if (!$order) {
            $order = \App\Models\Prescription::find($id);
            if (!$order) {
                return response()->json(['message' => 'Order or Prescription not found'], 404);
            }
            $isPrescription = true;
        }

        $validated = $request->validate([
            'status' => 'required|string|in:accepted,rejected,delivered,picked_up,out_for_delivery',
        ]);

        $newStatus = $validated['status'];

        if ($user->role === 'pharmacy') {
            if (!in_array($newStatus, ['accepted', 'rejected'])) {
                return response()->json(['message' => 'Pharmacy can only accept or reject orders'], 403);
            }
        } elseif ($user->role === 'delivery') {
            if (!in_array($newStatus, ['delivered', 'picked_up', 'out_for_delivery'])) {
                return response()->json(['message' => 'Delivery agent can only pick up, start navigation, or deliver'], 403);
            }
        } else {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $order->update(['status' => $newStatus]);

        // If delivery agent is picking up or updating, sync the deliveries table
        if ($user->role === 'delivery') {
            $deliveryData = [
                'user_id' => $user->id,
                'status' => $newStatus
            ];
            
            if ($isPrescription) {
                // Assuming you use the same deliveries table or handle it in the model
                $order->delivery()->updateOrCreate(['prescription_id' => $order->id], $deliveryData);
            } else {
                $order->delivery()->updateOrCreate(['order_id' => $order->id], $deliveryData);
            }
        }

        // Refresh with relations for the frontend
        $order->load(['user', 'delivery.user']);
        if (!$isPrescription) $order->load('orderItems.medicine');

        return response()->json([
            'message' => ($isPrescription ? "Prescription" : "Order") . " status updated to {$newStatus}",
            'order' => $order
        ]);
    }
}
