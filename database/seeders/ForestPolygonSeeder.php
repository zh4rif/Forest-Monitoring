<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ForestPolygon;
use App\Models\User;

class ForestPolygonSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample polygons for testing
        $user = User::first();

        if (!$user) {
            $this->command->info('No users found. Please create a user first.');
            return;
        }

        $samplePolygons = [
            [
                'name' => 'Primary Rainforest Reserve',
                'type' => 'primary',
                'height_category' => 'tall',
                'canopy_density' => 'dense',
                'source' => 'field_survey',
                'confidence_level' => 'high',
                'observation_date' => now()->subDays(30),
                'geometry' => [
                    'type' => 'Polygon',
                    'coordinates' => [[
                        [101.6869, 3.1390],
                        [101.6889, 3.1390],
                        [101.6889, 3.1410],
                        [101.6869, 3.1410],
                        [101.6869, 3.1390]
                    ]]
                ],
                'user_id' => $user->id,
                'is_public' => true,
                'description' => 'Well-preserved primary rainforest with high biodiversity'
            ],
            [
                'name' => 'Secondary Forest Area',
                'type' => 'secondary',
                'height_category' => 'medium',
                'canopy_density' => 'medium',
                'source' => 'satellite',
                'confidence_level' => 'medium',
                'observation_date' => now()->subDays(15),
                'geometry' => [
                    'type' => 'Polygon',
                    'coordinates' => [[
                        [101.6900, 3.1300],
                        [101.6920, 3.1300],
                        [101.6920, 3.1320],
                        [101.6900, 3.1320],
                        [101.6900, 3.1300]
                    ]]
                ],
                'user_id' => $user->id,
                'is_public' => true,
                'description' => 'Regenerating forest area showing good recovery'
            ],
            [
                'name' => 'Palm Oil Plantation',
                'type' => 'plantation',
                'height_category' => 'short',
                'canopy_density' => 'sparse',
                'source' => 'drone',
                'confidence_level' => 'high',
                'observation_date' => now()->subDays(7),
                'geometry' => [
                    'type' => 'Polygon',
                    'coordinates' => [[
                        [101.6850, 3.1250],
                        [101.6870, 3.1250],
                        [101.6870, 3.1270],
                        [101.6850, 3.1270],
                        [101.6850, 3.1250]
                    ]]
                ],
                'user_id' => $user->id,
                'is_public' => false,
                'description' => 'Commercial palm oil plantation, recently established'
            ]
        ];

        foreach ($samplePolygons as $polygonData) {
            ForestPolygon::create($polygonData);
        }

        $this->command->info('Created ' . count($samplePolygons) . ' sample forest polygons.');
    }
}
