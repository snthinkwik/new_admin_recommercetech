<?php

namespace App\Http\Controllers;

use App\Models\MasterAveragePrice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;

class MasterAverageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $averagePrice = MasterAveragePrice::fromRequest($request);
        $averagePrice = $averagePrice->paginate(config('app.pagination'))
            ->appends(array_filter(array_reverse($request->all())));

        $validate=MasterAveragePrice::where("validate",'Yes')->count();
        $unvalidated=MasterAveragePrice::where("validate",'No')->count();




        if ($request->ajax()) {
            return response()->json([
                'itemsHtml' => View::make('average-price-master.list', compact('averagePrice','unvalidated','validate'))->render(),
                'paginationHtml' => '' . $averagePrice->render(),
                'sort' => $request->sort,
                'sortO' => $request->sortO,
            ]);
        }
        return view('average-price-master.index',compact('averagePrice','unvalidated','validate'));

    }

    public function editDiffPercentage(Request $request)
    {
        $current_timestamp = Carbon::now();

        $masterAverage=MasterAveragePrice::find($request->id);
        $masterAverage->ma_update_time=$current_timestamp;
        $masterAverage->type='Manual';
        $masterAverage->manual_price=$request->manual_price;
        $masterAverage->save();

        return back()->with("messages.success",'Successfully');
    }

    public function paginate($items, $perPage = 50, $page = null)
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $total = count($items);
        $currentpage = $page;
        $offset = ($currentpage * $perPage) - $perPage ;
        $itemstoshow = array_slice($items , $offset , $perPage);
        return new LengthAwarePaginator($itemstoshow ,$total   ,$perPage);
    }

    public function removeMasterData(){
        MasterAveragePrice::truncate();
        dd("done");
    }

}
