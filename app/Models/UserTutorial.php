<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserTutorial extends Model
{
    protected $fillable = [
        'user_id',
        'tutorial_name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
