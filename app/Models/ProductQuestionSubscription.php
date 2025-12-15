<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductQuestionSubscription extends Model
{
    protected $fillable = [
        'product_question_id',
        'email',
        'user_id',
    ];

    public function productQuestion()
    {
        return $this->belongsTo(ProductQuestion::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
