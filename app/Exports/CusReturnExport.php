<?php

namespace App\Exports;

use App\Models\CustomerReturn;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;




class CusReturnExport implements FromArray,WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
//    public function collection()
//    {
//
//        return new Collection([
//            [1, 2, 3],
//            [4, 5, 6]
//        ]);
//
////        $customerReturn = CustomerReturn::with(['sales','customerReturnsItems'])
////            ->select('*', DB::raw("SUM(total_sales_value_ex_vat) as total_sales"),
////                DB::raw("SUM(total_purchase_cost_of_return_ex_vat) as total_purchase"))
////            ->groupBy('sales_id')->orderBy('id', 'DESC');
////
////        $customerReturn = $customerReturn->get();
////
////                foreach ($customerReturn as $item) {
////                        $nameList=[];
////                        foreach ($item->customerReturnsItems as $returnItems){
////                            array_push($nameList,str_replace( array('@rt'), 'GB', $returnItems->name));
////                        }
////
////
////                    return new Co([
////                        'Id' => $item->id,
////                        'Date of Issue' => date('d/m/y', strtotime($item->date_of_issue)) ,
////                        'Recomm Order Id' => $item->sales_id,
////                        'Customer Name' => $item->customer_name,
////                        'Buyers Ref' => $item->buyers_ref,
////                        'Sold on Platform' => $item->sold_on_platform,
////                        'Product' =>   implode(', ', $nameList),
////                        'Supplier'=>!is_null($item->customerReturnsItems[0]['stock'])?$item->customerReturnsItems[0]->stock->supplier_name:'',
////                        'Reason For The Return' => $item->reason_for_the_return,
////                        'Date Of Sale'=>!is_null($item->sales)? $item->sales->created_at->format('d/m/y'):'-',
////                        'Total Sales Value' => money_format($item->total_sales),
////                        'Total Purchase Cost of Return ExVat' => money_format($item->total_purchase),
////                        'Returns Tracking Ref' => $item->tracking_ref,
////                        'Date Return Received' => $item->date_return_received!=='-' ? date('d/m/y', strtotime($item->date_return_received)):'-' ,
////                        'Date Credited' => $item->date_credited,
////                        'QB Credit Note Ref' => $item->qb_credit_note_ref,
////                        'Return Status'=> $item->return_status,
////                        'Note'=>$item->notes
////
////                    ]);
////
////        }
//
//
//
//
//
//
//    }

    public function array(): array
    {

                $customerReturn = CustomerReturn::with(['sales','customerReturnsItems'])
            ->select('*', DB::raw("SUM(total_sales_value_ex_vat) as total_sales"),
                DB::raw("SUM(total_purchase_cost_of_return_ex_vat) as total_purchase"))
            ->groupBy('sales_id')->orderBy('id', 'DESC');

        $customerReturn = $customerReturn->get();

        $stock=[];
                foreach ($customerReturn as $item) {
            $nameList=[];
            foreach ($item->customerReturnsItems as $returnItems){
                array_push($nameList,str_replace( array('@rt'), 'GB', $returnItems->name));
            }


            $stock[] = [
                'Id' => $item->id,
                'Date of Issue' => date('d/m/y', strtotime($item->date_of_issue)) ,
                'Recomm Order Id' => $item->sales_id,
                'Customer Name' => $item->customer_name,
                'Buyers Ref' => $item->buyers_ref,
                'Sold on Platform' => $item->sold_on_platform,
                'Product' =>   implode(', ', $nameList),
                'Supplier'=>!is_null($item->customerReturnsItems[0]['stock'])?$item->customerReturnsItems[0]->stock->supplier_name:'',
                'Reason For The Return' => $item->reason_for_the_return,
                'Date Of Sale'=>!is_null($item->sales)? $item->sales->created_at->format('d/m/y'):'-',
                'Total Sales Value' => money_format($item->total_sales),
                'Total Purchase Cost of Return ExVat' => money_format($item->total_purchase),
                'Returns Tracking Ref' => $item->tracking_ref,
                'Date Return Received' => $item->date_return_received!=='-' ? date('d/m/y', strtotime($item->date_return_received)):'-' ,
                'Date Credited' => $item->date_credited,
                'QB Credit Note Ref' => $item->qb_credit_note_ref,
                'Return Status'=> $item->return_status,
                'Note'=>$item->notes
            ];
        }





        return [
            $stock
        ];
    }
    public function headings(): array
    {
        return [
            'Id',
            'Date of Issue',
            'Recomm Order Id',
            'Customer Name',
            'Buyers Ref',
            'Sold on Platform',
            'Product',
            'Supplier',
            'Reason For The Return',
            'Date Of Sale' ,
            'Total Sales Value',
            'Total Purchase Cost of Return ExVat',
            'Returns Tracking Ref',
            'Date Return Received',
            'Date Credited',
            'QB Credit Note Ref',
            'Return Status',
            'Note',
        ];
    }
}
