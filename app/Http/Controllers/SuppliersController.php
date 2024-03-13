<?php

namespace App\Http\Controllers;

use App\Commands\SupplierReturns\ReturnEmail;
use App\Models\Stock;
use App\Models\StockLog;
use App\Models\Supplier;
use App\Models\SupplierReturn;
use App\Models\SupplierReturnItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\TemplateProcessor;

class SuppliersController extends Controller
{
    public function getIndex(Request $request)
    {
        $suppliersQuery = Supplier::orderBy('id', 'desc');

        if($request->term) {
            $suppliersQuery->where(function($q) use($request) {
                $q->where('name', 'like', "%$request->term%");
                $q->orWhere('contact_name', 'like', "%$request->term%");
                $q->orWhere('email_address', 'like', "%$request->term%");
                $q->orWhere('returns_email_address', 'like', "%$request->term%");
            });
        }

        $suppliers = $suppliersQuery->paginate(config('app.pagination'));

        if($request->ajax()) {
            return response()->json([
                'itemsHtml' => View('suppliers.list', compact('suppliers'))->render(),
                'paginationHtml' => $suppliers->appends($request->all())->render()
            ]);
        }

        return view('suppliers.index', compact('suppliers'));
    }

    public function postAdd(Request $request)
    {
        $supplier = new Supplier();
        $supplier->fill($request->all());
        $supplier->save();

        return back()->with('messages.success', 'Supplier has been created');
    }

    public function getSingle($id)
    {
        $supplier = Supplier::findOrFail($id);

        return view('suppliers.single', compact('supplier'));
    }

    public function postUpdate(Request $request)
    {
        $supplier = Supplier::findOrFail($request->id);
        $supplier->fill($request->all());
        $supplier->save();

        return back()->with('messages.success', 'Updated');
    }

    public function getSupplierReturns(Request $request)
    {
        $supplierReturnsQuery = SupplierReturn::orderBy('id', 'desc');

        if($request->status){
            $supplierReturnsQuery->where('status', $request->status);
        }else{
            $supplierReturnsQuery->whereIn('status', [SupplierReturn::STATUS_PENDING_APPROVAL,SupplierReturn::STATUS_OPEN,SupplierReturn::STATUS_RETURNED]);
        }


        if($request->supplier_id)
            $supplierReturnsQuery->where('supplier_id', $request->supplier_id);

        $supplierReturns = $supplierReturnsQuery->paginate(config('app.pagination'));

        if($request->ajax()) {
            return response()->json([
                'itemsHtml' => View('suppliers.returns-list', compact('supplierReturns'))->render(),
                'paginationHtml' => $supplierReturns->appends($request->all())->render()
            ]);
        }

        return view('suppliers.returns', compact('supplierReturns'));
    }

    public function getRedirect(Request $request)
    {
        $ids = [];

        foreach (Auth::user()->basket as $item) {
            $ids[$item->id] = true;
        }

        foreach ($request->ids ?: [] as $id) {
            $ids[$id] = true;
        }

        if (!$ids) {
            return response()->json([
                'status' => 'error',
                'message' => "You didn't select anything",
            ]);
        }
        else {
            return response()->json([
                'status' => 'success',
                'url' => route('suppliers.return-create', ['ids' => array_keys($ids)]),
            ]);
        }
    }

    public function getSupplierReturnCreate(Request $request)
    {
        $items = Stock::whereIn('id', $request->ids)->get();
        $itemsCount = count($items);
        if($items->where('status', Stock::STATUS_IN_STOCK)->count() !== $itemsCount) {
            return back()->with('messages.error', 'All Selected Items needs to be In Stock');
        }

        foreach($items->groupBy('supplier_id') as $supplierId => $items) {
            if(!$supplierId) {
                continue;
            }
            $supplier = Supplier::findOrFail($supplierId);

            $return = new SupplierReturn();
            $return->status = SupplierReturn::STATUS_OPEN;
            $return->supplier_id = $supplier->id;
            $return->save();

            foreach($items as $item) {
                $returnItem = new SupplierReturnItem();
                $returnItem->supplier_return_id = $return->id;
                $returnItem->stock_id = $item->id;
                $returnItem->save();

                $item->shown_to = Stock::SHOWN_TO_NONE;
                $item->status = Stock::STATUS_RETURNED_TO_SUPPLIER;
                $item->save();

                StockLog::create([
                    'stock_id' => $item->id,
                    'user_id' => Auth::user()->id,
                    'content' => Auth::user()->first_name . " created a return for this device - <a href=".route('suppliers.return-single', ['id' => $return->id]).">#$return->id</a>"
                ]);
            }

            Auth::user()->basket()->sync([]);
        }

        return redirect()->route('suppliers.returns');
    }

    public function getSupplierReturnSingle($id)
    {

        $supplierReturn = SupplierReturn::findOrFail($id);

        return view('suppliers.returns-single', compact('supplierReturn'));
    }

    public function postSupplierReturnUpdate(Request $request)
    {
        $message = "Status has been updated.";

        $supplierReturn = SupplierReturn::findOrFail($request->id);
        $supplierReturn->status = $request->status;

        if($supplierReturn->status ==  SupplierReturn::STATUS_RETURNED && $supplierReturn->getOriginal('status') != SupplierReturn::STATUS_RETURNED && $request->notify) {
            Queue::pushOn('emails', new ReturnEmail($supplierReturn));
            $message .= " Email will be sent";
        }

        $supplierReturn->save();

        return back()->with('messages.success', $message);
    }

    public function postSupplierReturnRemoveItem(Request $request)
    {
        $supplierReturnItem = SupplierReturnItem::findOrFail($request->id);
        $supplierReturnItem->stock->returnToStock();
        $supplierReturnItem->delete();

        return back()->with('messages.success', 'Item was returned to Stock');
    }

    public function postSupplierReturnUpdateItem(Request $request)
    {
        $supplierReturnItem = SupplierReturnItem::findOrFail($request->id);
        if(!$request->action || $request->action == 'item') {
            $supplierReturnItem->reason = $request->reason;
            $supplierReturnItem->save();
            $message = 'Item Reason was updated';
        } elseif($request->action == 'items') {
            $items = SupplierReturnItem::where('supplier_return_id', $supplierReturnItem->supplier_return_id)->update(['reason' => $request->reason]);
            $message = "Items ($items) reason was updated";
        }

        return back()->with('messages.success', $message);
    }

    public function postSupplierReturnUpdateTrackingCourier(Request $request)
    {
        $supplierReturn = SupplierReturn::findOrFail($request->id);
        $supplierReturn->tracking_number = $request->tracking_number;
        $supplierReturn->courier = $request->courier;
        $supplierReturn->note=$request->note;
        $supplierReturn->save();

        return back()->with('messages.success', 'Tracking Details have been updated.');
    }

    public function getSupplierReturnSingleExport($id)
    {
        $supplierReturn = SupplierReturn::findOrFail($id);

        if(!$supplierReturn->return_template) {
            return back()->with('messages.error', 'Template not found');
        }


        $inputFileName = $supplierReturn->supplier->returns_form_formatted;

        if($supplierReturn->supplier->returns_form == 'mazuma.xlsx') {

            $outputFileName = $supplierReturn->supplier->name.' RMA.xlsx';
            /*
             * B3: Date d/m/y
             * items starts with row 8:
             * A8: make
             * B8: model
             * C8: network
             * D8: imei/serial
             * E8: stock id -> third party ref
             * F8: invoice no. (purchase order number)
             * G8: invoice date (purchase date: d.m.Y)
             * H8: reason
             * */
            $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
            $objReader = PHPExcel_IOFactory::createReader($inputFileType);
            $objPHPExcel = $objReader->load($inputFileName);

            $objPHPExcel->setActiveSheetIndex(0);

            $objPHPExcel->getActiveSheet()
                ->setCellValue('B3', Carbon::now()->format('d/m/y'))
            ;
            $row = 7;
            foreach($supplierReturn->items as $item) {
                $row++;
                $objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, $item->stock->make ? : ($item->stock->product ? $item->stock->product->productMake->title : (strpos(strtolower($item->stock->name), 'ipad') !== false ? 'Apple' : '')))->getStyle('A'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, $item->stock->name)->getStyle('B'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('C'.$row, $item->stock->network)->getStyle('C'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('D'.$row, $item->stock->imei ? : $item->stock->serial)->getStyle('D'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('E'.$row, $item->stock->third_party_ref)->getStyle('E'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('F'.$row, $item->stock->purchase_order_number)->getStyle('F'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('G'.$row, $item->stock->purchase_date ? $item->stock->purchase_date->format('d.m.Y') : '')->getStyle('G'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('H'.$row, $item->reason)->getStyle('H'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            }

            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            header('Content-Disposition: attachment;filename="' . $outputFileName . '"');
            header('Cache-Control: max-age=0');

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            $objWriter->save('php://output');
            die;

        } elseif($supplierReturn->supplier->returns_form == 'music-magpie.xlsx') {
            $outputFileName = $supplierReturn->supplier->name.' RMA.xlsx';
            /*
             * B2: Date Y-m-d
             * B3: Total Credit
             * items starts with row 6:
             * A6: invoice (purchase order number)
             * B6: purchase price
             * C6: make
             * D6: model
             * E6: t-code - third party ref
             * F6: imei
             * G6: reason
             * */
            $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
            $objReader = PHPExcel_IOFactory::createReader($inputFileType);
            $objPHPExcel = $objReader->load($inputFileName);

            $objPHPExcel->setActiveSheetIndex(0);

            $objPHPExcel->getActiveSheet()
                ->setCellValue('B2', Carbon::now()->format('Y-m-d'))
                ->setCellValue('B3', $supplierReturn->total_purchase_value_formatted)
            ;
            $row = 5;
            foreach($supplierReturn->items as $item) {
                $row++;
                $objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, $item->stock->purchase_order_number)->getStyle('A'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, $item->stock->purchase_price_formatted)->getStyle('B'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('C'.$row, $item->stock->make ? : ($item->stock->product ? $item->stock->product->productMake->title : (strpos(strtolower($item->stock->name), 'ipad') !== false ? 'Apple' : '')))->getStyle('C'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('D'.$row, $item->stock->name)->getStyle('D'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('E'.$row, $item->stock->third_party_ref)->getStyle('E'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('F'.$row, "'".($item->stock->imei ? : $item->stock->serial)."'")->getStyle('F'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('G'.$row, $item->reason)->getStyle('G'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            }

            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            header('Content-Disposition: attachment;filename="' . $outputFileName . '"');
            header('Cache-Control: max-age=0');

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            $objWriter->save('php://output');
            die;
        } elseif($supplierReturn->supplier->returns_form == 'm4m.docx') {
            $outputFileName = $supplierReturn->supplier->name.' RMA.docx';
            $phpWord = new TemplateProcessor($inputFileName);
            $phpWord->setValue('${date}', Carbon::now()->format('d/m/y'));

            $itemsCount = $supplierReturn->items()->count();
            $phpWord->cloneRow('modelLine', $itemsCount);

            $row=1;
            foreach($supplierReturn->items as $item) {
                $phpWord->setValue('${modelLine#'.$row.'}', $item->stock->name);
                $phpWord->setValue('${imeiLine#'.$row.'}', $item->stock->imei ? : $item->stock->serial);
                $phpWord->setValue('${reasonLine#'.$row.'}', $item->reason);
                $row++;
            }
            header("Content-Disposition: attachment; filename=$outputFileName.docx");
            header( "Content-Type: application/vnd.openxmlformats-officedocument.wordprocessing‌​ml.document" );
            $phpWord->saveAs('php://output');
            die;
        } elseif($supplierReturn->supplier->returns_form == 'e-giant.xlsx') {
            $outputFileName = $supplierReturn->supplier->name.' RMA.xlsx';
            /*
             * E12: Date Y-m-d
             *
             * items starts with row 16:
             * B16: make model
             * C16: imei/serial
             * D16: grade
             * E16: reason for return
             * F16: invoice (purchase order number)
             * G16: purchase price
             * */
            $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
            $objReader = PHPExcel_IOFactory::createReader($inputFileType);
            $objReader->setIncludeCharts(TRUE);
            $objPHPExcel = $objReader->load($inputFileName);

            $objPHPExcel->setActiveSheetIndex(0);

            $objPHPExcel->getActiveSheet()
                ->setCellValue('C12', 'RCT')
                ->setCellValue('D12', 'RCT')
                ->setCellValue('E12', Carbon::now()->format('Y-m-d'))
                ->setCellValue('F12', $supplierReturn->id)
            ;
            $row = 15;
            foreach($supplierReturn->items as $item) {
                $row++;
                $make = $item->stock->make ? : ($item->stock->product ? $item->stock->product->productMake->title : (strpos(strtolower($item->stock->name), 'ipad') !== false ? 'Apple' : ''));
                $makeModel = $make." ".$item->stock->name;
                $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, $makeModel)->getStyle('E'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('C'.$row, "'".($item->stock->imei ? : $item->stock->serial)."'")->getStyle('C'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('D'.$row, $item->stock->grade)->getStyle('D'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('E'.$row, $item->reason)->getStyle('E'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('F'.$row, $item->stock->purchase_order_number)->getStyle('F'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('G'.$row, $item->stock->purchase_price_formatted)->getStyle('G'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            }

            $styleArray = array(
                'borders' => array(
                    'left' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM),
                )
            );
            $objPHPExcel->getActiveSheet()->getStyle("B16:B$row")->applyFromArray($styleArray);

            $styleArray = array(
                'borders' => array(
                    'allborders' => array('style' => PHPExcel_Style_Border::BORDER_THIN),
                )
            );
            $objPHPExcel->getActiveSheet()->getStyle("C16:F$row")->applyFromArray($styleArray);

            $styleArray = array(
                'borders' => array(
                    'right' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM),
                )
            );
            $objPHPExcel->getActiveSheet()->getStyle("G16:G$row")->applyFromArray($styleArray);

            $row++;
            // summary row
            $objPHPExcel->getActiveSheet()->SetCellValue('G'.$row, $supplierReturn->total_purchase_value_formatted)->getStyle('G'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

            $styleArray = array(
                'fill' => array(
                    'type' => PHPExcel_Style_Fill::FILL_SOLID,
                    'startcolor' => array('rgb' => 'c0c0c0')
                ),
                'borders' => array(
                    'left' => array(
                        'style' => PHPExcel_Style_Border::BORDER_MEDIUM
                    ),
                    'right' => array(
                        'style' => PHPExcel_Style_Border::BORDER_MEDIUM
                    ),
                    'bottom' => array(
                        'style' => PHPExcel_Style_Border::BORDER_MEDIUM
                    ),
                    'top' => array(
                        'style' => PHPExcel_Style_Border::BORDER_MEDIUM
                    ),
                    'inside' => array(
                        'style' => PHPExcel_Style_Border::BORDER_THIN
                    )
                )
            );
            $objPHPExcel->getActiveSheet()->getStyle("B$row:G$row")->applyFromArray($styleArray);


            // row+2
            $row+=2;
            $text = "Before returning any items you must inform Egiant Returns department via email - returns@e-giant.co.uk within 7 days of purchase";
            $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, $text)->getStyle('B'.$row)->getFont()->setBold(false);

            // row+2
            $row+=2;
            $text = "All boxes must be filled in before an RMA number is given. Once this sheet has been completed, the returns department will issue you an RMA Number.";
            $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, $text)->getStyle('B'.$row)->getFont()->setBold(false);
            $row+=1;
            $text = "Once this has been issued, please return the stock to us at the above address with the RMA form.";
            $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, $text)->getStyle('B'.$row)->getFont()->setBold(false);

            $row+=2;
            $text = "Returns can only be accepted if returned within 7 days of purchase";
            $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, $text)->getStyle('B'.$row)->getFont()->setBold(true);
            $row+=1;
            $text = "Blacklist returns will be accepted back within 30 days of purchase";
            $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, $text)->getStyle('B'.$row)->getFont()->setBold(true);

            $row+=2;
            $text = "Returns will be processed within 3 - 5 Working days";
            $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, $text)->getStyle('B'.$row)->getFont()->setBold(true);
            $row+=1;
            $text = "Accepted items will be passed for credit. Credit on account is raised within 14 days and can only be used against stock.";
            $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, $text)->getStyle('B'.$row)->getFont()->setBold(true);
            $row+=1;
            $text = "Rejected items will be sent back to you in your next order.";
            $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, $text)->getStyle('B'.$row)->getFont()->setBold(true);

            $row+=2;
            $text = "Returns will not be accepted without an RMA Number.";
            $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, $text)->getStyle('B'.$row)->getFont()->setBold(true);


            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            header('Content-Disposition: attachment;filename="' . $outputFileName . '"');
            header('Cache-Control: max-age=0');

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            $objWriter->setIncludeCharts(TRUE);
            $objWriter->save('php://output');
            die;
        } elseif($supplierReturn->supplier->returns_form == 'default.xlsx') {
            $outputFileName = $supplierReturn->supplier->name.' RMA.xlsx';
            /*
             * C10: Supplier Name
             * C12:	Returns Address
             * items starts in row 14:
             * A14: purchase date
             * B14: supplier ref -> third party red
             * C14: imei/serial
             * D14: model
             * E14:	reason
             * F14: purchase price
             * */
            $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
            $objReader = PHPExcel_IOFactory::createReader($inputFileType);
            $objPHPExcel = $objReader->load($inputFileName);

            $objPHPExcel->setActiveSheetIndex(0);

            $objPHPExcel->getActiveSheet()
                ->setCellValue('C10', $supplierReturn->supplier->name)
                ->setCellValue('C12', $supplierReturn->supplier->address_long);
            ;
            $row = 14;
            foreach($supplierReturn->items as $item) {
                $row++;
                $objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, $item->stock->purchase_date ? $item->stock->purchase_date->format('d.m.Y') : '')->getStyle('G'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, $item->stock->third_party_ref)->getStyle('E'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('C'.$row, $item->stock->imei ? : $item->stock->serial)->getStyle('D'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('D'.$row, $item->stock->name)->getStyle('B'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('E'.$row, $item->reason)->getStyle('H'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('F'.$row, $item->stock->purchase_price_formatted)->getStyle('F'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            }

            header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            header('Content-Disposition: attachment;filename="' . $outputFileName . '"');
            header('Cache-Control: max-age=0');

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            $objWriter->save('php://output');
            die;
        }

        return back();
    }

    public function getSupplierReturnSingleExportRMA($id)
    {

        $number = $id;
        $supplierReturn = SupplierReturn::findOrFail($id);
        $stock=[];
        foreach ($supplierReturn->items as $item) {
            $supplierCondition='';
            if(!is_null($item->stock)){
                $supplierCondition+=$item->stock->original_condition.
                    $supplierCondition+='(RCM '. getSupplierMappingGrade($item->stock->supplier_id,$item->stock->original_condition).')';


                if (!$item->stock->condition && Auth::user() && Auth::user()->type === 'user'){
                    $condition= "A to C";
                }else{
                    $condition=$item->stock->condition;
                }


                $stock[] = [
                    'RCT'=>"RCT".$item->stock->id,
                    '3rd Party Ref'=>$item->stock->third_party_ref,
                    'Brand'=>$item->stock->make,
                    'Name' => $item->stock->name,
                    'IMEI' => $item->stock->imei ? : $item->stock->serial,
                    'Purchase Price' => $item->stock->purchase_price_formatted,
                    'Supplier Grade'=>$item->stock->original_grade,
                    'Recomm Grade'=>$item->stock->grade,
                    'Supplier Condition'=>$supplierCondition,
                    'Recomm Condition'=>$condition,
                    'Reason' => $item->reason
                ];
            }

        }


        $rBorder = "N";
        $filename = "Supplier-return-RMA-$number";
        $count = count($stock) +1;
        $file = Excel::create($filename, function($excel) use($stock, $count, $rBorder) {
            $excel->setTitle('Items');
            $excel->sheet('Items',function($sheet) use($stock, $count, $rBorder) {
                $sheet->fromArray($stock);

            });
        });

        $file->download('xls');
        return back();

    }

    public function removeSupplier($id){

        $supplier=Supplier::find($id);

        if(!is_null($supplier)){

            $supplier->delete();

            return back()->with('messages.success','successfully deleted');


        }else{
            abort(404);
        }
    }

    public function updateGradeMapping(Request $request){

        $mapping=[
            'g1'=>[
                's'=>trim($request->s_1),
                'r'=>trim($request->g_1)
            ],
            'g2'=>[
                's'=>trim($request->s_2),
                'r'=>trim($request->g_2)
            ],
            'g3'=>[
                's'=>trim($request->s_3),
                'r'=>trim($request->g_3)
            ],
            'g4'=>[
                's'=>trim($request->s_4),
                'r'=>trim($request->g_4)
            ],
            'g5'=>[
                's'=>trim($request->s_5),
                'r'=>trim($request->g_5)
            ],
            'g6'=>[
                's'=>trim($request->s_6),
                'r'=>trim($request->g_6)
            ],
        ];
        $suppliers=Supplier::find($request->supplier_id);


        if(is_null($suppliers)){
            abort(404);
        }

        $suppliers->grade_mapping=json_encode($mapping);

        $suppliers->save();

        return back()->with('messages.success','successfully updated');



    }


    public function updatePSModelPercentage(Request $request){


        $suppliers=Supplier::find($request->supplier_id);
        $suppliers->recomm_ps=$request->recomm_ps;
        $suppliers->supplier_ps=$request->supplier_ps;
        $suppliers->save();
        return back()->with('messages.success','successfully updated');


    }

}
