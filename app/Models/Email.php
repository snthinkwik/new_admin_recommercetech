<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Email extends Model
{
    use HasFactory;
    const STATUS_NEW = 'New';
    const STATUS_SENDING = 'Sending';
    const STATUS_SENT = 'Sent';
    const STATUS_ERROR = 'Error';

    const TO_REGISTERED = 'Registered';
    const TO_UNREGISTERED = 'Unregistered';
    const TO_EVERYONE = 'Everyone';

    const ATTACHMENT_NONE = 'None';
    const ATTACHMENT_FILE = 'File';
    const ATTACHMENT_BATCH = 'Batch';
    const ATTACHMENT_FILES = 'Files';

    const OPTION_BOUGHT_NOT_LAST_45_DAYS = "Bought not 45 days";
    const OPTION_NEVER_BOUGHT = "Never Bought";
    const OPTION_COUNTRY = "Country";
    const OPTION_NONE = "";
    const OPTION_PAID_NOT_DISPATCHED = "Paid not Dispatched";

    protected $fillable = ['subject', 'body', 'from_name', 'from_email', 'to', 'option', 'option_details', 'attachment', 'brand'];

    protected $casts = ['files' => 'array'];

    public function saveFile(UploadedFile $file)
    {
        if (!$this->exists) {
            throw new Exception("The email has to be saved before saving a file for it.");
        }

        $dir = storage_path('app/email-attachments');
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        $fileName = $this->id . '_' . $file->getClientOriginalName();
        $file->move($dir, $fileName);
        $this->file_name = $fileName;
        $this->save();
    }

    public function getFilePathAttribute()
    {
        return storage_path('app/email-attachments') . '/' . $this->file_name;
    }

    public function getFromFullAttribute()
    {
        return "$this->from_name <$this->from_email>";
    }

    public function getOptionFormattedAttribute()
    {
        $res = $this->option;
        if($this->option == self::OPTION_COUNTRY)
            $res .= " (".$this->option_details.")";

        return $res;
    }

    public static function getAvailableBrands()
    {
        return [];
    }

    public static function getAvailableBrandsWithKeys()
    {
        return array_combine(self::getAvailableBrands(), self::getAvailableBrands());
    }

    public static function getBodyHtml($body, $user = null)
    {
        $user = $user ?: Auth::user();
        if (!$user) {
            throw new Exception("User empty.");
        }

        $body = str_replace('%%FULL_NAME%%', $user->full_name, $body);
        $body = str_replace('%%FIRST_NAME%%', $user->first_name, $body);
        $body = str_replace('%%LAST_NAME%%', $user->last_name, $body);
        $body = str_replace('%%COMPANY_NAME%%', $user->company_name, $body);
        $body = str_replace('%%ADDRESS%%', $user->address ? nl2br($user->address->full) : '', $body);
        $body = str_replace('%%ADDRESS_LINE1%%', !empty($user->address->line1) ? $user->address->line1 : '', $body);
        $body = str_replace('%%ADDRESS_LINE2%%', !empty($user->address->line2) ? $user->address->line2 : '', $body);
        $body = str_replace('%%ADDRESS_CITY%%', !empty($user->address->city) ? $user->address->city : '', $body);
        $body = str_replace('%%ADDRESS_COUNTY%%', !empty($user->address->county) ? $user->address->county : '', $body);
        $body = str_replace('%%ADDRESS_POSTCODE%%', !empty($user->address->postcode) ? $user->address->postcode : '', $body);
        $body = str_replace('%%ADDRESS_COUNTRY%%', !empty($user->address->country) ? $user->address->country : '', $body);
        $body = str_replace('%%EMAIL%%', $user->email, $body);
        $body = str_replace('%USERHASH%', $user->hash, $body);
        $body = preg_replace('/<(p|ul)>/', '<$1 style="margin-bottom: 15px">', $body);

        return $body;
    }

    public static function getSubjectHtml($subject, $user = null)
    {
        $user = $user ?: Auth::user();
        if(!$user) {
            throw new Exception("User empty.");
        }

        $subject = str_replace('%%FULL_NAME%%', $user->full_name, $subject);
        $subject = str_replace('%%FIRST_NAME%%', $user->first_name, $subject);
        $subject = str_replace('%%LAST_NAME%%', $user->last_name, $subject);
        $subject = str_replace('%%COMPANY_NAME%%', $user->company_name, $subject);
        $subject = str_replace('%%ADDRESS%%', $user->address ? nl2br($user->address->full) : '', $subject);
        $subject = str_replace('%%ADDRESS_LINE1%%', !empty($user->address->line1) ? $user->address->line1 : '', $subject);
        $subject = str_replace('%%ADDRESS_LINE2%%', !empty($user->address->line2) ? $user->address->line2 : '', $subject);
        $subject = str_replace('%%ADDRESS_CITY%%', !empty($user->address->city) ? $user->address->city : '', $subject);
        $subject = str_replace('%%ADDRESS_COUNTY%%', !empty($user->address->county) ? $user->address->county : '', $subject);
        $subject = str_replace('%%ADDRESS_POSTCODE%%', !empty($user->address->postcode) ? $user->address->postcode : '', $subject);
        $subject = str_replace('%%ADDRESS_COUNTRY%%', !empty($user->address->country) ? $user->address->country : '', $subject);
        $subject = str_replace('%%EMAIL%%', $user->email, $subject);

        return $subject;
    }

    public static function getBrandLogo($brand)
    {
        $url = asset('img/brands/recommercetech.png');

        return $url;
    }

    public static function getBrandWebsite($brand)
    {
        $url = 'www.recomm.co.uk';

        return $url;
    }

    public function email_trackings()
    {
        return $this->hasMany(EmailTracking::class);
    }
}
