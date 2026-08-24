<?php

namespace App\Http\Controllers;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Response;


class CategoryController extends Controller
{
    // Get all categories
    public function index()
    {
        $categories = Category::all();
        return response()->json($categories, Response::HTTP_OK);
    }

    // Create a new category
    public function store(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'name' => 'required|string|unique:categories,name',
            'description' => 'nullable|string',
        ]);

        $category = Category::create($validated);

        return response()->json($category, Response::HTTP_CREATED);
    }

    // Update an existing category
    public function update(Request $request, $id)
    {
        // Find category
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['message' => 'Category not found'], Response::HTTP_NOT_FOUND);
        }
        // Validate input
        $validated = $request->validate([
            'name' => 'sometimes|required|string|unique:categories,name,' . $id,
            'description' => 'nullable|string',
        ]);

        $category->update($validated);

        return response()->json($category, Response::HTTP_OK);
    }
    
    // Delete a category
    public function destroy($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['message' => 'Category not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if category has any attached resources to prevent SQL constraint violation
        if ($category->resources()->exists()) {
            return response()->json(['message' => 'Cannot delete category. It has attached resources.'], Response::HTTP_BAD_REQUEST);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted'], Response::HTTP_OK);
    }
}