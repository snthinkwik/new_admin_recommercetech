<?php

namespace App\Models;

use App\Csv\Parser;
use App\Models\Part;
use App\Models\RepairsItems;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\File\File;

class Repair extends Model
{
    use HasFactory;
    protected $table = "repairs";
    protected $fillable = ['repair_id', 'type', 'status', 'engineer', 'item_id'];

    protected $primaryKey = 'repair_id'; // should be id

    protected $dates = ['closed_at'];

    public function getIdAttribute()
    {
        return $this->repair_id; // as there's repair_id instead of id which is obviously wrong
    }

    public static function getAvailableTypeWithKeys() {
        return array_combine(self::getAvailableType(), self::getAvailableType());
    }

    public static function getAvailableType() {

        $types = DB::table('repair_type')->get();
        $typeList = [];

        foreach ($types as $type) {
            array_push($typeList, $type->type);
        }

        return $typeList;
    }

    public static function getAvailableStatusWithKeys() {
        return array_combine(self::getAvailableStatus(), self::getAvailableStatus());
    }

    public static function getAvailableStatus() {

        $statuses = DB::table('repair_status')->get();
        $statusList = [];

        foreach ($statuses as $status) {
            array_push($statusList, $status->status);
        }
        return $statusList;
    }

    public static function getRepairByItemId($itemId) {
        return \App\Repair::where('item_id', $itemId)->first();
    }

    public function stock() {
        return $this->belongsTo(Stock::class,'item_id', 'id');
    }

    public function Repairstatus() {
        return $this->belongsTo(RepairStatus::class,'status');
    }

    public function RepaireItemExternal(){
        return $this->hasMany(RepairsItems::class ,'repair_id','id')->where('type',RepairsItems::TYPE_EXTERNAL);
    }

    public function RepaireItemInternal(){
        return $this->hasMany(RepairsItems::class,'repair_id','id')->where('type',RepairsItems::TYPE_INTERNAL);
    }

    public function RepaireItem(){
        return $this->hasMany(RepairsItems::class,'repair_id','repair_id')->orderBy('id','desc');
    }



    public function Repairengineer() {
        return $this->belongsTo(RepairEngineer::class,'engineer');
    }

    public function repairType() {
        return $this->belongsTo(RepairType::class, 'type', 'id');
    }

    public function getPartsAttribute()
    {
        $repairStartDate = $this->created_at;
        $repairEndDate = null;
        if($repair = self::where('repair_id', '!=', $this->repair_id)->where('created_at', '>', $this->created_at)->where('item_id', $this->item_id)->orderBy('repair_id', 'asc')->first()) {
            $repairEndDate = $repair->created_at;
        }
        /*if($this->closed_at) {
            $repairEndDate = $this->closed_at;
        }*/
        $parts = [];
        $partsLong = []; // whole part names, not just type of a part
        $stockLogsParts = $repairEndDate ? $this->stock->stockLogs()->where('content', 'like', '%assigned parts%')->where('created_at', '>=', $repairStartDate)->where('created_at', '<', $repairEndDate)->get() : $this->stock->stockLogs()->where('content', 'like', '%assigned parts%')->where('created_at', '>=', $repairStartDate)->get();
        foreach($stockLogsParts as $stockLogsPart) {
            $pregReplace = preg_replace('#\sPart\sCost.*?\n#si', '', str_replace("Assigned Parts:\n", "", $stockLogsPart['content']));
            $partsLong[] = array_filter(explode(',', $pregReplace), function ($el) {
                return $el;
            });
        }
        $partsTypes = Part::whereIn('name', $partsLong)->get()->pluck('type', 'name')->toArray();

        foreach($partsLong as $partLong) {
            $partLong = is_array($partLong) ? $partLong[0] : $partLong;
            $parts[] = isset($partsTypes[$partLong]) ? $partsTypes[$partLong] : $partLong;
        }

        $parts = implode(", ", $parts);

        return $parts;
    }

    public static function parseValidateCsv(File $csv, $salesPriceRequired = false)
    {
        $csvParser = new Parser($csv->getRealPath(), [
            'headerFilter' => function ($columnName) {
                $columnName = strtolower($columnName);
                $columnName = preg_replace('/\W+/', '_', $columnName);
                return $columnName;
            },

        ]);

        $rows = $csvParser->getAllRows();
        $errors = [];


        return [$rows, $errors];
    }
}
