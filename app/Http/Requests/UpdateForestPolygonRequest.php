<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateForestPolygonRequest extends FormRequest
{
    public function authorize(): bool
    {
        $polygon = $this->route('uuid') ?
            \App\Models\ForestPolygon::where('uuid', $this->route('uuid'))->first() :
            null;

        return $polygon && (
            $polygon->user_id === auth()->id() ||
            auth()->user()->can('update-any-polygon')
        );
    }

    public function rules(): array
    {
        return [
            'type' => 'sometimes|in:primary,secondary,plantation,degraded,regrowth,mangrove,peat_swamp,montane,unknown',
            'height_category' => 'nullable|in:tall,medium,short,low',
            'canopy_density' => 'nullable|in:dense,medium,sparse,very_sparse',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'observation_date' => 'nullable|date|before_or_equal:today',
            'confidence_level' => 'nullable|in:high,medium,low',
            'is_public' => 'boolean',
            'additional_properties' => 'nullable|array'
        ];
    }
}
