<?php

namespace App\Models;

use App\Models\Email;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailDraft extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'user_id', 'subject', 'body', 'from_name', 'from_email', 'to', 'option', 'option_details'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFromFullAttribute()
    {
        return "$this->from_name <$this->from_email>";
    }

    public function getOptionFormattedAttribute()
    {
        $res = $this->option;
        if($this->option == Email::OPTION_COUNTRY)
            $res .= " (".$this->option_details.")";

        return $res;
    }
}
