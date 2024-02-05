<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhoneCheck extends Model
{
    use HasFactory;
    const STATUS_NEW = "new";
    const STATUS_PROCESSING = "processing";
    const STATUS_DONE = "done";
    const STATUS_FAILED = "failed";

    protected $fillable = ['stock_id', 'imei', 'status', 'station_id', 'response', 'no_updates'];

    /*public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);
        // Database name prepended because of the bug in \Illuminate\Database\Eloquent::has()
        $this->table = DB::connection()->getDatabaseName() . '.' . 'phone_checks';
    }*/

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function getReportRenderAttribute()
    {
        try {
            $report = "";
            $response = json_decode($this->response);

            $failedFormatted = $response->Failed;
            if(strpos($failedFormatted, 'Headset Port') !== false && strpos($failedFormatted, 'Headset-Right') !== false) {
                $failedFormatted = str_replace(',Headset-Right', '', $failedFormatted);
            }
            if(strpos($failedFormatted, 'Headset Port') !== false && strpos($failedFormatted, 'Headset-Left') !== false) {
                $failedFormatted = str_replace(',Headset-Left', '', $failedFormatted);
            }

            if(strpos(strtolower($response->Model), 'iphone 7') !== false && strpos($failedFormatted, 'Front Microphone') !== false && strpos($failedFormatted, 'Microphone') !== false  && strpos($failedFormatted, 'Video Microphone') !== false) {
                $failedFormatted = str_replace(',Front Microphone', '', $failedFormatted);
                $failedFormatted = str_replace('Front Microphone', '', $failedFormatted);
                $failedFormatted = str_replace(',Microphone', '', $failedFormatted);
                $failedFormatted = str_replace(',Video Microphone', '', $failedFormatted);
                $failedFormatted = str_replace('Video Microphone', '', $failedFormatted);
                $failedFormatted = str_replace('Microphone', '', $failedFormatted);
                if(strlen($failedFormatted) > 0) {
                    $failedFormatted .= ",Sound IC Issue";
                } else {
                    $failedFormatted = "Sound IC Issue";
                }
            }

            $report .= "Created At: " . $response->DeviceCreatedDate . "<br/>";
            $report .= "Updated At: " . $response->DeviceUpdatedDate . "<br/>";
            $report .= "Battery Health: " . $response->BatteryHealthPercentage . "%<br/>";
            $report .= "Number Cycles: " . $response->BatteryCycle . "<br/>";
            $report .= "iOS Version: " . $response->Version . "<br/>";
            $report .= "Failed Tests: " . $failedFormatted. "<br/>";
            $report .= "Erasure Status: " . $response->Erased . "<br/>";
            $report .= "Working: " . $response->Working . "<br/>";
            $report .= "Model: " . $response->Model . "<br/>";
            $report .= "Memory: ". $response->Memory . "<br/>";

        } catch(\Exception $e) {
            $report = "ERROR";
        }
        return $report;
    }

    public function getCreatedDeviceDateAttribute(){
        $response = json_decode($this->response);

        return $response->DeviceCreatedDate;
    }

    public function getReportFailedRenderAttribute()
    {
        try {
            $report = "";
            $response = json_decode($this->response);

            $failedFormatted = $response->Failed;
            if(strpos($failedFormatted, 'Headset Port') !== false && strpos($failedFormatted, 'Headset-Right') !== false) {
                $failedFormatted = str_replace(',Headset-Right', '', $failedFormatted);
            }
            if(strpos($failedFormatted, 'Headset Port') !== false && strpos($failedFormatted, 'Headset-Left') !== false) {
                $failedFormatted = str_replace(',Headset-Left', '', $failedFormatted);
            }

            if(strpos(strtolower($response->Model), 'iphone 7') !== false && strpos($failedFormatted, 'Front Microphone') !== false && strpos($failedFormatted, 'Microphone') !== false  && strpos($failedFormatted, 'Video Microphone') !== false) {
                $failedFormatted = str_replace(',Front Microphone', '', $failedFormatted);
                $failedFormatted = str_replace('Front Microphone', '', $failedFormatted);
                $failedFormatted = str_replace(',Microphone', '', $failedFormatted);
                $failedFormatted = str_replace(',Video Microphone', '', $failedFormatted);
                $failedFormatted = str_replace('Video Microphone', '', $failedFormatted);
                $failedFormatted = str_replace('Microphone', '', $failedFormatted);
                if(strlen($failedFormatted) > 0) {
                    $failedFormatted .= ",Sound IC Issue";
                } else {
                    $failedFormatted = "Sound IC Issue";
                }
            }

            $report .=  $failedFormatted;

        } catch(\Exception $e) {
            $report = "ERROR";
        }
        return $report;
    }

    public function getResponseRenderAttribute()
    {
        $report = "";
        try {
            $response = json_decode($this->response);
            foreach($response as $key => $value) {
                $report .= "<b>$key</b>: ".(is_array($value) ? json_encode($value) : $value)."<br/>";
            }
        } catch(\Exception $e) {
            $report = $e;
        }

        return $report;
    }

    public function getTestedByAttribute()
    {
        try {
            $response = json_decode($this->response);

            $stationId = $response->StationID;

            if($user = User::where('type', 'admin')->where('station_id', $stationId)->first()) {
                return $user->first_name;
            }

            return $stationId;
        } catch(\Exception $e) {
            return '';
        }
    }

    public function getStationUserIdAttribute()
    {
        try {
            $response = json_decode($this->response);

            $stationId = $response->StationID;

            if($user = User::where('type', 'admin')->where('station_id', $stationId)->first()) {
                return $user->id;
            }

            return null;
        } catch(\Exception $e) {
            return null;
        }
    }

    public static function getAvailableStatationIds()
    {
        try {
            $stations = self::where('station_id', '!=', '')->groupBy('station_id')->select('station_id')->get()->lists('station_id');
            return array_combine($stations, $stations);
        } catch(\Exception $e) {
            return ['' => 'Error'];
        }
    }
}
