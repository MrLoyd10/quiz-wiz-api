<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SharedQuiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'shared_with_user_id',
        'shared_by_user_id',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function sharedByUser()
    {
        return $this->belongsTo(User::class, 'shared_by_user_id');
    }

    public function sharedWithUser()
    {
        return $this->belongsTo(User::class, 'shared_with_user_id');
    }
}
