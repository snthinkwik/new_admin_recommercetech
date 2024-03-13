<?php

namespace App\Jobs\Repairs;

use App\Models\Repair;
use App\Models\RepairsItems;
use App\Models\Stock;
use App\Models\StockLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExternalImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public $repairs_id;
    public $user_id;
    public $data;
    public function __construct($data,$repairsId,$userId)
    {
        $this->data=$data;
        $this->repairs_id=$repairsId;
        $this->user_id=$userId;

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->data as $item){

            $value='';

            if(strpos($item['rct_ref'], "RCT") !== false){
                $value= preg_replace('/[^0-9.]+/', '', $item['rct_ref']);

            }

            $query = Stock::orderBy('created_at', 'desc');


            if($value!==''){
                $query->where('id',$value);
            }else if($item['imei']!==""){
                $query->where('imei',$item['imei']);
            }else if($item['serial']!==""){
                $query->where('serial',$item['serial']);
            }

            $stock=$query->first();
            $content='';
            if(isset($stock->id)){
                $repaireItem=RepairsItems::firstOrNew([
                    'stock_id'=>$stock->id,
                    'repair_id'=>$this->repairs_id
                ]);
                $repaireItem->repaired_faults=$item['repaired_faults'];
                $repaireItem->original_faults=$stock->phone_check ? $stock->phone_check->report_failed_render:'';
                $repaireItem->type=RepairsItems::TYPE_EXTERNAL;
                $repaireItem->status=RepairsItems::STATUS_OPEN;
                $repaireItem->estimate_repair_cost=$item['estimate_repair_cost'];
                $repaireItem->actual_repair_cost=$item['actual_repair_cost'];
                $repaireItem->stock_status=$stock->status;
                $repaireItem->save();

                $stockItems=Stock::find($stock->id);
                $stockItems->status=Stock::STATUS_REPAIR;




                $vatCalculation=calculationOfProfit($stockItems->sale_price,$stockItems->total_cost_with_repair,$stockItems->vat_type,$stockItems->purchase_price);

                $stockItems->profit=$vatCalculation['profit'];
                $stockItems->true_profit=$vatCalculation['true_profit'];
                $stockItems->marg_vat=$vatCalculation['marg_vat'];
                $stockItems->sale_vat=$vatCalculation['sale_vat'];
                $stockItems->total_price_ex_vat=$vatCalculation['total_price_ex_vat'];

                $stockItems->save();






                $repair=Repair::find($this->repairs_id);


                $content .= "Repair Id: " . $this->repairs_id . "<br/>";
                $content .= "Type: " . RepairsItems::TYPE_EXTERNAL . "<br/>";
                $content .= "Engineer: " .$repair->Repairengineer->name . "<br/>";
                $content .= "<span class='text-danger'>Original Faults: " . $repaireItem->original_faults . "</span><br/>";
                $content .= "Estimate Cost: " .   $repaireItem->estimate_repair_cost . "<br/>";
                $content .= "<span class='text-success'>Status: " . RepairsItems::STATUS_OPEN . "</span><br/>";


                StockLog::create([
                    'user_id' => $this->user_id,
                    'stock_id'=>$stock->id,
                    'content' => $content,
                ]);





            }




        }
    }
}
