<?php

namespace App\Http\Controllers;

use App\Models\ResourceTemplate;
use App\Models\TemplateField;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;

class ResourceTemplateController extends Controller
{
    // List all resource templates with related data
    public function index(): JsonResponse
    {
        $templates = ResourceTemplate::with(['category', 'fields'])
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($templates);
    }

    // Show a specific resource template with related data
    public function show($id): JsonResponse
    {
        $template = ResourceTemplate::with(['category', 'fields'])->findOrFail($id);
        return response()->json($template);
    }

    // Create a new resource template with fields
    public function store(Request $request): JsonResponse
    {
        // Validate input
        $validated = $request->validate([
            'template_name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
            'created_by' => 'required|integer',
            'fields' => 'required|array|min:1',
            'fields.*.field_name' => 'required|string|max:255',
            'fields.*.field_type' => 'required|in:text,number,textarea,checkbox,image,dropdown',
            'fields.*.is_required' => 'sometimes|boolean',
            'fields.*.metadata' => 'nullable',
        ]);

        // Create template and fields in a transaction
        DB::beginTransaction();
        try {
            $template = ResourceTemplate::create([
                'template_name' => $validated['template_name'],
                'category_id' => $validated['category_id'],
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'],
                'created_by' => $validated['created_by'],
            ]);

            foreach ($validated['fields'] as $index => $fieldData) {
                // Use the Model's create method to trigger $casts for metadata
                $template->fields()->create([
                    'field_name' => $fieldData['field_name'],
                    'field_key' => TemplateField::generateFieldKey($fieldData['field_name']),
                    'field_type' => $fieldData['field_type'],
                    'is_required' => $fieldData['is_required'] ?? false,
                    'order_index' => $index,
                    'metadata' => $fieldData['metadata'] ?? null,
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Template created successfully', 'template' => $template->load('fields')], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed', 'error' => $e->getMessage()], 500);
        }
    }

    // Update an existing resource template and its fields
    public function update(Request $request, $id): JsonResponse
    {
        // Find template
        $template = ResourceTemplate::findOrFail($id);
        $validated = $request->validate([
            'template_name' => 'sometimes|string|max:255',
            'category_id' => 'sometimes|exists:categories,id',
            'fields' => 'sometimes|array',
            'fields.*.id' => 'nullable|integer',
            'fields.*.field_name' => 'required_with:fields|string',
            'fields.*.field_type' => 'required_with:fields|string',
            'fields.*.metadata' => 'nullable',
            'delete_fields' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $template->update($request->only(['template_name', 'category_id', 'description', 'status']));

            if (!empty($validated['delete_fields'])) {
                TemplateField::whereIn('id', $validated['delete_fields'])->delete();
            }

            // Update or create fields
            if (!empty($validated['fields'])) {
                foreach ($validated['fields'] as $index => $fieldData) {
                    // Use the Model's create method to trigger $casts for metadata
                    $data = [
                        'field_name' => $fieldData['field_name'],
                        'field_key' => TemplateField::generateFieldKey($fieldData['field_name']),
                        'field_type' => $fieldData['field_type'],
                        'is_required' => $fieldData['is_required'] ?? false,
                        'order_index' => $index,
                        'metadata' => $fieldData['metadata'] ?? null,
                    ];

                    if (isset($fieldData['id'])) {
                        TemplateField::where('id', $fieldData['id'])->update($data);
                    } else {
                        $template->fields()->create($data);
                    }
                }
            }

            DB::commit();
            return response()->json(['message' => 'Template updated', 'template' => $template->load('fields')]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Delete a resource template
    public function destroy($id): JsonResponse
    {
        $template = ResourceTemplate::findOrFail($id);
        $template->delete();
        return response()->json(['message' => 'Deleted']);
    }
}