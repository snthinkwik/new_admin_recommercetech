@extends('app')

@section('title', Auth::user()->type === 'admin' ? "Stock":"Shop")

@section('content')

	<div class="container-fluid">

		@include('messages')

		@if ($errors->has('user_invoice_api_id'))
			<div class="alert alert-danger">{{ $errors->first('user_invoice_api_id') }}</div>
		@endif

		@include('stock.nav')

		@include(
			'stock.search-form',
			[
				'showStatus' => Auth::user() && Auth::user()->canRead('stock.inbound'),
				'showCondition' => Auth::user() && Auth::user()->canRead('stock.condition'),
			]
		)
		@if(Auth::user()->type == 'admin' && in_array(Auth::user()->admin_type, ['admin', 'manager']))
			<div class="stock-copy-whats-app-items-list-wrapper" style="width:0px; height:0px;">
				@include('stock.copy-items-list')
			</div>
			<a class="btn btn-default stock-copy-whats-app-button btn-xs">Copy for What's App</a>
		@endif
		<div id="stock-items-wrapper">
			@include('stock.items')
		</div>


		<div id="stock-pagination-wrapper">{!! $stock->appends(Request::all())->render() !!}</div>

		@if (Auth::user() && Auth::user()->type === 'user')
			<div class="alert alert-success">
				<p>Welcome {{ Auth::user()->first_name }}, to the Recomm Stock System. We sell phones individually and also in batches.
				You can purchase the above individually or view the <a href="{{ route('batches') }}">batches for sale</a>.
					Want a bargain? Head on over to our
                    {{--<a href="{{ route('auction') }}">auctions page</a>--}}
                    to see our current auctions.</p>
			</div>
		@endif
	</div>

	@if (Auth::user()->type !== 'user')
		@include('stock.receive-bulk-modal')
		@include('stock.set-repair-modal')
		@include('stock.shown-for-modal')
	@endif

@endsection

@section('nav-right')
	@if (Auth::user()->type !== 'user' && in_array(Auth::user()->admin_type, ['admin', 'manager']))
		<div class="navbar-form navbar-right pr0">
			<div class="btn-group">
				<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
					Export CSV <span class="caret"></span>
				</button>
				<ul class="dropdown-menu">
					<li><a href="{{ route('stock.export') }}">Export Everything</a></li>
					<li><a href="{{ route('stock.in-stock.export') }}">Export Items in Stock</a></li>
					<li><a href="{{ route('stock.export', ['option' => 'for_sale']) }}">Export Items for Sale</a></li>
					<li><a href="{{ route('stock.export-aged-stock') }}">Export Aged Stock</a></li>
					<li><a href="" data-toggle="modal" data-target="#exampleModalLong" id="purchase_date">Export Item By Purchase Date</a></li>
					<li><a href="" data-toggle="modal" data-target="#exampleModalLong" id="sale_date">Export Item By Sale Date</a></li>
                    <li><a href="" data-toggle="modal" data-target="#exampleModalLong" id="make">Export Item By Make</a></li>
					<li><a href="" data-toggle="modal" data-target="#exampleModalLong" id="model">Export Item By Model</a></li>
					<li><a href="" data-toggle="modal" data-target="#exampleModalLong" id="grade">Export Item By Grade</a></li>
					<li><a href="" data-toggle="modal" data-target="#exampleModalLong" id="vat_type">Export Item By Vat Type</a></li>
					<li><a href="" data-toggle="modal" data-target="#exampleModalLong" id="customer_id">Export Item By Customer ID</a></li>
					<li><a href="" data-toggle="modal" data-target="#exampleModalLong" id="customer_name">Export Item By Customer Name</a></li>
                    <li><a href="" data-toggle="modal" data-target="#exampleModalLong" id="supplier">Export Item By Supplier</a></li>
                    <li><a href="" data-toggle="modal" data-target="#exampleModalLong" id="product_type">Export Item By Produt Type</a></li>


				</ul>
			</div>
		</div>
	@endif
	@if (Request::route()->getName() === 'stock' && Auth::user() && Auth::user()->type !== 'user' && in_array(Auth::user()->admin_type, ['admin', 'manager']))
		<form class="navbar-form navbar-right">
			<div class="btn-group navbar-right">
				<a class="btn btn-default" id="receive-stock-bulk-no-check" href="javascript:">Receive</a>
			</div>
		</form>
	@endif
	<div class="navbar-form navbar-right pr0">
		@if(in_array(Auth::user()->admin_type, ['admin', 'manager']))
		<div class="btn-group">
			<button id="create-sale" class="btn btn-default">
				{{ Auth::user() ? Auth::user()->texts['sales']['create'] : 'Create sale' }}
			</button>
			@if (Auth::user()->type === 'admin')
				<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					<span class="caret"></span>
					<span class="sr-only">Toggle Dropdown</span>
				</button>
				<ul class="dropdown-menu">
					<li><a id="create-sale-other-recycler" href="javascript:">Sell to Other Recycler</a></li>
					<li><a href="{{ route('stock.quick-order-form') }}">Quick Order</a></li>
					<li class="divider"></li>
					<li><a href="javascript:"  id="create-batch">Create batch</a></li>
					<li><a id="create-repair" href="javascript:">Create Repair</a></li>
					<li><a id="create-return" href="javascript:">Create Supplier Return</a></li>
                    <li><a id="customer-return" href="javascript:">Create Customer Return</a></li>
				</ul>
			@endif
		</div>
		@endif
	</div>
	<div id="basket-wrapper" class="navbar-right pr0">
		@include('basket.navbar')
	</div>
@endsection

@section('bottom-left')
	@if(\Cache::has('stock-users') && (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$_SERVER['HTTP_USER_AGENT']) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($_SERVER['HTTP_USER_AGENT'],0,4))))
		<div class="bottom-left">
			<div class="alert alert-info">
			{{ \Cache::get('stock-users') }} people are looking at the items right now!
			</div>
		</div>
	@endif
@endsection

@section('pre-scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>

    <script>
        $("#purchase_date").on('click',function () {
            $("#purchase_date_section").show();
            $("#exampleModalLongTitle").html("Purchase Date")
            $("#sale_date_section").hide();
            $("#make_section").hide();
            $("#model_section").hide();
            $("#grade_section").hide();
            $("#vat_type_section").hide();
            $("#customer_id_section").hide();
            $("#customer_name_section").hide();
            $("#supplier_section").hide();
            $("#product_type_section").hide();
            $(".input_purchase").attr("required", "true");
            $(".input_sale").val("");
            $("#input_grade").val("");
            $("#input_mode").val("");
            $("#input_vat").val("");
            $("#input_customer_id").val("")
            $("#input_customer_name").val("")
            $("#input_supplier").val("");
            $("#input_product_type").val("");
            $("#input_mode").attr("required", false);
            $("#input_make").attr("required", false);
            $("#input_grade").attr("required", false);
            $("#input_vat").attr("required", false);
            $("#input_customer_id").attr("required", false);
            $("#input_customer_name").attr("required", false);
            $("#input_supplier").attr("required", false);
            $("#input_product_type").attr("required", false);

            $(".input_sale").attr("required", false);
        })
        $("#sale_date").on('click',function () {
            $("#purchase_date_section").hide();
            $("#exampleModalLongTitle").html("Sale Date")
            $("#sale_date_section").show();
            $("#make_section").hide();
            $("#model_section").hide();
            $("#grade_section").hide();
            $("#vat_type_section").hide();
            $("#customer_id_section").hide();
            $("#customer_name_section").hide();
            $("#supplier_section").hide();
            $("#product_type_section").hide();
            $(".input_sale").attr("required", "true");
            $(".input_purchase").val("");
            $("#input_grade").val("");
            $("#input_mode").val("");
            $("#input_vat").val("");
            $("#input_customer_id").val("")
            $("#input_customer_name").val("")
            $("#input_supplier").val("");
            $("#input_product_type").val("");

            $("#input_mode").attr("required", false);
            $("#input_make").attr("required", false);
            $("#input_grade").attr("required", false);
            $("#input_vat").attr("required", false);
            $("#input_customer_id").attr("required", false);
            $("#input_customer_name").attr("required", false);
            $("#input_supplier").attr("required", false);
            $("#input_product_type").attr("required", false);
            $(".input_purchase").attr("required", false);

        })
        $("#make").on('click',function () {
            $("#purchase_date_section").hide();
            $("#exampleModalLongTitle").html("Make")
            $("#sale_date_section").hide();
            $("#make_section").show();
            $("#model_section").hide();
            $("#grade_section").hide();
            $("#vat_type_section").hide();
            $("#customer_id_section").hide();
            $("#customer_name_section").hide();
            $("#supplier_section").hide();
            $("#product_type_section").hide();
            $("#input_make").attr("required", "true");
            $("#input_grade").val("");
            $("#input_mode").val("");
            $("#input_vat").val("");
            $("#input_customer_id").val("")
            $("#input_customer_name").val("")
            $("#input_supplier").val("");
            $("#input_product_type").val("");
            $(".input_sale").val("");
            $(".input_purchase").val("");

            $("#input_mode").attr("required", false);
            $("#input_grade").attr("required", false);
            $("#input_vat").attr("required", false);
            $("#input_customer_id").attr("required", false);
            $("#input_customer_name").attr("required", false);
            $("#input_supplier").attr("required", false);
            $("#input_product_type").attr("required", false);
            $(".input_purchase").attr("required", false);
            $(".input_sale").attr("required", false);


        })
        $("#model").on('click',function () {
            $("#purchase_date_section").hide();
            $("#exampleModalLongTitle").html("Model")
            $("#sale_date_section").hide();
            $("#make_section").hide();
            $("#model_section").show();
            $("#grade_section").hide();
            $("#vat_type_section").hide();
            $("#customer_id_section").hide();
            $("#customer_name_section").hide();
            $("#supplier_section").hide();
            $("#product_type_section").hide();
            $("#input_mode").attr("required", "true");

            $("#input_grade").val("");
            $("#input_make").val("");
            $("#input_vat").val("");
            $("#input_customer_id").val("")
            $("#input_customer_name").val("")
            $("#input_supplier").val("");
            $("#input_product_type").val("");
            $(".input_sale").val("");
            $(".input_purchase").val("");

            $("#input_make").attr("required", false);
            $("#input_grade").attr("required", false);
            $("#input_vat").attr("required", false);
            $("#input_customer_id").attr("required", false);
            $("#input_customer_name").attr("required", false);
            $("#input_supplier").attr("required", false);
            $("#input_product_type").attr("required", false);
            $(".input_purchase").attr("required", false);
            $(".input_sale").attr("required", false);


        })
        $("#grade").on('click',function () {
            $("#purchase_date_section").hide();
            $("#exampleModalLongTitle").html("Grade")
            $("#sale_date_section").hide();
            $("#make_section").hide();
            $("#model_section").hide();
            $("#grade_section").show();
            $("#vat_type_section").hide();
            $("#customer_id_section").hide();
            $("#customer_name_section").hide();
            $("#supplier_section").hide();
            $("#product_type_section").hide();
            $("#input_grade").attr("required", "true");

            $("#input_model").val("");
            $("#input_make").val("");
            $("#input_vat").val("");
            $("#input_customer_id").val("")
            $("#input_customer_name").val("")
            $("#input_supplier").val("");
            $("#input_product_type").val("");
            $(".input_sale").val("");
            $(".input_purchase").val("");
            $("#input_mode").attr("required", false);
            $("#input_make").attr("required", false);
            $("#input_vat").attr("required", false);
            $("#input_customer_id").attr("required", false);
            $("#input_customer_name").attr("required", false);
            $("#input_supplier").attr("required", false);
            $("#input_product_type").attr("required", false);
            $(".input_purchase").attr("required", false);
            $(".input_sale").attr("required", false);


        })
        $("#vat_type").on('click',function () {
            $("#purchase_date_section").hide();
            $("#exampleModalLongTitle").html("Vat Type")
            $("#sale_date_section").hide();
            $("#make_section").hide();
            $("#model_section").hide();
            $("#grade_section").hide();
            $("#vat_type_section").show();
            $("#customer_id_section").hide();
            $("#customer_name_section").hide();
            $("#product_type_section").hide();
            $("#input_vat").attr("required", "true");
            $("#input_model").val("");
            $("#input_make").val("");
            $("#input_grade").val("");
            $("#input_customer_id").val("")
            $("#input_customer_name").val("")
            $("#input_supplier").val("");
            $("#input_product_type").val("");
            $(".input_sale").val("");
            $(".input_purchase").val("");
            $("#input_mode").attr("required", false);
            $("#input_make").attr("required", false);
            $("#input_grade").attr("required", false);
            $("#input_customer_id").attr("required", false);
            $("#input_customer_name").attr("required", false);
            $("#input_supplier").attr("required", false);
            $("#input_product_type").attr("required", false);
            $(".input_purchase").attr("required", false);
            $(".input_sale").attr("required", false);
        })
        $("#customer_id").on('click',function () {
            $("#purchase_date_section").hide();
            $("#exampleModalLongTitle").html("Customer ID")
            $("#sale_date_section").hide();
            $("#make_section").hide();
            $("#model_section").hide();
            $("#grade_section").hide();
            $("#vat_type_section").hide();
            $("#customer_id_section").show();
            $("#customer_name_section").hide();
            $("#supplier_section").hide();
            $("#product_type_section").hide();
            $("#input_customer_id").attr("required", "true");
            $("#input_model").val("");
            $("#input_make").val("");
            $("#input_grade").val("");
            $("#input_vat").val("")
            $("#input_customer_name").val("")
            $("#input_supplier").val("");
            $("#input_product_type").val("");
            $(".input_sale").val("");
            $(".input_purchase").val("");

            $("#input_mode").attr("required", false);
            $("#input_make").attr("required", false);
            $("#input_grade").attr("required", false);
            $("#input_vat").attr("required", false);
            $("#input_customer_name").attr("required", false);
            $("#input_supplier").attr("required", false);
            $("#input_product_type").attr("required", false);
            $(".input_purchase").attr("required", false);
            $(".input_sale").attr("required", false);
        })
        $("#customer_name").on('click',function () {
            $("#purchase_date_section").hide();
            $("#exampleModalLongTitle").html("Customer Name")
            $("#sale_date_section").hide();
            $("#make_section").hide();
            $("#model_section").hide();
            $("#grade_section").hide();
            $("#vat_type_section").hide();
            $("#customer_id_section").hide();
            $("#customer_name_section").show();
            $("#supplier_section").hide();
            $("#product_type_section").hide();
            $("#input_customer_name").attr("required", "true");
            $("#input_model").val("");
            $("#input_make").val("");
            $("#input_grade").val("");
            $("#input_vat").val("")
            $("#input_customer_id").val("")
            $("#input_supplier").val("");
            $("#input_product_type").val("");
            $(".input_sale").val("");
            $(".input_purchase").val("");

            $("#input_mode").attr("required", false);
            $("#input_make").attr("required", false);
            $("#input_grade").attr("required", false);
            $("#input_vat").attr("required", false);
            $("#input_customer_id").attr("required", false);
            $("#input_supplier").attr("required", false);
            $("#input_product_type").attr("required", false);
            $(".input_purchase").attr("required", false);
            $(".input_sale").attr("required", false);
        })
        $("#supplier").on('click',function () {
            $("#purchase_date_section").hide();
            $("#exampleModalLongTitle").html("Supplier")
            $("#sale_date_section").hide();
            $("#make_section").hide();
            $("#model_section").hide();
            $("#grade_section").hide();
            $("#vat_type_section").hide();
            $("#customer_id_section").hide();
            $("#customer_name_section").hide();
            $("#supplier_section").show();
            $("#product_type_section").hide();
            $("#input_supplier").attr("required", "true");
            $("#input_model").val("");
            $("#input_make").val("");
            $("#input_grade").val("");
            $("#input_vat").val("")
            $("#input_customer_id").val("")
            $("#input_customer_name").val();
            $("#input_product_type").val();
            $(".input_sale").val("");
            $(".input_purchase").val("");

            $("#input_mode").attr("required", false);
            $("#input_make").attr("required", false);
            $("#input_grade").attr("required", false);
            $("#input_vat").attr("required", false);
            $("#input_customer_id").attr("required", false);
            $("#input_customer_name").attr("required", false);
            $("#input_product_type").attr("required", false);
            $(".input_purchase").attr("required", false);
            $(".input_sale").attr("required", false);
        })

        $("#product_type").on('click',function () {
            $("#purchase_date_section").hide();
            $("#exampleModalLongTitle").html("Product Type")
            $("#sale_date_section").hide();
            $("#make_section").hide();
            $("#model_section").hide();
            $("#grade_section").hide();
            $("#vat_type_section").hide();
            $("#customer_id_section").hide();
            $("#customer_name_section").hide();
            $("#supplier_section").hide();
            $("#product_type_section").show();
            $("#input_product_type").attr("required", "true");
            $("#input_model").val("");
            $("#input_make").val("");
            $("#input_grade").val("");
            $("#input_vat").val("");
            $("#input_customer_id").val("");
            $("#input_customer_name").val("");
            $("#input_supplier").val("");
            $(".input_sale").val("");
            $(".input_purchase").val("");

            $("#input_mode").attr("required", false);
            $("#input_make").attr("required", false);
            $("#input_grade").attr("required", false);
            $("#input_vat").attr("required", false);
            $("#input_customer_id").attr("required", false);
            $("#input_customer_name").attr("required", false);
            $("#input_supplier").attr("required", false);
            $(".input_purchase").attr("required", false);
            $(".input_sale").attr("required", false);
        })


        $("#addStock").on('click',function () {
            var modalConfirm = function(callback){
                $("#mi-modal").modal('show');
                $("#modal-btn-si").on("click", function(){
                    callback(true);
                    $("#mi-modal").modal('hide');
                });

                $("#modal-btn-no").on("click", function(){
                    callback(false);
                    $("#mi-modal").modal('hide');
                });
            };

            modalConfirm(function(confirm){
                if(confirm){
                    var code=$("#verification_code").val();
                    $("#code").val(code);
                    $("#verificationForm").submit()
                }else{
                    $("#result").html("NO CONFIRMADO");
                }
            });
        });

        $("#non_serialised_stock").on('click',function () {

            var modalConfirm = function(callback){
                $("#mi-modal").modal('show');
                $("#modal-btn-si").on("click", function(){
                    callback(true);
                    $("#mi-modal").modal('hide');
                });

                $("#modal-btn-no").on("click", function(){
                    callback(false);
                    $("#mi-modal").modal('hide');
                });
            };


            modalConfirm(function(confirm){
                if(confirm){
                    var code=$("#verification_code").val();
                    $("#code_non_serialised").val(code);
                    $("#non_serialised_verificationForm").submit()
                }else{
                    $("#result").html("NO CONFIRMADO");
                }
            });
        });
    </script>
@endsection

