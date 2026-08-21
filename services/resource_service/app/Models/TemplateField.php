<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateField extends Model
{
    // Define table name and fillable fields
    protected $table = 'template_fields';
    protected $fillable = [
        'template_id', 'field_name', 'field_key', 'field_type', 
        'is_required', 'order_index', 'placeholder', 'default_value', 'metadata'
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'metadata' => 'array',
    ];

    // Define relationship with ResourceTemplate model
    public function template(): BelongsTo
    {
        return $this->belongsTo(ResourceTemplate::class);
    }

    // Static method to generate field key from field name
    public static function generateFieldKey(string $fieldName): string
    {
        return strtolower(str_replace(' ', '_', trim($fieldName)));
    }
}