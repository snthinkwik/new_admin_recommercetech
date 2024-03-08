<?php

namespace App\Exports;

use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;


class InventoryExport implements FromArray,WithHeadings
{
    public function array(): array
    {


        $request=new Request();
        $finalStock = [];


        $stock = Stock:: fromRequest($request, 'overview')->join('products', 'products.id', '=', 'new_stock.product_id')
            ->whereNotNull('product_id')
            ->whereIn('status', ['In Stock',
                'Inbound',
                'Re-test',
                'Batch',
                'In Repair',
                '3rd Party',
                'Ready for Sale',
                'Retail Stock',
                'Listed on Auction',
                'Reserved for Order',
                'Allocated'
            ])
            ->where('products.archive', '0')
            ->orderBy('products.product_name', 'ASC')->get();


        $suffixes = [
            Stock::STATUS_IN_STOCK => 'in_stock',
            Stock::STATUS_RE_TEST => 're_test',
            Stock::STATUS_BATCH => 'batch',
            Stock::STATUS_REPAIR => 'in_repair',
            Stock::STATUS_3RD_PARTY => "3rd_party",
            Stock::STATUS_READY_FOR_SALE => "ready_for_sale",
            Stock::STATUS_RETAIL_STOCK => "retail_stock",
            Stock::STATUS_LISTED_ON_AUCTION => "listed_on_auction",
            Stock::STATUS_RESERVED_FOR_ORDER => "reserved_for_order",
            Stock::STATUS_ALLOCATED => 'allocated',
            Stock::STATUS_INBOUND => 'inbound',
        ];

        $found = DB::select('select found_rows() as found')[0]->found;

        //  $paginator = new LengthAwarePaginator($stock, $found, $perPage, null, ['path' => route('stock.overview')]);


        foreach ($stock as $item) {

            $total = 0;
            $totalPurchaseTotal = 0;
            $crackBack = 0;
            $touchId = 0;
            $unlocked = 0;
            $totalQuantity = 0;
            $testStatusInStock = 0;
            $testStatusInbound = 0;
            $testStatusRetest = 0;
            $testStatusBatch = 0;
            $testStatusInRepair = 0;
            $testStatusParty = 0;
            $testStatusReadyForSale = 0;
            $testStatusRetailStock = 0;
            $testStatusListedOnAuc = 0;
            $testStatusReserved = 0;
            $testStatusAllocated = 0;
            $testStatus = 0;
            $gradeA = 0;
            $gradeB = 0;
            $gradeC = 0;
            $gradeD = 0;
            $gradeE = 0;
            $totalTestedItems = 0;

            $skuStock = Stock::whereIn('product_id', [$item->product_id])->where('grade', $item->grade)->where('vat_type', $item->vat_type)->where('status', $item->status)->get();

            foreach ($skuStock as $sku) {
                $totalPurchaseTotal += $sku->total_cost_with_repair;
                if ($sku->cracked_back === "Yes") {
                    $crackBack++;
                }
                if ($sku->touch_id_working === "Yes") {
                    $touchId++;

                }

                if ($sku->condition === "A") {
                    $gradeA++;

                }
                if ($sku->condition === "B") {
                    $gradeB++;

                }
                if ($sku->condition === "C") {
                    $gradeC++;

                }
                if ($sku->condition === "D") {
                    $gradeD++;

                }
                if ($sku->condition === "E") {
                    $gradeE++;

                }
                if (!in_array($sku->network, ['Unlocked', ''])) {
                    $unlocked++;

                }

                if ($sku->test_status === "Complete" && $sku->status === "In Stock") {
                    $testStatusInStock++;

                }
                if ($sku->test_status === "Complete" && $sku->status === "Inbound") {
                    $testStatusInbound++;

                }

                if ($sku->test_status === "Complete" && $sku->status === "Re-test") {
                    $testStatusRetest++;
                }

                if ($sku->test_status === "Complete" && $sku->status === "Batch") {
                    $testStatusBatch++;
                }

                if ($sku->test_status === "Complete" && $sku->status === "In Repair") {
                    $testStatusInRepair++;
                }

                if ($sku->test_status === "Complete" && $sku->status === "3rd Party") {
                    $testStatusParty++;
                }

                if ($sku->test_status === "Complete" && $sku->status === "Ready for Sale") {
                    $testStatusReadyForSale++;
                }

                if ($sku->test_status === "Complete" && $sku->status === "Retail Stock") {
                    $testStatusRetailStock++;
                }
                if ($sku->test_status === "Complete" && $sku->status === "Listed on Auction") {
                    $testStatusListedOnAuc++;
                }

                if ($sku->test_status === "Complete" && $sku->status === "Reserved for Order") {
                    $testStatusReserved++;
                }

                if ($sku->test_status === "Complete" && $sku->status === "Allocated") {
                    $testStatusAllocated++;
                }

                if ($sku->test_status === "Complete") {
                    $testStatus++;

                }


                $totalTestedItems = $testStatusInStock + $testStatusInbound + $testStatusRetest + $testStatusBatch + $testStatusInRepair + $testStatusParty + $testStatusReadyForSale + $testStatusRetailStock + $testStatusListedOnAuc + $testStatusReserved + $testStatusAllocated;
                $totalQuantity += $sku->quantity;
            }


            foreach ($suffixes as $tt) {
                $total += $item['count_' . $tt];
            }


            $item['total'] = $total;
            $item['total_in_stock'] = $stock->count();
            $item['total_purchase_cost'] = $totalPurchaseTotal;
            $item['total_crack_back'] = $crackBack;
            $item['total_touch_id'] = $touchId;
            $item['total_locked'] = $unlocked;
            $item['total_quantity'] = $totalQuantity;
            $item['In_Stock'] = $testStatusInStock;
            $item['Inbound'] = $testStatusInbound;
            $item['Re_test'] = $testStatusRetest;
            $item['Batch'] = $testStatusBatch;
            $item['In_Repair'] = $testStatusInRepair;
            $item['Party'] = $testStatusParty;
            $item['Ready_for_Sale'] = $testStatusReadyForSale;
            $item['Retail_Stock'] = $testStatusRetailStock;
            $item['Listed_on_Auction'] = $testStatusListedOnAuc;
            $item['Reserved_for_Order'] = $testStatusReserved;
            $item['Allocated'] = $testStatusAllocated;

            $item['gradeA'] = $gradeA;
            $item['gradeB'] = $gradeB;
            $item['gradeC'] = $gradeC;
            $item['gradeD'] = $gradeD;
            $item['gradeE'] = $gradeE;
//            $totalQtyTotalLocked+=$unlocked;

        }


        foreach ($stock as $item) {

            $totalTested = 0;
            if ($item->status === Stock::STATUS_IN_STOCK) {
                $totalTested = $item->In_Stock;
            }
            if ($item->status === Stock::STATUS_INBOUND) {
                $totalTested = $item->Inbound;
            }
            if ($item->status === Stock::STATUS_RE_TEST) {
                $totalTested = $item->Re_test;
            }
            if ($item->status === Stock::STATUS_BATCH) {
                $totalTested = $item->Batch;
            }
            if ($item->status === Stock::STATUS_REPAIR) {
                $totalTested = $item->In_Repair;
            }
            if ($item->status === Stock::STATUS_3RD_PARTY) {
                $totalTested = $item->Party;
            }
            if ($item->status === Stock::STATUS_READY_FOR_SALE) {
                $totalTested = $item->Ready_for_Sale;
            }
            if ($item->status === Stock::STATUS_RETAIL_STOCK) {
                $totalTested = $item->Retail_Stock;
            }
            if ($item->status === Stock::STATUS_LISTED_ON_AUCTION) {
                $totalTested = $item->Listed_on_Auction;
            }
            if ($item->status === Stock::STATUS_RESERVED_FOR_ORDER) {
                $totalTested = $item->Reserved_for_Order;
            }
            if ($item->status === Stock::STATUS_ALLOCATED) {
                $totalTested = $item->Allocated;
            }

            $finalStock[] = [
                'Product Category' => isset($item->product->category) ? $item->product->category : '',
                'Make' => isset($item->product->make) ? $item->product->make : '',
                'Name' => isset($item->product->product_name) ? $item->product->product_name : '',
                'Product Id' => isset($item->product) ? $item->product->id : '',
                'Non Serialised' => $item->non_serialised ? 'Yes' : 'No',
                'Model' => isset($item->product) ? $item->product->model : '',
                'Manufacturers SKU(MPN)' => isset($item->product) ? $item->product->slug : '',
                'EAN' => isset($item->product) ? $item->product->ean : '',
                'Grade' => $item->grade,
                'Status' => $item->status,
                'Vat Type' => $item->vat_type,
                'Total Purchase Price' => $item->total_purchase_cost,
                'Total Qty in Stock' => $item->total,
                'Total Qty Tested' => $totalTested,
                'Inbound Qty' => $item->count_inbound,
                'Grade A' => $item->gradeA,
                'Grade B' => $item->gradeB,
                'Grade C' => $item->gradeC,
                'Grade D' => $item->gradeD,
                'Grade E' => $item->gradeE,
                'Cracked Back' => $item->total_crack_back,
                'No Touch/Face ID' => $item->total_touch_id,
                'Network Locked' => $item->total_locked,
                'Retail Comparison' => $item->product->retail_comparison ? 'Yes' : 'No',

            ];
        }


        return [
            $finalStock
        ];


    }
    public function headings(): array
    {

        return [
            'Product Category',
            'Make',
            'Name' ,
            'Product Id',
            'Non Serialised',
            'Model',
            'Manufacturers SKU(MPN)',
            'EAN',
            'Grade',
            'Status',
            'Vat Type',
            'Total Purchase Price',
            'Total Qty in Stock',
            'Total Qty Tested',
            'Inbound Qty',
            'Grade A',
            'Grade B',
            'Grade C',
            'Grade D',
            'Grade E',
            'Cracked Back',
            'No Touch/Face ID',
            'Network Locked',
            'Retail Comparison',
        ];



    }
}
