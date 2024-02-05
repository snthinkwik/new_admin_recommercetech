<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Network extends Model
{
    use HasFactory;
    protected $table = 'network';

    protected $fillable = ['pr_network', 'slug'];

    public $timestamps = false;

    public function scopeCustomOrder(Builder $query)
    {
        return $query->orderByRaw('if(length(slug), 0, 1), pr_network');
    }
}
