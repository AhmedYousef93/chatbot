<?php

namespace Database\Seeders;

use App\Models\HousingUnit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HousingUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        HousingUnit::insert([
            [
                'name'         => 'شقة فاخرة في الرياض',
                'location'     => 'الرياض',
                'price'        => 3500.00,
                'rooms'        => 3,
                'description'  => 'شقة مفروشة بالكامل قريبة من الخدمات.',
                'features'     => json_encode(['wifi', 'parking', 'ac']),
                'images'       => json_encode([
                    'https://example.com/images/riyadh1.jpg',
                    'https://example.com/images/riyadh2.jpg',
                ]),
                'is_available' => true,
            ],
            [
                'name'         => 'فيلا راقية في جدة',
                'location'     => 'جدة',
                'price'        => 8000.00,
                'rooms'        => 5,
                'description'  => 'فيلا بمسبح وحديقة واسعة في حي هادئ.',
                'features'     => json_encode(['wifi', 'garden', 'pool']),
                'images'       => json_encode([
                    'https://example.com/images/jeddah1.jpg',
                    'https://example.com/images/jeddah2.jpg',
                ]),
                'is_available' => true,
            ],
            [
                'name'         => 'استوديو مريح في الدمام',
                'location'     => 'الدمام',
                'price'        => 2000.00,
                'rooms'        => 1,
                'description'  => 'استوديو صغير مثالي للعزاب، قريب من البحر.',
                'features'     => json_encode(['ac']),
                'images'       => json_encode([
                    'https://example.com/images/dammam1.jpg',
                ]),
                'is_available' => false,
            ],
        ]);
    }
}
