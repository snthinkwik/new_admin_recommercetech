<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Image;

class ProductsController extends Controller
{
    public function getIndex(Request $request)
    {
        $productsQuery = Product::orderBy('id', 'desc');

        if ($request->term) {
            $productsQuery->where(function ($q) use ($request) {
                $q->where('make', 'like', "%$request->term%");
                $q->orWhere('model', 'like', "%$request->term%");
                $q->orWhere('product_name', 'like', "%$request->term%");
                $q->orWhere('slug', 'like', "%$request->term%");
                $q->orWhere('back_market_id', 'like', "%$request->term%");

            });
        }

        if ($request->vat_type) {
            $productsQuery->where('vat_type', $request->vat_type);
        }

        if ($request->non_serialised) {
            $productsQuery->where('non_serialised', $request->non_serialised);
        }

        if ($request->category) {
            $productsQuery->where('category', $request->category);
        }


        $products = $productsQuery->paginate(config('app.pagination'));

        if ($request->ajax()) {
            return response()->json([
                'itemsHtml' => View::make('products.list', compact('products'))->render(),
                'paginationHtml' => $products->appends($request->all())->render()
            ]);
        }

        return view('products.index', compact('products'));
    }

    public function create()
    {

        return view('products.new');
    }

    public function getSingle($id, Request $request)
    {
        $product = Product::with('images')->findOrFail($id);

        if ($request->segment(3)) {
            $page = $request->segment(3);
        }else{
            $page=null;
        }


        return view('products.single', compact('product', 'page'));
    }


    public function postCreate(Request $request)
    {

        $this->validate($request, [
            'make' => 'required',
            // 'model' => 'required',
            //   'capacity' => 'required',
            'category' => 'required',
            'sku' => 'unique:products,slug',
            'product_name' => 'required|unique:products,product_name',
            'ean' => 'unique:products,ean',

        ]);

        $allowedfileExtension = ['jpeg', 'jpg', 'png', 'docx'];

        if ($request->hasFile('image')) {
            foreach ($request->file('image') as $key => $image) {
                //   dd(in_array($image->getClientOriginalExtension(),$valideType));
                $extension = $image->getClientOriginalExtension();

                $check = in_array($extension, $allowedfileExtension);


                if (!$check) {
                    return back()->with('messages.error', 'Only Upload Png,Jpeg,Jpg');
                }


            }

        }


        $product = new Product();
        $product->make = $request->make;
        $product->model = $request->model;
        $product->colour = $request->color;
        $product->capacity = $request->capacity;
        $product->category = $request->category;
        $product->capacity = $request->capacity;
        $product->sort_description = $request->sort_description;
        $product->product_features = $request->product_features;
        $product->non_serialised = $request->non_serialised ? 1 : 0;
        $product->multi_quantity = $request->non_serialised ? $request->multi_quantity : 1;
        $product->vat = $request->vat_type;
        $product->purchase_price = $request->purchase_price;
        $product->ean = trim($request->ean);
        $product->retail_comparison = $request->retail_comparison ? 1 : 0;
        $product->weight = $request->weight;
        $product->pco2 = $request->pco2;

        $product->product_name = $request->product_name;
        //->slug = str_replace(' ', '-', str_replace(' gb', 'gb', strtolower($product->product_name)));
        $product->slug = trim($request->sku);
        $product->save();


        if ($request->hasFile('image')) {
            foreach ($request->file('image') as $key => $image) {
                $dir = base_path('public/img/products/');

                $randomId = rand(10, 10000);
                $filename = $randomId . "." . $image->getClientOriginalExtension();
                Image::make($image)->resize(2048, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })->save($dir . $filename, 80);
                //  $filename = asset('img/products/'.$filename);
                $productImage = new ProductImage();
                $productImage->product_id = $product->id;
                $productImage->name = $filename;
                $productImage->save();
            }

        }

        if ($product->retail_comparison) {
            artisan_call_background("ebay:dynamic-price");
        }

        return redirect()->route('products.single', ['id' => $product->id])->with('messages.success', 'Created');
    }


    public function postUpdate(Request $request)
    {
        $this->validate($request, [
            'make' => 'required',
            // 'model' => 'required',
            // 'capacity' => 'required',
            'category' => 'required',
            'slug' => 'unique:products,slug,' . $request->id,
            'product_name' => 'required|unique:products,product_name,' . $request->id,
            'ean' => 'unique:products,ean,' . $request->id

        ]);

        $allowedfileExtension = ['jpeg', 'jpg', 'png', 'docx'];

        if ($request->hasFile('image')) {
            foreach ($request->file('image') as $key => $image) {
                //   dd(in_array($image->getClientOriginalExtension(),$valideType));
                $extension = $image->getClientOriginalExtension();

                $check = in_array($extension, $allowedfileExtension);


                if (!$check) {
                    return back()->with('messages.error', 'Only Upload Png,Jpeg,Jpg');
                }


            }

        }


        $current_timestamp = Carbon::now();


        $product = Product::findOrFail($request->id);
        $product->make = $request->make;
        $product->model = $request->model;
        $product->product_name = $request->product_name;
        $product->colour = $request->colour;
        $product->capacity = $request->capacity;
        $product->category = $request->category;
        $product->capacity = $request->capacity;
        $product->sort_description = $request->sort_description;
        $product->product_features = $request->product_features;
        $product->vat = $request->vat_type;
        $product->non_serialised = $request->non_serialised ? 1 : 0;
        $product->multi_quantity = $request->non_serialised ? $request->multi_quantity : 1;
        $product->purchase_price = $request->purchase_price;
        $product->ean = trim($request->ean);
        $product->retail_comparison = $request->retail_comparison ? 1 : 0;
        $product->weight = $request->weight;
        $product->pco2 = $request->pco2;
        $product->back_market_id = $request->back_market_id !== '' ? $request->back_market_id : null;
        $product->archive = $request->archive;
        $product->epd = $request->epd;
        $product->asw = $request->asw;
        $product->ma = $request->ma;
        $product->refurbished_price_A = $request->price_a;
        $product->refurbished_price_B = $request->price_b;
        $product->refurbished_price_C = $request->price_c;
        $product->always_in_stock_A = $request->always_in_stock_A ? $request->always_in_stock_A : 0;
        $product->always_in_stock_B = $request->always_in_stock_B ? $request->always_in_stock_B : 0;
        $product->always_in_stock_C = $request->always_in_stock_C ? $request->always_in_stock_C : 0;
        if ($request->ma !== '') {

            $product->ma_update_time = $current_timestamp;
        }


        $product->slug = trim($request->slug);
        $product->save();


        if ($request->hasFile('image')) {
            foreach ($request->file('image') as $key => $image) {
                $dir = base_path('public/img/products/');

                $randomId = rand(10, 10000);
                $filename = $randomId . "." . $image->getClientOriginalExtension();
                Image::make($image)->resize(2048, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })->save($dir . $filename, 80);

                $productImage = new ProductImage();
                $productImage->product_id = $product->id;
                $productImage->name = $filename;
                $productImage->save();
            }

        }

        if ($product->retail_comparison) {
            artisan_call_background("ebay:dynamic-price");
        }

        return redirect()->back()->with('messages.success', 'Updated');
    }

    public function removeImage($id)
    {
        $productImage = ProductImage::findOrFail($id);


        if (File::exists(public_path('img/products/' . $productImage->name))) {
            File::delete(public_path('img/products/' . $productImage->name));
        }

        File::delete($productImage->name);

        $productImage->delete();


        return redirect()->back()->with('messages.success', 'Image Successfully deleted');


    }

    public function getProductSearch(Request $request)
    {
        $term = $request->term;

        $products = Product::where(function ($query) use ($term) {
            $query->where('id', 'like', "%$term%")->orWhere('product_name', 'like', "%$term%");
        })->select('product_name', 'id')->get();

        return response()->json($products);
    }

    public function deletedProduct($id)
    {

        $product = Product::find($id);

        $stock = Stock::where('product_id', $id)->first();
        if (!is_null($stock)) {
            return back()->with("messages.success", "Sorry This Product can't Deleted Because of Product Assigned To Stock");
        }
        $product->delete();

        return back()->with("messages.success", 'Product Successfully Deleted');


    }

    public function getAllExport(Request $request)
    {

        ini_set('max_execution_time', 1000);

        $product = Product::where('slug', '!=', '')->orderBy('id', 'desc');
        $fields = [
            'Product Id' => 'id',
            'Category' => 'category',
            'Make' => 'make',
            'Product Name' => 'product_name',
            'Capacity' => 'capacity',
            'Model' => 'model',
            'MPN' => 'slug',
            'EAN' => 'ean',
            'Weight' => 'weight',
            'PCO2' => 'pco2',
            'Non Serialised' => 'non_serialised',
            'BackMarket Id' => 'back_market_id',
            'eBay (EPID)' => 'epd',
            'Amazon (ASIN)' => 'asw',
            'MA ID' => 'ma'

        ];


        $csvPath = tempnam('/tmp', 'stock-full-export-');
        unlink($csvPath);
        $csvPath .= '.csv';
        $fh = fopen($csvPath, 'w');
        fputcsv($fh, array_keys($fields));


        $product->chunk(500, function ($items) use ($fields, $fh) {


            foreach ($items as $item) {


                $trimSlug = str_replace(array('\'', '"', ',', ';', '–', '‎', 'œur', '-', 'Е6320', '™', '’’', '•', '’', ' ', '”', '€', '﻿', '↑'), '', $item->slug);
                $trimModel = str_replace(array('\'', '"', ',', ';', '–', '‎', 'œur', '-', 'Е6320', '™', '’’', '•', '’', ' ', '”', '€', '﻿', '↑'), '', $item->model);
                $trimProductName = str_replace(array('\'', '"', ',', ';', '–', '‎', 'œur', '-', 'Е6320', '™', '’’', '•', '’', ' ', '”', '€', '﻿', '↑'), '', $item->product_name);
                $trimEAN = str_replace(array('\'', '"', ',', ';', '–', '‎', 'œur', '-', 'Е6320', '™', '’’', '•', '’', ' ', '”', '€', '﻿', '↑'), '', $item->ean);

                $item->product_name = trim($trimProductName);
                $item->capacity = $item->capacity ? trim($item->capacity) : '-';
                $item->slug = $item->slug ? trim($trimSlug) : '-';
                $item->make = $item->make ? trim($item->make) : '-';

                $item->model = $item->model ? trim($trimModel) : '-';
                $item->category = $item->category ? trim($item->category) : '-';
                $item->ean = $item->ean ? trim($trimEAN) : '-';
                $item->weight = $item->weight ? trim($item->weight) : '-';
                $item->pco2 = $item->pco2 ? trim($item->pco2) : '-';
                $item->non_serialised = $item->non_serialised ? 'Yes' : 'No';
                $item->back_market_id = $item->back_market_id ? trim($item->back_market_id) : '-';
                $item->epd = $item->epd ? trim($item->epd) : '-';
                $item->asw = $item->asw ? trim($item->asw) : '-';
                $item->ma = $item->ma ? trim($item->ma) : '-';

                $row = array_map(function ($field) use ($item) {

                    return $item->$field;
                }, $fields);


                fputcsv($fh, $row);
            }
        });


        fclose($fh);
        convert_file_encoding($csvPath);
        header('Content-length: ' . filesize($csvPath));
        header('Content-Disposition: attachment; filename="Product.csv"');
        header('Content-type: application/vnd.ms-excel');
        readfile($csvPath);
        die;


    }


    public function importCsv(Request $request)
    {
        $file = $request->file('csv');
        //  dd($request->all());
        if (empty($file)) {
            return back()->withInput()->with('messages.error', "Please upload the CSV file.");
        }

        if ($file->getClientOriginalExtension() != 'csv') {
            return back()->with('messages.error', 'Invalid file extension');
        }


        $data = \Excel::load($file)->get();
        if ($data->count()) {

            foreach ($data as $key => $value) {
                $product = Product::find($value['product_id']);

                if ($value['category'] !== '-') {
                    $product->category = trim($value['category']);
                }

                if ($value['make'] !== "-") {
                    $product->make = trim($value['make']);
                }
                if ($value['product_name'] !== '-') {
                    $product->product_name = trim($value['product_name']);
                }
                if ($value['capacity'] !== '-') {
                    $product->capacity = trim($value['capacity']);
                }
                if ($value['model'] !== '-') {
                    $product->model = trim($value['model']);
                }
                if ($value['sku'] !== '-') {
                    $product->slug = trim($value['sku']);
                }
                if ($value['ean'] !== '-') {
                    $product->ean = trim($value['ean']);
                }
                if ($value['weight'] !== '-') {
                    $product->weight = trim($value['weight']);
                }
                if ($value['pco2'] !== '-') {
                    $product->pco2 = trim($value['pco2']);
                }

                if ($value['back_market_id'] !== '-') {
                    $product->back_market_id = trim($value['back_market_id']);
                }

                $product->non_serialised = $value['non_serialised'] === "Yes" ? 1 : 0;
                $product->epd = trim($value['epd']);
                $product->asw = trim($value['asw']);
                $product->ma = trim($value['ma']);


                $product->save();

            }

        }

        return back()->with('messages.success', 'Product Updated.');
    }
}
