<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MedicineSeeder extends Seeder
{
    public function run(): void
    {
        $prefixes = ['Amoxi', 'Doli', 'Para', 'Ibu', 'Aspi', 'Venta', 'Clari', 'Azithro', 'Cipro', 'Loxa', 'Fent', 'Tram', 'Morph', 'Cod', 'Oxy', 'Lora', 'Cetir', 'Ome', 'Panto', 'Lanso'];
        $suffixes = ['cillin', 'prane', 'cetamol', 'profen', 'rin', 'lin', 'mycin', 'throm', 'flox', 'xacin', 'anyl', 'adol', 'phine', 'eine', 'contin', 'tadine', 'zine', 'prazole', 'razole', 'xine'];
        $forms = ['Tablets', 'Syrup', 'Capsules', 'Ointment', 'Gel', 'Drops', 'Injection', 'Inhaler', 'Suppository', 'Solution'];
        $dosages = ['50mg', '100mg', '200mg', '250mg', '400mg', '500mg', '800mg', '1000mg', '1g', '5ml', '10ml', '15ml'];
        
        $categories = [
            'Painkillers', 'Antibiotics', 'Vitamins', 'Cardiology', 
            'Neurology', 'Dermatology', 'Gastroenterology', 
            'Pediatrics', 'Ophthalmology', 'Dental', 'Allergy', 'First Aid'
        ];

        $usages = [
            'Take one tablet every 8 hours after meals.',
            'Apply to the affected area twice daily.',
            'Take 5ml every 6 hours for cough.',
            'Inhale one puff as needed for shortness of breath.',
            'Take with a full glass of water.',
            'Do not take on an empty stomach.',
            'Take at bedtime to avoid drowsiness.',
            'Dissolve in water before taking.',
            'Take one capsule daily in the morning.',
            'Use 2 drops in each eye every 4 hours.'
        ];

        $data = [];
        $now = Carbon::now();

        for ($i = 0; $i < 20000; $i++) {
            $name = $prefixes[array_rand($prefixes)] . $suffixes[array_rand($suffixes)] . ' ' . $dosages[array_rand($dosages)] . ' ' . $forms[array_rand($forms)];
            $stock = rand(0, 500); // Some will be out of stock
            
            $data[] = [
                'name' => $name,
                'description' => 'Generic or branded medication intended for general treatment. Please consult your physician before use.',
                'price' => rand(15, 600) + (rand(0, 99) / 100),
                'stock' => $stock,
                'status' => $stock == 0 ? 'out_of_stock' : 'in_stock',
                'category' => $categories[array_rand($categories)],
                'usage' => $usages[array_rand($usages)],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Insert in chunks to optimize database performance and memory
        $chunks = array_chunk($data, 1000);
        foreach ($chunks as $chunk) {
            DB::table('medicines')->insert($chunk);
        }
    }
}
