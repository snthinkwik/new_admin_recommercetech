<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PhpOffice\PhpWord\TemplateProcessor;

class SupplierReturn extends Model
{
    use HasFactory;
    const STATUS_OPEN = "Open";
    const STATUS_RETURNED = "Returned";
    const STATUS_CLOSED_CREDITED = "Closed/Credited";
    const STATUS_PENDING_APPROVAL= "Pending/Approval";

    protected $fillable = ['supplier_id', 'status','note'];

    public function items()
    {
        return $this->hasMany(SupplierReturnItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function getTotalPurchaseValueAttribute()
    {
        $value = 0;

        foreach($this->items as $item) {

            if(isset($item->stock->purchase_price)){
                $value += $item->stock->purchase_price;
            }

        }

        return $value;
    }

    public function getTotalPurchaseValueFormattedAttribute()
    {
        return money_format($this->total_purchase_value);
    }

    public static function getAvailableStatuses()
    {
        return [self::STATUS_OPEN, self::STATUS_RETURNED, self::STATUS_CLOSED_CREDITED,self::STATUS_PENDING_APPROVAL];
    }

    public static function getAvailableStatusesWithKeys()
    {
        return array_combine(self::getAvailableStatuses(), self::getAvailableStatuses());
    }

    public function getReturnTemplateAttribute()
    {
        if($this->supplier->name == 'Music Magpie') {
            return public_path().'/files/supplierReturns/magpie.xlsx';
        } elseif($this->supplier->name == 'Mazuma') {
            return public_path().'/files/supplierReturns/mazuma.xlsx';
        } elseif($this->supplier->name == 'Money4Machines') {
            return public_path().'/files/supplierReturns/m4m.docx';
        } elseif($this->supplier->name == 'E-Giant Ltd') {
            return public_path().'/files/supplierReturns/e-giant.xlsx';
        }

        return null;
    }

    public function getAttachment()
    {
        $inputFileName = $this->supplier->returns_form_formatted;

        $path = 'public/files/tmpSend/';

        if($this->supplier->returns_form == 'mazuma.xlsx') {

            $outputFileName = $this->supplier->name.' RMA.xlsx';
            /*
             * B3: Date d/m/y
             * items starts with row 8:
             * A8: make
             * B8: model
             * C8: network
             * D8: imei/serial
             * E8: stock id -> third_party_ref
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
            foreach($this->items as $item) {
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

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            $objWriter->save($path.$outputFileName);
            return $path.$outputFileName;

        } elseif($this->supplier->returns_form == 'music-magpie.xlsx') {
            $outputFileName = $this->supplier->name.' RMA.xlsx';
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
                ->setCellValue('B3', $this->total_purchase_value_formatted)
            ;
            $row = 5;
            foreach($this->items as $item) {
                $row++;
                $objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, $item->stock->purchase_order_number)->getStyle('A'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, $item->stock->purchase_price_formatted)->getStyle('B'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('C'.$row, $item->stock->make ? : ($item->stock->product ? $item->stock->product->productMake->title : (strpos(strtolower($item->stock->name), 'ipad') !== false ? 'Apple' : '')))->getStyle('C'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('D'.$row, $item->stock->name)->getStyle('D'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('E'.$row, $item->stock->third_party_ref)->getStyle('E'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('F'.$row, "'".($item->stock->imei ? : $item->stock->serial)."'")->getStyle('F'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('G'.$row, $item->reason)->getStyle('G'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            }

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            $objWriter->save($path.$outputFileName);

            return $path.$outputFileName;

        } elseif($this->supplier->returns_form == 'm4m.docx') {
            $outputFileName = $this->supplier->name.' RMA.docx';
            $phpWord = new TemplateProcessor($inputFileName);
            $phpWord->setValue('${date}', Carbon::now()->format('d/m/y'));

            $itemsCount = $this->items()->count();
            $phpWord->cloneRow('modelLine', $itemsCount);

            $row=1;
            foreach($this->items as $item) {
                $phpWord->setValue('${modelLine#'.$row.'}', $item->stock->name);
                $phpWord->setValue('${imeiLine#'.$row.'}', $item->stock->imei ? : $item->stock->serial);
                $phpWord->setValue('${reasonLine#'.$row.'}', $item->reason);
                $row++;
            }
            $phpWord->saveAs($path.$outputFileName);
            return $path.$outputFileName;

        } elseif($this->supplier->returns_form == 'e-giant.xlsx') {
            $outputFileName = $this->supplier->name.' RMA.xlsx';
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
            $objPHPExcel = $objReader->load($inputFileName);

            $objPHPExcel->setActiveSheetIndex(0);

            $objPHPExcel->getActiveSheet()
                ->setCellValue('C12', 'RCT')
                ->setCellValue('D12', 'RCT')
                ->setCellValue('E12', Carbon::now()->format('Y-m-d'))
                ->setCellValue('F12', $this->id);
            $row = 15;
            foreach ($this->items as $item) {
                $row++;
                $make = $item->stock->make ? : ($item->stock->product ? $item->stock->product->productMake->title : (strpos(strtolower($item->stock->name), 'ipad') !== false ? 'Apple' : ''));
                $makeModel = $make . " " . $item->stock->name;
                $objPHPExcel->getActiveSheet()->SetCellValue('B' . $row, $makeModel)->getStyle('E' . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('C' . $row, "'" . ($item->stock->imei ?: $item->stock->serial) . "'")->getStyle('C' . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('D' . $row, $item->stock->grade)->getStyle('D' . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('E' . $row, $item->reason)->getStyle('E' . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('F' . $row, $item->stock->purchase_order_number)->getStyle('F' . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('G' . $row, $item->stock->purchase_price_formatted)->getStyle('G' . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
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
            $objPHPExcel->getActiveSheet()->SetCellValue('G' . $row, $this->total_purchase_value_formatted)->getStyle('G' . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

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
            $row += 2;
            $text = "Before returning any items you must inform Egiant Returns department via email - returns@e-giant.co.uk within 7 days of purchase";
            $objPHPExcel->getActiveSheet()->SetCellValue('B' . $row, $text)->getStyle('B' . $row)->getFont()->setBold(false);

            // row+2
            $row += 2;
            $text = "All boxes must be filled in before an RMA number is given. Once this sheet has been completed, the returns department will issue you an RMA Number.";
            $objPHPExcel->getActiveSheet()->SetCellValue('B' . $row, $text)->getStyle('B' . $row)->getFont()->setBold(false);
            $row += 1;
            $text = "Once this has been issued, please return the stock to us at the above address with the RMA form.";
            $objPHPExcel->getActiveSheet()->SetCellValue('B' . $row, $text)->getStyle('B' . $row)->getFont()->setBold(false);

            $row += 2;
            $text = "Returns can only be accepted if returned within 7 days of purchase";
            $objPHPExcel->getActiveSheet()->SetCellValue('B' . $row, $text)->getStyle('B' . $row)->getFont()->setBold(true);
            $row += 1;
            $text = "Blacklist returns will be accepted back within 30 days of purchase";
            $objPHPExcel->getActiveSheet()->SetCellValue('B' . $row, $text)->getStyle('B' . $row)->getFont()->setBold(true);

            $row += 2;
            $text = "Returns will be processed within 3 - 5 Working days";
            $objPHPExcel->getActiveSheet()->SetCellValue('B' . $row, $text)->getStyle('B' . $row)->getFont()->setBold(true);
            $row += 1;
            $text = "Accepted items will be passed for credit. Credit on account is raised within 14 days and can only be used against stock.";
            $objPHPExcel->getActiveSheet()->SetCellValue('B' . $row, $text)->getStyle('B' . $row)->getFont()->setBold(true);
            $row += 1;
            $text = "Rejected items will be sent back to you in your next order.";
            $objPHPExcel->getActiveSheet()->SetCellValue('B' . $row, $text)->getStyle('B' . $row)->getFont()->setBold(true);

            $row += 2;
            $text = "Returns will not be accepted without an RMA Number.";
            $objPHPExcel->getActiveSheet()->SetCellValue('B' . $row, $text)->getStyle('B' . $row)->getFont()->setBold(true);

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            $objWriter->save($path.$outputFileName);

            return $path.$outputFileName;
        } elseif($this->supplier->returns_form == 'default.xlsx') {
            $outputFileName = $this->supplier->name.' RMA.xlsx';
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
                ->setCellValue('C10', $this->supplier->name)
                ->setCellValue('C12', $this->supplier->address_long);
            ;
            $row = 14;
            foreach($this->items as $item) {
                $row++;
                $objPHPExcel->getActiveSheet()->SetCellValue('A'.$row, $item->stock->purchase_date ? $item->stock->purchase_date->format('d.m.Y') : '')->getStyle('G'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('B'.$row, $item->stock->third_party_ref)->getStyle('E'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('C'.$row, $item->stock->imei ? : $item->stock->serial)->getStyle('D'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('D'.$row, $item->stock->name)->getStyle('B'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('E'.$row, $item->reason)->getStyle('H'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
                $objPHPExcel->getActiveSheet()->SetCellValue('F'.$row, $item->stock->purchase_price_formatted)->getStyle('F'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            }

            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            $objWriter->save($path.$outputFileName);

            return $path.$outputFileName;

        }
    }
}
