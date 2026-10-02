<?php

namespace Database\Seeders;

use App\Models\Disease;
use App\Models\RiceVariety;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        foreach (['Arborio', 'Basmati', 'Ipsala', 'Jasmine', 'Karacadag'] as $name) {
            RiceVariety::firstOrCreate(['name' => $name]);
        }

        $diseases = [
            'Bacterial Leaf Blight',
            'Bacterial Leaf Streak',
            'Bakanae',
            'Brown Spot',
            'Grassy Stunt Virus',
            'Healthy Rice Plant',
            'Narrow Brown Spot',
            'Ragged Stunt Virus',
            'Rice Blast',
            'Rice False Smut',
            'Sheath Blight',
            'Sheath Rot',
            'Stem Rot',
            'Tungro Virus',
        ];

        foreach ($diseases as $name) {
            Disease::firstOrCreate(
                ['name' => $name],
                ['description' => 'To be filled in.', 'prevention_tips' => 'To be filled in.'],
            );
        }
    }
}
