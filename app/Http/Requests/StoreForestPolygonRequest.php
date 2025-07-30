<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreForestPolygonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'geometry' => [
                'required',
                'array',
                function ($attribute, $value, $fail) {
                    if (!isset($value['type']) || $value['type'] !== 'Polygon') {
                        $fail('The geometry must be a valid Polygon.');
                    }

                    if (!isset($value['coordinates']) || !is_array($value['coordinates'])) {
                        $fail('The geometry must have valid coordinates.');
                    }

                    // Validate coordinate structure
                    if (count($value['coordinates']) === 0 || count($value['coordinates'][0]) < 4) {
                        $fail('Polygon must have at least 4 coordinate pairs.');
                    }

                    // Validate coordinate values
                    foreach ($value['coordinates'][0] as $coord) {
                        if (!is_array($coord) || count($coord) !== 2) {
                            $fail('Each coordinate must be [longitude, latitude].');
                        }

                        [$lng, $lat] = $coord;
                        if (!is_numeric($lng) || !is_numeric($lat)) {
                            $fail('Coordinates must be numeric values.');
                        }

                        if ($lat < -90 || $lat > 90) {
                            $fail('Latitude must be between -90 and 90 degrees.');
                        }

                        if ($lng < -180 || $lng > 180) {
                            $fail('Longitude must be between -180 and 180 degrees.');
                        }
                    }
                }
            ],
            'type' => 'required|in:primary,secondary,plantation,degraded,regrowth,mangrove,peat_swamp,montane,unknown',
            'height_category' => 'nullable|in:tall,medium,short,low',
            'canopy_density' => 'nullable|in:dense,medium,sparse,very_sparse',
            'source' => 'required|in:manual,satellite,lidar,drone,field_survey,imported',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'observation_date' => 'nullable|date|before_or_equal:today',
            'confidence_level' => 'nullable|in:high,medium,low',
            'is_public' => 'boolean',
            'additional_properties' => 'nullable|array'
        ];
    }

    public function messages(): array
    {
        return [
            'geometry.required' => 'Polygon geometry is required.',
            'type.required' => 'Forest type is required.',
            'type.in' => 'Invalid forest type selected.',
            'source.required' => 'Data source is required.',
            'observation_date.before_or_equal' => 'Observation date cannot be in the future.'
        ];
    }
}
