<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CategoryController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'nullable',
        ]);

        $data = [
            'name' => $request->name,
            'description' => $request->description,
        ];

        $response = Http::post('http://localhost:8080/categories', $data);

        // Buat Testing

        // if ($response->successful()) {
        //     $result = $response->json();
        //     dd($result);
        // } else {
        //     dd([
        //         'url' => 'http://localhost:8080/products',
        //         'data' => $data,
        //         'response' => $response->body(),
        //         'status' => $response->status(),
        //     ]);
        // }

        return redirect()->back();
    }
}