<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class News extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'news';

    protected $fillable = [
        'titulo',
        'sub_titulo',
        'ruta',
        'link_final',
        'fecha_hora',
        'user_id',
        'slug',
        'display',
        'time_slider',
        'show_title_top10'
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'display' => 'boolean',
        'time_slider' => 'integer',
        'show_title_top10' => 'boolean'
    ];

    // Relación con User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
