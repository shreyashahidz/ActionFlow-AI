<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $guarded = [];

    protected $casts = [
        'due_date' => 'date:Y-m-d',
    ];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }
}
