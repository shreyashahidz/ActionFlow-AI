<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageAnalysis extends Model
{
    protected $guarded = [];

    protected $casts = [
        'requires_response' => 'boolean',
        'raw_response'      => 'array',
    ];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }
}
