<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type'
    ];

    // helper to read JSON values
    public function getValueAttribute($v)
    {
        // try decode JSON
        $json = json_decode($v, true);
        return $json === null ? $v : $json;
    }

    // helper to set JSON values
    public function setValueAttribute($v)
    {
        if (is_array($v) || is_object($v)) {
            $this->attributes['value'] = json_encode($v);
        } else {
            $this->attributes['value'] = $v;
        }
    }
}
