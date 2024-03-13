<?php

namespace App\Models;

use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Facades\Excel;

class Batch extends Model
{
    use HasFactory;

    const STATUS_SOLD = "Sold";
    const STATUS_FOR_SALE = "For Sale";

    protected $fillable = ['name', 'sale_price', 'description'];

    public function getXls($option = null)
    {
        $stockItems = Stock::orderBy('id', 'desc')
            ->where('batch_id', $this->id)
            ->get();
        $stock = [];
        if(in_array($option, ['batch', 'auction'])){
            foreach($stockItems as $item)
            {
                $stock[] = [
                    'Batch' => '#'.$item->batch_id,
                    'Ref' => $item->our_ref,
                    'Name' => $item->name,
                    'Capacity' => $item->capacity_formatted,
                    'Colour' => $item->colour,
                    'Condition' => $item->condition,
                    'Grade' => $item->grade,
                    'Network' => $item->network,
                    'Engineer Notes' => $item->notes
                ];
            }
            //Right Border Cell
            $rBorder = 'I';
        }
        elseif(in_array($option, ['batch_imeis'])) {
            foreach($stockItems as $item)
            {
                $stock[] = [
                    'Batch' => '#'.$item->batch_id,
                    'Ref' => $item->our_ref,
                    'Name' => $item->name,
                    'IMEI' => $item->imei,
                    'Capacity' => $item->capacity_formatted,
                    'Colour' => $item->colour,
                    'Condition' => $item->condition,
                    'Grade' => $item->grade,
                    'Network' => $item->network,
                    'Engineer Notes' => $item->notes
                ];
            }
            //Right Border Cell
            $rBorder = 'J';
        }
        else {
            foreach ($stockItems as $item) {
                $stock[] = [
                    'Batch' => '#' . $item->batch_id,
                    'Ref' => $item->our_ref,
                    'Name' => $item->name,
                    'Capacity' => $item->capacity_formatted,
                    'Colour' => $item->colour,
                    'Condition' => $item->condition,
                    'Grade' => $item->grade,
                    'Network' => $item->network,
                    'Engineer Notes' => $item->notes
                ];
            }
            $rBorder = 'I';
        }

        //Count+1 (row with keys)
        $count = count($stock)+1;
        if(!isset($email))
            $filename = 'Batch '.$this->id;
        else
            $filename = $email;
        $file = Excel::create($filename, function($excel) use($stock, $count, $rBorder) {
            $excel->setTitle('Batch');
            $excel->sheet('Batch',function($sheet) use($stock, $count, $rBorder) {
                $sheet->fromArray($stock);
                $sheet->setFontSize(10);
                // Left Border
                $sheet->cells('A1:A'.$count, function($cells){
                    $cells->setBorder('none','none','none','medium');
                });
                // Right Border
                $sheet->cells($rBorder.'1:'.$rBorder.$count, function($cells){
                    $cells->setBorder('none','medium','none','none');
                });
                // Top+Bottom border - first row
                $sheet->row(1, function($row){
                    $row->setBorder('medium','medium','medium','medium');
                    $row->setFontSize(11);
                });
                // Bottom border - last row
                $sheet->row($count, function($row){
                    $row->setBorder('none','medium','medium','medium');
                });
            });
        });

        return $file;
    }

    public function stock()
    {
        return $this->hasMany(Stock::class)->whereIn("status",[Stock::STATUS_BATCH]);
    }


    public function batch_offers()
    {
        return $this->hasMany(BatchOffer::class);
    }

    public function getPurchasePriceFormattedAttribute()
    {
        $price = $this->stock()->sum('purchase_price');
        return money_format($price);
    }

    public function getWantedPriceAttribute()
    {
        $price = 0;
        foreach ($this->stock as $item) {
            $price += $item->purchase_price * 100;
        }
        return round($price * 1.13 / 100);
    }

    public function sale()
    {
        return $this->hasOne(Sale::class);
    }

    public function getPhotoUrlAttribute()
    {
        return asset('/img/batches/' . $this->photo);
    }

    public function getFileUrlAttribute()
    {
        return asset('/files/batches/'.$this->file);
    }

    public function getTrgUkUrlAttribute()
    {
        return config('services.trg_uk.url')."/batches/batch-details/".$this->id;
    }

    public function getEndTimeFormattedAttribute()
    {
        return $this->end_time !== MYSQL_ZERO_DATE ? $this->end_time : "";
    }

    public function getDeletableAttribute()
    {
        if($this->status == self::STATUS_FOR_SALE) {
            return true;
        }
        return false;
    }

    public function getStatusAttribute()
    {
        $itemsCount = $this->stock()->count();
        if($this->stock()->where('status', Stock::STATUS_BATCH)->whereNull('sale_id')->count() == $itemsCount && !$this->sale) {
            return self::STATUS_FOR_SALE;
        } elseif($this->stock()->whereIn('status', [Stock::STATUS_PAID, Stock::STATUS_SOLD])->count() == $itemsCount) {
            return self::STATUS_SOLD;
        }
    }

    public function getSellableAttribute()
    {
        return $this->status == self::STATUS_FOR_SALE ? true : false;
    }

    public function getEndTimeCheckAttribute()
    {
        return $this->end_time !== MYSQL_ZERO_DATE && $this->end_time <= Carbon::now() ? false : true;
    }

    public static function getAvailableStatuses($keys = false)
    {
        $statuses = [self::STATUS_FOR_SALE, self::STATUS_SOLD];

        if($keys) $statuses = array_combine($statuses, $statuses);

        return $statuses;
    }

    public function getSendableAttribute()
    {
        if($this->stock()->count()) {
            return true;
        }

        return false;
    }

    public function scopeSold($query) {
        $query->has('stock');
        $query->whereDoesntHave('stock', function($q) {
            $q->whereNotIn('status', [Stock::STATUS_PAID, Stock::STATUS_SOLD]);
            //$q->whereNull('sale_id');
        });
    }

    public function scopeForSale($query) {
        $query->whereDoesntHave('stock', function($q) {
            $q->whereNotIn('status', [Stock::STATUS_BATCH]);
        })->doesntHave('sale');
    }
}
