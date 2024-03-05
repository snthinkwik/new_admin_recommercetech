<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class EmailTracking extends Model
{
    use HasFactory;
    protected $fillable = ['user_id','type', 'to', 'to_name', 'from', 'from_name', 'subject', 'body'];

    protected $table = 'emails_tracking';

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function email_webhooks()
    {
        return $this->hasMany(EmailWebhook::class);
    }
}
