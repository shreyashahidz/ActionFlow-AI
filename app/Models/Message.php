<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $guarded = [];

    protected $casts = [
        'attachments' => 'array',
        'extra'       => 'array',
        'sent_at'     => 'datetime',
    ];

    public function analysis()
    {
        return $this->hasOne(MessageAnalysis::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
