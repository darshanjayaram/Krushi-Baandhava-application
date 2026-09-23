<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Locality extends Model
{
    use HasFactory;

    protected $fillable = [
        'taluk_id',
        'name',
        'name_kn',
        'pincode',
    ];

    public function taluk(): BelongsTo
    {
        return $this->belongsTo(Taluk::class);
    }
}
