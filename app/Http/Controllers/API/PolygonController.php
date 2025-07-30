<?php
// app/Http/Controllers/API/PolygonController.php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PolygonController extends Controller
{
    public function index()
    {
        return response()->json([
            'polygons' => [],
            'message' => 'Polygon controller working'
        ]);
    }

    public function store(Request $request)
    {
        return response()->json([
            'message' => 'Polygon created (placeholder)',
            'data' => $request->all()
        ]);
    }

    public function show($id)
    {
        return response()->json([
            'polygon' => ['id' => $id],
            'message' => 'Polygon retrieved'
        ]);
    }

    public function update(Request $request, $id)
    {
        return response()->json([
            'message' => 'Polygon updated (placeholder)',
            'id' => $id,
            'data' => $request->all()
        ]);
    }

    public function destroy($id)
    {
        return response()->json([
            'message' => 'Polygon deleted (placeholder)',
            'id' => $id
        ]);
    }
}
