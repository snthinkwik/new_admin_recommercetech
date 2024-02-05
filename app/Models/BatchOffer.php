<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class BatchOffer extends Model
{
    use HasFactory;
    protected $fillable = ['batch_id', 'user_id', 'offer'];

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getOfferFormattedAttribute()
    {
      //  return money_format(config('app.money_format'), $this->offer);
        return $this->offer;
    }

}
