<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;


class DepartmentController extends Controller
{
    // List all departments
    public function index()
    {
        $departments = Department::all();
        return response()->json($departments);
    }

    // Create a new department
    public function store(Request $request)
    {
        // Validate input
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:departments',
            'description' => 'nullable|string',
        ]);

        $department = Department::create($validatedData);
        return response()->json($department, 201);
    }

    // Show a specific department
    public function show(Department $department)
    {
        return response()->json($department);
    }

    // Update an existing department
    public function update(Request $request, Department $department)
    {
        // Validate input
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:departments,name,' . $department->id,
            'description' => 'nullable|string',
        ]);

        $department->update($validatedData);
        return response()->json($department);
    }

    // Delete a department
    public function destroy(Department $department)
    {
        $department->delete();
        return response()->json(null, 204);
    }
}
