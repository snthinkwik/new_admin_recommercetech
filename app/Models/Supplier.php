<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'address_1', 'address_2', 'town', 'county', 'postcode', 'email_address', 'contact_name', 'returns_email_address',
        'returns_form','crm_id','recomm_ps','supplier_ps'];

    public function getAddressLongAttribute()
    {
        $address = "";

        if($this->address_1) $address .= $this->address_1.", ";
        if($this->address_2) $address .= $this->address_2.", ";
        if($this->town) $address .= $this->town.", ";
        if($this->county) $address .= $this->county.", ";
        if($this->postcode) $address .= $this->postcode;

        return $address;
    }

    public static function getAvailableReturnForms()
    {
        return [
            'Default' => 'default.xlsx',
            'E-Giant' => 'e-giant.xlsx',
            'Money4Machines' => 'm4m.docx',
            'Music Magpie' => 'music-magpie.xlsx',
            'Mazuma' => 'mazuma.xlsx'
        ];
    }

    public function getReturnsFormFormattedAttribute()
    {
        return public_path()."/files/supplierReturns/".$this->returns_form;
    }
}
