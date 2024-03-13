<?php

namespace App\Http\Controllers;

use App\Commands\ebayFees\ImportEbayFees;
use App\Models\EbayFees;
use App\Models\EbayFeesHistory;
use App\Models\EbayFeesLog;
use App\Models\EbayOrders;
use App\Models\ManualEbayFeeAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EbayFeesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request) {

        $ebayFees = EbayFees::fromRequest($request)
            ->with('EbayOrders')
            ->where(function($query) use ($request) {
                if (!$request->ajax() && !$request->matched) {
                    $query->where('matched', EbayFees::MATCHED_NO);
                }
            })
            ->orderBy('formatted_fee_date', 'DESC')
            ->paginate(config('app.pagination'))->appends(array_filter(array_reverse($request->all())));


        if ($request->ajax()) {
            return response()->json([
                'itemsHtml' => View::make('ebay-fee.list', compact('ebayFees'))->render(),
                'paginationHtml' => '' . $ebayFees->render(),
                'sort' => $request->sort,
                'sortO' => $request->sortO,
            ]);
        }

        $GetLastRecordDate = DB::table('ebay_fees')
            ->orderBy('formatted_fee_date', 'desc')
            ->first();

        $count = DB::table('ebay_fees')
            ->select('id', DB::raw('COUNT(CASE WHEN `matched`= "' . EbayFees::MATCHED_MANUALLY_ASSIGNED . '" OR matched = "' . EbayFees::MATCHED_YES . '" OR `matched`= "' . EbayFees::MATCHED_NA . '" THEN 1 END) as total_matched'), DB::raw('COUNT(CASE WHEN `matched`= "' . EbayFees::MATCHED_NO . '" THEN 1 END) as total_unmatched')
            )->get();


        return view('ebay-fee.index', compact('ebayFees', 'count', 'GetLastRecordDate'));
    }

    public function eBayFeesHistory() {
        $ebayFeesHistory = EbayFeesHistory::paginate(config('app.pagination'));
        return view('ebay-fee.history', compact('ebayFeesHistory'));
    }

    public function getUnmatchedExport() {
        $ebayFee = EbayFees::where('matched', 'No')->get();

        $unMatched = [];
        foreach ($ebayFee as $item) {
            $unMatched[] = [
                'Title' => $item->title,
                'Date' => $item->date,
                'Item Number' => $item->item_number,
                'Fee Type' => $item->fee_type,
                'Amount' => $item->amount,
                'eBay Discount' => $item->received_top_rated_discount,
                'eBay Username' => $item->ebay_username
            ];
        }

        $rBorder = "F";
        $filename = "Unmatched Fees";
        $count = count($unMatched) + 1;
        $file = \Maatwebsite\Excel\Facades\Excel::create($filename, function ($excel) use ($unMatched, $count, $rBorder) {
            $excel->setTitle('Items');
            $excel->sheet('Items', function ($sheet) use ($unMatched, $count, $rBorder) {
                $sheet->fromArray($unMatched);
                $sheet->setFontSize(10);
                // Left Border
                $sheet->cells('A1:A' . $count, function ($cells) {
                    $cells->setBorder('none', 'none', 'none', 'none');
                });
                // Right Border
                $sheet->cells($rBorder . '1:' . $rBorder . $count, function ($cells) {
                    $cells->setBorder('none', 'none', 'none', 'none');
                });
                // Top+Bottom border - first row
                $sheet->row(1, function ($row) {
                    $row->setBorder('none', 'none', 'none', 'none');
                    $row->setFontSize(11);
                });
                // Bottom border - last row
                $sheet->row($count, function ($row) {
                    $row->setBorder('none', 'none', 'none', 'none');
                });
            });
        });

        $file->download('xls');
        return back();
    }

    public function exportCSVManualEbayFeeAssignment() {

        $ebayFee = ManualEbayFeeAssignment::where('owner', \App\EbayOrderItems::RECOMM)->get();

        $manualAssignmentList = [];
        foreach ($ebayFee as $item) {
            $manualAssignmentList[] = [
                'Fee Record No' => $item->fee_record_no,
                'Fee Title' => $item->fee_title,
                'Date' => $item->date,
                'Item Number' => $item->item_number,
                'Fee Type' => $item->fee_type,
                'Amount' => $item->amount,
                'Owner' => $item->owner,
            ];
        }

        $rBorder = "F";
        $filename = "eBayFee Manually Assignment";
        $count = count($manualAssignmentList) + 1;
        $file = \Maatwebsite\Excel\Facades\Excel::create($filename, function ($excel) use ($manualAssignmentList, $count, $rBorder) {
            $excel->setTitle('Items');
            $excel->sheet('Items', function ($sheet) use ($manualAssignmentList, $count, $rBorder) {
                $sheet->fromArray($manualAssignmentList);
                $sheet->setFontSize(10);
                // Left Border
                $sheet->cells('A1:A' . $count, function ($cells) {
                    $cells->setBorder('none', 'none', 'none', 'none');
                });
                // Right Border
                $sheet->cells($rBorder . '1:' . $rBorder . $count, function ($cells) {
                    $cells->setBorder('none', 'none', 'none', 'none');
                });
                // Top+Bottom border - first row
                $sheet->row(1, function ($row) {
                    $row->setBorder('none', 'none', 'none', 'none');
                    $row->setFontSize(11);
                });
                // Bottom border - last row
                $sheet->row($count, function ($row) {
                    $row->setBorder('none', 'none', 'none', 'none');
                });
            });
        });

        $file->download('xls');
        return back();
    }

    public function addInManualEbayFeeAssignment(Request $request) {

        $EbayOrdersFees = EbayFees::whereIn('id', $request->ids)->get();

        foreach ($EbayOrdersFees as $eBayFee) {
            $manualEbayFeeAssignmentModel = ManualEbayFeeAssignment::where("fee_record_no", $eBayFee->id)->first();
            if (is_null($manualEbayFeeAssignmentModel)) {
                $manualEbayFeeAssignmentModel = new ManualEbayFeeAssignment();
            }

            $manualEbayFeeAssignmentModel->fee_record_no = $eBayFee->id;
            $manualEbayFeeAssignmentModel->fee_title = $eBayFee->title;
            $manualEbayFeeAssignmentModel->date = $eBayFee->formatted_fee_date;
            $manualEbayFeeAssignmentModel->item_number = $eBayFee->item_number;
            $manualEbayFeeAssignmentModel->fee_type = $eBayFee->fee_type;
            $manualEbayFeeAssignmentModel->amount = $eBayFee->amount;
            $manualEbayFeeAssignmentModel->owner = $request->owner;
            $manualEbayFeeAssignmentModel->save();

            $eBayFee->matched = EbayFees::MATCHED_MANUALLY_ASSIGNED;

            $ChangeEbayFees = '';
            if ($eBayFee->isDirty()) {
                foreach ($eBayFee->getAttributes() as $key => $value) {
                    if ($value !== $eBayFee->getOriginal($key) && !checkUpdatedFields($value, $eBayFee->getOriginal($key))) {
                        $orgVal = $eBayFee->getOriginal($key);
                        $ChangeEbayFees .= "Changed \"$key\" from \"$orgVal\" to \"$value\".\n";
                    }
                }
            }

            $eBayFee->save();

            if (!empty($ChangeEbayFees)) {
                $eBayFeeLog = new EbayFeesLog();
                $eBayFeeLog->fees_id = $eBayFee->id;
                $eBayFeeLog->content = $ChangeEbayFees;
                $eBayFeeLog->save();
            }
        }
    }

    public function postImport(Request $request) {
        ini_set('max_execution_time', 120);

        $userEmail = Auth::user()->email;
        $userName = Auth::user()->fullname;

        $request->file('ebay-fee');
        $this->validate($request, [
            'ebay-fee' => 'required|mimes:csv,txt',
        ]);

        $fileName = $request->file('ebay-fee')->getClientOriginalName();

        $path = $request->file('ebay-fee')->getRealPath();
        config(['excel.import.startRow' => 8]);
        $data = Excel::load($path, function ($reader) {

        }, 'ISO-8859-1')->get();

        Queue::pushOn('ebay_fee', new ImportEbayFees($data, $userEmail, $userName, $fileName));
        return back()->with('messages.success', 'Fees are now importing. You will receive an email once complete.');
    }

    public function getTemplate() {
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="eBay-fee.csv"');
        readfile(public_path() . '/files/ebay-fee.csv');
        die;
    }

    public function updateEbayFeeUsername() {
        artisan_call_background('ebay:update-ebay-fee-matched-username');
        return redirect()->route('ebay-fee.index')->with('messages.success', 'Match eBay Fees Cron Job has now started');
    }

    public function edit($id, Request $request) {

        $eBayFee = EbayFees::with('ManualEbayFeeAssignment')->find($id);

        if ($request->isMethod('put')) {
            $this->validate($request, [
                'title' => 'required',
                'item_number' => 'required',
                'fee_type' => 'required'
            ]);

            if ($request->sales_record_number) {
                $request["matched"] = "Yes";
            }

            $eBayFee->fill($request->all());

            $ChangeEbayFees = '';
            if ($eBayFee->isDirty()) {
                foreach ($eBayFee->getAttributes() as $key => $value) {
                    if ($value !== $eBayFee->getOriginal($key) && !checkUpdatedFields($value, $eBayFee->getOriginal($key))) {
                        $orgVal = $eBayFee->getOriginal($key);
                        $ChangeEbayFees .= "Changed \"$key\" from \"$orgVal\" to \"$value\".\n";
                    }
                }
            }

            $eBayFee->save();

            if ($request->manually_assign) {
                $manualFessAssignment = ManualEbayFeeAssignment::where("fee_record_no", $id)->first();
                $manualFessAssignment->owner = $request->manually_assign;
                $manualFessAssignment->save();
            }

            if (!empty($ChangeEbayFees)) {
                $eBayFeeLog = new EbayFeesLog();
                $eBayFeeLog->fees_id = $eBayFee->id;
                $eBayFeeLog->content = $ChangeEbayFees;
                $eBayFeeLog->save();
            }

            return back()->with('messages.success', 'eBay Fee has been successfully updated.');
        }

        $sale_record_number = EbayOrders::all()->lists('sales_record_number');
        $salesRecordNumberList = array_combine($sale_record_number, $sale_record_number);

        $fee_type = array_map('current', \App\EbayFees::select('fee_type')
            ->groupBy('fee_type')
            ->get()
            ->toArray());

        $feeTypeList = array_combine($fee_type, $fee_type);
        $ebayFeesLogs = EbayFeesLog::where('fees_id', $id)->paginate(config('app.pagination'));
        return view('ebay-fee.update', compact('eBayFee', 'salesRecordNumberList', 'feeTypeList', 'ebayFeesLogs'));
    }
}
