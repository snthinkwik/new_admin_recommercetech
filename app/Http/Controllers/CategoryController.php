<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {

        $category=Category::paginate(config('app.pagination'));
        return view('category.index',compact('category'));

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return view('category.create');
    }

    public function update($id){
        $category=null;
        if($id){
            $category=Category::find($id);
        }




        return view('category.create',compact('category'));
    }
    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function postSave(Request $request)
    {

        if(!is_null($request->id)){

            $this->validate($request, [
                'name' => 'required|unique:categories,name,'.$request->id
            ]);


        }else{
            $this->validate($request, [
                'name' => 'required|unique:categories'
            ]);
        }

        if(!is_null($request->id) || isset($request->id) ){
            $category=Category::find($request->id);

        }else{
            $category=new Category();
        }



        $slug = Str::slug($request->name, '-');

        $category->name=$request->name;
        $category->slug=$slug;
        $category->save();

        return back()->with('messages.success',"Category Added Successfully");

    }


    public function eBayCategoryIdAssignedCronJob(){


        artisan_call_background("ebay:get-category-id");

        return back()->with('messages.success', 'SuccessFully Run Cron Job');
    }

    public function updateValidation(Request $request){

        $category=Category::find($request->category);

        $category->validation=$request->percentage;
        $category->save();

        return back()->with('messages.success',"Category Updated Successfully");

    }

    public  function removeCategory($id){

        $category=Category::find($id);
        $category->delete();

        return back()->with('messages.success',"Category Deleted Successfully");
    }

}
