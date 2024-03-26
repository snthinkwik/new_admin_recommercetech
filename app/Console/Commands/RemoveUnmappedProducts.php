<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Console\Command;

class RemoveUnmappedProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:remove-unmapped-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $products=Product::all();


        $i=0;
        foreach ( $products as $product){

            $stock=Stock::where('product_id',$product->id)->first();

            if(is_null($stock)){
                $i++;
                $this->info("Product Id:-".$product->id.' Is Deleted');
                $deleteProduct=Product::find($product->id);
                $deleteProduct->delete();
            }


        }




        $this->info("Total ".$i . " UnMapped Products Remove ");





    }
}
