<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ForestPolygon;
use App\Services\GeospatialService;

class CalculatePolygonMetrics extends Command
{
    protected $signature = 'forest:calculate-metrics {--force : Force recalculation even if metrics exist}';
    protected $description = 'Calculate area and centroid for forest polygons';

    public function handle()
    {
        $query = ForestPolygon::query();

        if (!$this->option('force')) {
            $query->where(function($q) {
                $q->whereNull('area_hectares')
                  ->orWhereNull('centroid_latitude')
                  ->orWhereNull('centroid_longitude');
            });
        }

        $polygons = $query->get();

        if ($polygons->isEmpty()) {
            $this->info('No polygons need metric calculation.');
            return;
        }

        $this->info("Calculating metrics for {$polygons->count()} polygons...");

        $bar = $this->output->createProgressBar($polygons->count());
        $bar->start();

        foreach ($polygons as $polygon) {
            try {
                if ($polygon->geometry) {
                    $area = GeospatialService::calculateArea($polygon->geometry);
                    $centroid = GeospatialService::calculateCentroid($polygon->geometry);

                    $polygon->update([
                        'area_hectares' => $area,
                        'area_square_meters' => $area * 10000,
                        'centroid_latitude' => $centroid['latitude'],
                        'centroid_longitude' => $centroid['longitude']
                    ]);
                }
            } catch (\Exception $e) {
                $this->error("Failed to calculate metrics for polygon {$polygon->uuid}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Metric calculation completed!');
    }
}
