<?php

namespace App\Models;

use App\Models\Part;
use App\Models\Stock;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairsItems extends Model
{
    use HasFactory;
    const TYPE_INTERNAL="Internal";
    const TYPE_EXTERNAL="External";
    const STATUS_OPEN='Open';
    const STATUS_CLOSE='Close';



    protected $table='repairs_items';
    protected $fillable=[
        'repair_id',
        'stock_id',
        'repaired_faults',
        'created_at',
        'closed_at',
        'no_days',
        'total_repair_cost',
        'estimate_repair_cost',
        'actual_repair_cost',


    ];


    public function stock()
    {
        return $this->hasOne(Stock::class,'id','stock_id')
            ->whereNotIn('status', [Stock::STATUS_SOLD,Stock::STATUS_PAID,Stock::STATUS_DELETED,Stock::STATUS_LOST]);
    }

    public function repair()
    {
        return $this->belongsTo(Repair::class,'repair_id','repair_id');
    }

    public function getPartsAttribute()
    {

        //  dd($this->stock_id);

        $repairStartDate = $this->created_at;
        $repairEndDate = null;
        if($repair = self::where('repair_id', '!=', $this->repair_id)->where('created_at', '>', $this->created_at)->where('stock_id', $this->stock_id)->orderBy('id', 'asc')->first()) {

            $repairEndDate = $repair->created_at;
        }

        /*if($this->closed_at) {
            $repairEndDate = $this->closed_at;
        }*/
        $parts = [];
        $partsLong = []; // whole part names, not just type of a part

        $stockLogsParts=[];
        if(!is_null($this->stock)){
            $stockLogsParts = $repairEndDate ? $this->stock->stockLogs()->where('content', 'like', '%assigned parts%')->where('created_at', '>=', $repairStartDate)->where('created_at', '<', $repairEndDate)->get() : $this->stock->stockLogs()->where('content', 'like', '%assigned parts%')->where('created_at', '>=', $repairStartDate)->get();
        }


        if(count($stockLogsParts)>0){
            foreach($stockLogsParts as $stockLogsPart) {
                $pregReplace = preg_replace('#\sPart\sCost.*?\n#si', '', str_replace("Assigned Parts:\n", "", $stockLogsPart['content']));
                $partsLong[] = array_filter(explode(',', $pregReplace), function ($el) {
                    return $el;
                });
            }
        }

        $partsTypes = Part::whereIn('name', $partsLong)->get()->pluck('type', 'name')->toArray();

        foreach($partsLong as $partLong) {
            $partLong = is_array($partLong) ? $partLong[0] : $partLong;
            $parts[] = isset($partsTypes[$partLong]) ? $partsTypes[$partLong] : $partLong;
        }

        $parts = implode(", ", $parts);

        return $parts;
    }


    public static function getTypes()
    {
        return [self::TYPE_INTERNAL, self::TYPE_EXTERNAL];
    }

    public static function getTypesWithKeys()
    {
        return array_combine(self::getTypes(), self::getTypes());
    }

    public static function getStatus()
    {
        return [self::STATUS_OPEN, self::STATUS_CLOSE];
    }

    public static function getStatusWithKeys()
    {
        return array_combine(self::getStatus(), self::getStatus());
    }
}
