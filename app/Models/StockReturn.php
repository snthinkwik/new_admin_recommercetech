<?php

namespace App\Models;

use App\StockReturnLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class StockReturn extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'admin_user_id', 'valid_to_date', 'return_period'];

    protected $dates = ['valid_to_date'];

    public function user()
    {
        return $this->belongsTo('App\User', 'user_id', 'id');
    }

    public function admin_user()
    {
        return $this->belongsTo('App\User', 'admin_user_id', 'id');
    }

    public function stock_return_items()
    {
        return $this->hasMany('App\StockReturnItem', 'stock_return_id', 'id');
    }

    public function stock_return_logs()
    {
        return $this->hasMany('App\StockReturnLog', 'stock_return_id', 'id');
    }

    public function setValidToDateAttribute($value) {
        if (strlen($value) === 16) { // Date and time without seconds.
            $value .= ':00';
        }

        $this->attributes['valid_to_date'] = $value ? $this->fromDateTime($value) : '';
    }

    public function getRecommUrlAttribute()
    {
        return config('services.trg_uk.url')."/returns/".$this->id."/start";
    }

    public function save(array $options = array())
    {
        $changes = '';
        $exists = $this->exists;
        foreach ($this->attributes as $key => $value)
        {
            if (!array_key_exists($key, $this->original))
            {
                $changes .= "Added value \"$value\" for field \"$key\".\n";
            }
            elseif ($value !== $this->original[$key] && !checkUpdatedFields($value, $this->original[$key]))
            {
                $changes .= "Changed value of field \"$key\" from \"{$this->original[$key]}\" to \"$value\".\n";
            }
        }

        if ($changes) {
            $user = Auth::user();
            if ($user) {
                $changes .= "User ID: \"$user->id\" (name \"$user->full_name\").\n";
            }

            if (!empty($GLOBALS['argv'])) {
                $changes .= "Cron: \"" . implode(' ', $GLOBALS['argv']) . "\"";
            }
        }

        $res =  parent::save($options);

        if($changes) {
            StockReturnLog::create([
                'user_id' => $user ? $user->id: null,
                'stock_return_id' => $this->id,
                'content' => ($exists ? "Updated: \n" : "Created: \n").$changes
            ]);
        }
    }
}
