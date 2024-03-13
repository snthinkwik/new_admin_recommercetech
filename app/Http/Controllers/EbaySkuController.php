<?php

namespace App\Http\Controllers;

use App\Models\EbayOrderItems;
use App\Models\EbaySku;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class EbaySkuController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request) {

        $query = EbaySku::query();

        if(!in_array(Auth::user()->email, $this->getAllOrdersEmails())) {
            return back()->with('messages.error', 'Access Forbidden');
        }

        if ($request->sku) {
            $query->where('sku', 'like', "%$request->sku%");
        }

        $totalNumberSKU = $query->count();

        $ebayAll = $query->orderBy("id", "DESC")->paginate(config('app.pagination'));

        if ($request->ajax()) {
            return response()->json([
                'itemsHtml' => View::make('ebay-sku.list', compact('ebayAll'))->render(),
                'paginationHtml' => '' . $ebayAll->appends($request->all())->render()
            ]);
        }

        $ownerCount = EbayOrderItems::select('id', DB::raw('COUNT(CASE WHEN `owner`= "' . EbayOrderItems::TRG . '" THEN 1 END) as total_trg'), DB::raw('COUNT(CASE WHEN `owner`= "' . EbayOrderItems::CMT . '" THEN 1 END) as total_cmt'), DB::raw('COUNT(CASE WHEN `owner`= "' . EbayOrderItems::LCDBUYBACK . '" THEN 1 END) as total_lcdbuyback'), DB::raw('COUNT(CASE WHEN `owner`= "' . EbayOrderItems::CMN . '" THEN 1 END) as total_cmn'), DB::raw('COUNT(CASE WHEN `owner`= "' . EbayOrderItems::REFURBSTORE . '" THEN 1 END) as total_refurbstore'), DB::raw('COUNT(CASE WHEN `owner`= "' . EbayOrderItems::UNKNOWN . '" THEN 1 END) as total_awaiting_unknown'), DB::raw('COUNT(CASE WHEN `owner`= "' . EbayOrderItems::LCDBUYBACK . '" THEN 1 END) as total_lcd_buyback'), DB::raw('COUNT(CASE WHEN `owner`= "' . EbayOrderItems::RECOMM . '" THEN 1 END) as total_recomm'), DB::raw('COUNT(CASE WHEN `owner`= "' . EbayOrderItems::UNKNOWN . '" THEN 1 END) as total_unknown'), DB::raw('COUNT(CASE WHEN `owner`= "" THEN 1 END) as total_unassigned'))->get();

        return view('ebay-sku.index', compact('ebayAll', 'ownerCount', 'totalNumberSKU'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function postSave(Request $request) {

        $ebaySkuModel = new EbaySku();

        $rules = [
            'sku' => 'required|unique:new_ebay_skus',
            'owner' => 'required'
        ];

        $validator = $this->validate($request, $rules);

        if (!is_null($request->id)) {
            $ebaySkuModel = EbaySku::find($request->id);
        }
        $ebaySkuModel->sku = $request->sku;
        $ebaySkuModel->owner = $request->owner;
        $ebaySkuModel->location = $request->location;
        $ebaySkuModel->shipping_method = $request->shippingMethod;
        $ebaySkuModel->save();

        if (!is_null($request->id)) {
            return redirect(route('ebay.sku.index'))->with('messages.success', ' SKUs update successfully');
        }
        return back()->with('messages.success', 'Add new SKUs successfully');
    }

    public function getExport() {

        $ebaySku = EbaySku::all();
        $ebaySkuList = [];
        foreach ($ebaySku as $item) {

            $ebaySkuList[] = [
                'SKU' => $item->sku,
                'Owner' => $item->owner,
                'Location' => $item->location,
            ];
        }

        $rBorder = "C";
        $filename = "ebay-sku";
        $count = count($ebaySkuList) + 1;
        $file = Excel::create($filename, function($excel) use($ebaySkuList, $count, $rBorder) {
            $excel->setTitle('Items');
            $excel->sheet('Items', function($sheet) use($ebaySkuList, $count, $rBorder) {
                $sheet->fromArray($ebaySkuList);
                $sheet->setFontSize(10);
                // Left Border
                $sheet->cells('A1:A' . $count, function($cells) {
                    $cells->setBorder('none', 'none', 'none', 'medium');
                });
                // Right Border
                $sheet->cells($rBorder . '1:' . $rBorder . $count, function($cells) {
                    $cells->setBorder('none', 'medium', 'none', 'none');
                });
                // Top+Bottom border - first row
                $sheet->row(1, function($row) {
                    $row->setBorder('medium', 'medium', 'medium', 'medium');
                    $row->setFontSize(11);
                });
                // Bottom border - last row
                $sheet->row($count, function($row) {
                    $row->setBorder('none', 'medium', 'medium', 'medium');
                });
            });
        });

        $file->download('xls');
        return back();
    }

    public function updateShippingMethod(Request $request) {
        if (!is_null($request->item_id)) {
            $ebaySkus = EbaySku::find($request->item_id);
            $ebaySkus->shipping_method = $request->shipping_method;
            $ebaySkus->save();
        }
    }

    public function Unassigned(Request $request) {

        $ebayOrderItems = EbayOrderItems::fromRequest($request)
            ->where("owner", "")
            ->orderBy('id', 'desc')
            ->paginate(config('app.pagination'))
            ->appends(array_filter(array_reverse($request->all())));

        if ($request->ajax()) {
            return response()->json([
                'itemsHtml' => View::make('ebay-sku.unassigned_list', compact('ebayOrderItems'))->render(),
                'paginationHtml' => '' . $ebayOrderItems->render(),
                'sort' => $request->sort,
                'sortO' => $request->sortO,
            ]);
        }
        return view('ebay-sku.unassigned_index', compact('ebayOrderItems'));
    }

    public function updateOwner(Request $request) {

        $ebayOrderItem = new EbayOrderItems();
        $EbayOrdersItems = $ebayOrderItem::whereIn('id', $request->ids)->get();

        if ($EbayOrdersItems->count() > 0) {
            foreach ($EbayOrdersItems as $Item) {
                $Item->owner = $request->owner;
                $Item->sale_type = EbayOrderItems::SALE_TYPE_BUY_IT_NOW;

                $ChangeOwner = '';
                if ($Item->isDirty()) {
                    foreach ($Item->getAttributes() as $key => $value) {
                        if ($value !== $Item->getOriginal($key) && !checkUpdatedFields($value, $Item->getOriginal($key))) {
                            $orgVal = $Item->getOriginal($key);
                            $ChangeOwner .= "Changed \"$key\" from \"$orgVal\" to \"$value\".\n";
                        }
                    }
                }

                $Item->save();

                if (!empty($ChangeOwner)) {
                    $ebayOrdersLogModel = new \App\EbayOrderLog();
                    $ebayOrdersLogModel->orders_id = $Item->order_id;
                    $ebayOrdersLogModel->content = $ChangeOwner;
                    $ebayOrdersLogModel->save();
                }
            }
        }
    }

    public function addLocation(Request $request) {

        $ebaySkus = EbaySku::find($request->id);
        $oldLocation = $ebaySkus->location;
        $ebaySkus->location = $request->location;
        $ebaySkus->save();

        $message = "Add";
        if (!is_null($oldLocation)) {
            $message = "Update";
        }

        return back()->with('messages.success', $message . '  Location successfully');
    }

    public function postImport(Request $request) {
        $csv = $request->file('ebay-sku');

        if (empty($csv)) {
            return back()->withInput()->with('messages.error', "Please upload the CSV file.");
        }

        if ($csv->getClientOriginalExtension() != 'csv') {
            return back()->with('messages.error', 'Invalid file extension');
        }

        list($rows, $errors) = EbaySku::parseValidateCsv($csv);

        if ($errors) {
            return back()->withInput()->with('ebay.csv_errors', $errors);
        }

        $i = 0;
        foreach ($rows as $row) {
            $i++;
            $extSku = EbaySku::where('sku', $row['sku'])->count();

            if ($extSku > 0) {
                $ebaySkuModel = EbaySku::where('sku', $row['sku'])->first();
            } else {
                $ebaySkuModel = new EbaySku();
            }

            $ebaySkuModel->sku = $row['sku'];
            $ebaySkuModel->owner = $row['owner'];
            $ebaySkuModel->location = $row['location'];
            $ebaySkuModel->save();
        }

        return back()->with('messages.success', $i . ' new eBay SKU has imported');
    }

    public function getTemplate() {
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="eBay-sku.csv"');
        readfile(public_path() . '/files/ebay-sku.csv');
        die;
    }

    public function updateOwnerCron(Request $request) {
        artisan_call_background('ebay-orders:assign-ebay-sku');
        return back()->with('messages.success', 'Assign Owner Cron Job has now started');
    }

    public function ExportUnassignedSku() {

        ini_set('max_execution_time', 120);

        $fields = [
            'Order Number' => 'order_number',
            'Item Name' => 'item_name',
            'Custom Label' => 'item_sku',
            'Sales Price' => 'individual_item_price'
        ];

        $csvPath = tempnam('/tmp', 'ebay-unassigned-sku-');
        unlink($csvPath);
        $csvPath .= '.csv';
        $fh = fopen($csvPath, 'w');
        fputcsv($fh, array_keys($fields));

        EbayOrderItems::with("order")
            ->where("owner", "")
            ->orderBy('id', 'desc')
            ->chunk(500, function ($OrderItem) use ($fields, $fh) {
                foreach ($OrderItem as $item) {
                    $row = array_map(function ($field) use ($item) {
                        if ($field == "order_number")
                            return $item->order->$field;

                        return $item->$field;
                    }, $fields);
                    fputcsv($fh, $row);
                }
            });

        fclose($fh);
        convert_file_encoding($csvPath);
        header('Content-length: ' . filesize($csvPath));
        header('Content-Disposition: attachment; filename="eBay Unassigned SKU.csv"');
        header('Content-type: application/vnd.ms-excel');
        readfile($csvPath);
        die;
    }

    protected function getAllOrdersEmails()
    {
        return ['sam@recomm.co.uk'];//, 'radoslaw.kowalczyk@netblink.net'];
    }
}
