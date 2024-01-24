{{--<!doctype html>--}}
{{--<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">--}}
{{--<head>--}}
{{--    <meta charset="utf-8">--}}
{{--    <meta name="viewport" content="width=device-width, initial-scale=1">--}}

{{--    <!-- CSRF Token -->--}}
{{--    <meta name="csrf-token" content="{{ csrf_token() }}">--}}

{{--    <title>{{ config('app.name', 'Laravel') }}</title>--}}

{{--    <!-- Fonts -->--}}
{{--    <link rel="dns-prefetch" href="//fonts.bunny.net">--}}
{{--    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">--}}
{{--    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">--}}

{{--    <!-- Scripts -->--}}
{{--    @vite(['resources/sass/app.scss', 'resources/js/app.js'])--}}
{{--</head>--}}
{{--<body>--}}
{{--    <div id="app">--}}
{{--        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">--}}
{{--            <div class="container">--}}
{{--                <a class="navbar-brand" href="{{ url('/') }}">--}}
{{--                    {{ config('app.name', 'Laravel') }}--}}
{{--                </a>--}}
{{--                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">--}}
{{--                    <span class="navbar-toggler-icon"></span>--}}
{{--                </button>--}}

{{--                <div class="collapse navbar-collapse" id="navbarSupportedContent">--}}
{{--                    <!-- Left Side Of Navbar -->--}}
{{--                    <ul class="navbar-nav me-auto">--}}

{{--                    </ul>--}}

{{--                    <!-- Right Side Of Navbar -->--}}
{{--                    <ul class="navbar-nav ms-auto">--}}
{{--                        <!-- Authentication Links -->--}}
{{--                        @guest--}}
{{--                            @if (Route::has('login'))--}}
{{--                                <li class="nav-item">--}}
{{--                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>--}}
{{--                                </li>--}}
{{--                            @endif--}}

{{--                            @if (Route::has('register'))--}}
{{--                                <li class="nav-item">--}}
{{--                                    <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>--}}
{{--                                </li>--}}
{{--                            @endif--}}
{{--                        @else--}}
{{--                            <li class="nav-item dropdown">--}}
{{--                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>--}}
{{--                                    {{ Auth::user()->name }}--}}
{{--                                </a>--}}

{{--                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">--}}
{{--                                    <a class="dropdown-item" href="{{ route('logout') }}"--}}
{{--                                       onclick="event.preventDefault();--}}
{{--                                                     document.getElementById('logout-form').submit();">--}}
{{--                                        {{ __('Logout') }}--}}
{{--                                    </a>--}}

{{--                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">--}}
{{--                                        @csrf--}}
{{--                                    </form>--}}
{{--                                </div>--}}
{{--                            </li>--}}
{{--                        @endguest--}}
{{--                    </ul>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </nav>--}}

{{--        <main class="py-4">--}}
{{--            @yield('content')--}}
{{--        </main>--}}
{{--    </div>--}}
{{--</body>--}}
{{--</html>--}}

<?php
use Illuminate\Support\Facades\Request;

$keys = [''=>'Select Status','any' => 'Any', 'open_paid' => 'Open & Paid', 'open_paid_other_recycler' => 'Open, Paid & Other Recyclers'];
$invoiceKeys = \App\Models\Invoice::getAvailableStatusesWithKeys();

//$status=array_merge($keys,$invoiceKeys);
$users=\App\Models\User::where('invoice_api_id','!=','')->get();




?>
    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"/>

    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('img') }}/favicon.png">

    <title>@yield('title') - {{ config('app.name') }}</title>

    <link href="{{ asset('css/vendor.css') }}" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    {{--<link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">--}}
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.19/css/dataTables.bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.10/css/select2.min.css" rel="stylesheet"/>

    @section('styles')
    @show

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    @if (config('app.env') === 'production')
        <!-- Global site tag (gtag.js) - Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=UA-113410209-1"></script>
        <script>
            window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }

            gtag('js', new Date());

            gtag('config', 'UA-113410209-1');
        </script>


        <script type='text/javascript'>
            window.__lo_site_id = 106249;

            (function () {
                var wa = document.createElement('script');
                wa.type = 'text/javascript';
                wa.async = true;
                wa.src = 'https://d10lpsik1i8c69.cloudfront.net/w.js';
                var s = document.getElementsByTagName('script')[0];
                s.parentNode.insertBefore(wa, s);
            })();
        </script>
    @endif

    @yield('extra-header')
</head>
<body>
@if (config('app.env') === 'staging')
    <div id="environment">{{ ucfirst(config('app.env')) }}</div>
@endif
@if(Auth::user() && Auth::user()->suspended)
    <div id="red-banner">
        <div class="alert alert-danger">Your account has been suspended. Please contact us for further information</div>
    </div>
@endif
@if(!in_array(Route::currentRouteName(), ['home.tv3-stats', 'home.tv4-stats']))
    @include('nav')
@endif

<div class="modal fade" id="exampleModalLong" tabindex="-1" role="dialog" aria-labelledby="exampleModalLongTitle"
     aria-hidden="true">
    <div class="modal-dialog" role="document">

        <div class="modal-content">

            {{--            <form method="post" action="{{route('stock.export.filter')}}" id="filter_form">--}}
            {{--                <input type="hidden" name="_token" value="{{{ csrf_token() }}}"/>--}}

            {{--                <div class="modal-header">--}}

            {{--                    <h5 class="modal-title" > Filter By <span id="exampleModalLongTitle"></span> </h5>--}}
            {{--                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">--}}
            {{--                        <span aria-hidden="true">&times;</span>--}}
            {{--                    </button>--}}
            {{--                </div>--}}
            {{--                <div class="modal-body" id="purchase_date_section" style="display: none">--}}
            {{--                    <label>Start Date</label>--}}

            {{--                    <input type="text" name="purchase_start_date" class="has-datepicker form-control input_purchase" >--}}
            {{--                    <label>End Date</label>--}}

            {{--                    <input type="text" name="purchase_end_date" class="has-datepicker form-control input_purchase" >--}}
            {{--                </div>--}}

            {{--                <div class="modal-body" id="sale_date_section" style="display: none">--}}
            {{--                    <label>Start Date</label>--}}

            {{--                    <input type="text" name="sale_start_date" class="has-datepicker form-control input_sale" >--}}
            {{--                    <label>End Date</label>--}}

            {{--                    <input type="text" name="sale_end_date" class="has-datepicker form-control input_sale">--}}
            {{--                </div>--}}

            {{--                <div class="modal-body" id="make_section" style="display: none">--}}
            {{--                    <label>Make</label>--}}
            {{--                    <select class="filter-select2 form-control" name="make" id="input_make" >--}}
            {{--                        @if(\App\Stock::getMake())--}}
            {{--                            <option value="">select make</option>--}}
            {{--                            @foreach(\App\Stock::getMake() as $make)--}}
            {{--                                <option value="{{$make->make}}">{{ucfirst($make->make)}}</option>--}}
            {{--                            @endforeach--}}
            {{--                        @endif--}}
            {{--                    </select>--}}
            {{--                </div>--}}
            {{--                <div class="modal-body" id="model_section" style="display: none">--}}
            {{--                    <label>Model</label>--}}
            {{--                    <select class="filter-select-model" name="model" id="input_model"></select>--}}
            {{--                </div>--}}

            {{--                <div class="modal-body" id="grade_section" style="display: none">--}}
            {{--                    <label>Grade</label>--}}

            {{--                    <select class="filter-select2" name="grade" id="input_grade">--}}

            {{--                        @if(App\Stock::getAvailableGradesWithKeys('all'))--}}
            {{--                            <option value="">select grade</option>--}}
            {{--                            @foreach(\App\Stock::getAvailableGradesWithKeys('all') as $grade)--}}

            {{--                                <option value="{{$grade}}">{{$grade}}</option>--}}
            {{--                            @endforeach--}}
            {{--                        @endif--}}
            {{--                    </select>--}}
            {{--                </div>--}}
            {{--                <div class="modal-body" id="vat_type_section" style="display: none">--}}
            {{--                    <label>Vat Type</label>--}}

            {{--                    <select class="filter-select2" name="vat_type" id="input_vat">--}}
            {{--                        <option value="">select vat</option>--}}
            {{--                        <option value="Margin">Margin</option>--}}
            {{--                        <option value="Standard">Standard</option>--}}
            {{--                    </select>--}}
            {{--                </div>--}}

            {{--                <div class="modal-body" id="customer_id_section" style="display: none">--}}
            {{--                    <label>Customer ID </label>--}}

            {{--                    <select class="filter-select2" name="customer_id" id="input_customer_id">--}}
            {{--                        @if(\App\Stock::getCustomerId())--}}
            {{--                            <option value="">select customer id</option>--}}
            {{--                            @foreach(\App\Stock::getCustomerId() as $customerId)--}}
            {{--                                <option value="{{$customerId->id}}">{{$customerId->id}}</option>--}}
            {{--                            @endforeach--}}
            {{--                        @endif--}}
            {{--                    </select>--}}
            {{--                </div>--}}
            {{--                <div class="modal-body" id="customer_name_section" style="display: none">--}}
            {{--                    <label>Customer Name </label>--}}
            {{--                    <select class="filter-select2" name="customer_name" id="input_customer_name">--}}
            {{--                        @if(\App\Stock::getCustomerName())--}}
            {{--                            <option value="">select customer name</option>--}}
            {{--                            @foreach(\App\Stock::getCustomerName() as $name)--}}

            {{--                                <option value="{{$name->id}}">{{$name->first_name.' '.$name->last_name}}</option>--}}
            {{--                            @endforeach--}}
            {{--                        @endif--}}
            {{--                    </select>--}}
            {{--                </div>--}}
            {{--                <div class="modal-body" id="supplier_section" style="display: none">--}}
            {{--                    <label>Supplier</label>--}}
            {{--                    <select class="filter-select2" name="supplier" id="input_supplier">--}}
            {{--                        @if(\App\Stock::getSupplier())--}}
            {{--                            <option value="">select supplier</option>--}}
            {{--                            @foreach(\App\Stock::getSupplier() as $name)--}}
            {{--                                <option value="{{$name->name}}">{{$name->name}}</option>--}}
            {{--                            @endforeach--}}
            {{--                        @endif--}}
            {{--                    </select>--}}
            {{--                </div>--}}
            {{--                <div class="modal-body" id="product_type_section" style="display: none">--}}
            {{--                    <label>Product Type</label>--}}

            {{--                    <select class="filter-select2" name="product_type" id="input_product_type">--}}
            {{--                        @if(\App\Stock::getProductType())--}}
            {{--                            <option value="">select product type</option>--}}
            {{--                            @foreach(\App\Stock::getProductType() as $product)--}}
            {{--                                <option value="{{$product->product_type}}">{{$product->product_type}}</option>--}}
            {{--                            @endforeach--}}
            {{--                        @endif--}}
            {{--                    </select>--}}
            {{--                </div>--}}


            {{--                <div class="modal-footer">--}}
            {{--                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>--}}
            {{--                    <input type="submit" class="btn btn-primary" value="Export Csv">--}}

            {{--                </div>--}}
            {{--            </form>--}}
        </div>
    </div>
</div>


<div class="modal fade" id="modalLong"  role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">

        <div class="modal-content">
            {{--            <form method="post" action="{{route('sales.export.filter')}}" id="filter_sales">--}}
            {{--                <input type="hidden" name="_token" value="{{{ csrf_token() }}}"/>--}}
            {{--                <div class="modal-header">--}}
            {{--                    <h5 class="modal-title" > Filter By <span id="exampleModalLongTitleSales"></span> </h5>--}}
            {{--                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">--}}
            {{--                        <span aria-hidden="true">&times;</span>--}}
            {{--                    </button>--}}
            {{--                </div>--}}
            {{--                <div class="modal-body">--}}
            {{--                    <div id="sale_date" style="display: none">--}}

            {{--                        <div class="row">--}}
            {{--                            <div class="col-md-6">--}}
            {{--                                <label>Start Date</label>--}}
            {{--                                <input type="date" name="start_date" class="form-control" id="input_start_date" >--}}
            {{--                            </div>--}}
            {{--                            <div class="col-md-6">--}}
            {{--                                <label>End Date</label>--}}
            {{--                                <input type="date" name="last_date" class="form-control" id="input_last_date" >--}}
            {{--                            </div>--}}
            {{--                        </div>--}}
            {{--                    </div>--}}
            {{--                    <div id="customer" style="display: none">--}}
            {{--                        <label>Customer Name</label>--}}
            {{--                        --}}{{--                   <input type="text" name="customer_name" id="input_customer_name" class="form-control">--}}
            {{--                        <select class="average-select2" name="customer_id" id="input_customer_name">--}}


            {{--                            <option value="">Select Customer</option>--}}

            {{--                            @foreach($users as $user)--}}
            {{--                                <option value="{{$user->invoice_api_id}}">{{$user->first_name.' '. $user->last_name}}</option>--}}
            {{--                            @endforeach--}}


            {{--                        </select>--}}

            {{--                    </div>--}}
            {{--                    <div id="status" style="display: none">--}}

            {{--                        {!! BsForm::groupSelect('status', $keys + $invoiceKeys,['id' => 'input_status']) !!}--}}
            {{--                    </div>--}}
            {{--                </div>--}}
            {{--                <div class="modal-footer">--}}
            {{--                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>--}}
            {{--                    <input type="submit" class="btn btn-primary" value="Export" />--}}
            {{--                </div>--}}
            {{--            </form>--}}
        </div>
    </div>
</div>

@yield('content')

@yield('bottom-left')

{{--@if(!in_array(Route::currentRouteName(), ['stock-take.scanner']))
    @include('footer')
@endif--}}

<script>
    // A few globals.
    window.BASE_URL = "{{ url('') }}";
    window.CURRENT_URL = "{{ Request::url() }}";
    @if (Auth::user())
        window.USER = {
        type: "{{ Auth::user()->type }}"
    };
    @else
        window.USER = null;
    @endif

    // Config for things that don't change.
    var Config = {
        urls: {
            sales: {
                {{--redirect: {!! json_encode(route('sales.redirect')) !!},--}}
                {{--summary: {!! json_encode(route('sales.summary')) !!},--}}
                {{--statusCheck: {!! json_encode(route('sales.status-check')) !!},--}}
                {{--changeStatus: {!! json_encode(route('sales.change-status')) !!},--}}
                {{--paymentComplete: {!! json_encode(route('sales.payment-complete')) !!},--}}
                {{--pay: {!! json_encode(route('sales.pay')) !!}--}}
            },
            basket: {
                {{--toggle: {!! json_encode(route('basket.toggle')) !!},--}}
                {{--getHtml: {!! json_encode(route('basket.get-html')) !!}--}}
            },
            emails: {
                {{--preview: {!! json_encode(route('emails.preview')) !!},--}}
                {{--checkStatuses: {!! json_encode(route('emails.check-statuses')) !!},--}}
                {{--testSend: {!! json_encode(route('emails.test-send')) !!},--}}
                {{--saveDraft: {!! json_encode(route('emails.save-draft')) !!},--}}
            },
            stock: {
                {{--index: {!! json_encode(route('stock')) !!},--}}
                {{--batch: {!! json_encode(route('stock.redirect-batch')) !!},--}}
                {{--locationSave: {!! json_encode(route('stock.locations.save')) !!},--}}
                {{--receive: {!! json_encode(route('stock.receive')) !!},--}}
                {{--inRepairChangeBack: {!! json_encode(route('stock.in-repair-change-back')) !!},--}}
                {{--shownToSave: {!! json_encode(route('stock.shown-to-save')) !!},--}}
                {{--changeGrade: {!! json_encode(route('stock.change-grade')) !!},--}}
                {{--batchesSend: {!! json_encode(route('batches.send-batches')) !!}--}}
            },
            batches: {
                {{--sendBatches: {!! json_encode(route('batches.send-batches')) !!}--}}
            },
            customers: {
                {{--details: {!! json_encode(route('customers.details')) !!},--}}
                {{--customerReturn: {!! json_encode(route('customer.return.save')) !!}--}}
            },
            admin: {
                users: {
                    {{--autocomplete: {!! json_encode(route('admin.users.autocomplete')) !!},--}}
                    {{--emails: {--}}
                    {{--    preview: {!! json_encode(route('admin.users.emails.preview')) !!}--}}
                    {{--},--}}
                    {{--whatsAppUsersAdded: {!! json_encode(route('admin.users.whats-app-users-added')) !!},--}}
                    {{--customersWithBalanceReminders: {!! json_encode(route('admin.users.customers-with-balance-reminders')) !!},--}}
                    {{--customersWithBalanceHide: {!! json_encode(route('admin.users.customers-with-balance-hide')) !!}--}}
                }
            },
            unlocks: {
                {{--index: {!! json_encode(route('unlocks')) !!},--}}
                {{--add: {!! json_encode(route('unlocks.add')) !!},--}}
                {{--addByStock: {!! json_encode(route('unlocks.add-by-stock')) !!},--}}
                {{--paymentComplete: {!! json_encode(route('unlocks.payment-complete')) !!},--}}
                {{--pay: {!! json_encode(route('unlocks.pay-get')) !!},--}}
                {{--bulkRetry: {!! json_encode(route('unlocks.bulk-retry')) !!}--}}
            },
            auth: {
                {{--postcode: {!! json_encode(route('auth.postcode')) !!}--}}
            },
            home: {
                {{--singleSearch: {!! json_encode(route('home.single-search')) !!}--}}
            },
            suppliers: {
                {{--redirect: {!! json_encode(route('suppliers.redirect')) !!}--}}
            },
            parts: {
                {{--search: {!! json_encode(route('parts.search')) !!}--}}
            },
            advancedSearch: {
                {{--search: {!! json_encode(route('advance.search')) !!},--}}
                {{--autoComplete:{!! json_encode(route('average_price.search-info')) !!}--}}
            }
        }
    };

    // Data for things specific to current request.
    var Data = {
        stock: {},
        sales: {},
        unlocks: {},
    };
</script>

@section('pre-scripts')
@show

{{--<script src="{{ asset('/js/vendor.js') }}"></script>--}}
{{--<script src="{{ elixir('js/app.js') }}"></script>--}}
<script type="text/javascript" src="https://code.jquery.com/jquery-1.7.1.min.js"></script>
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.js"></script>
<script type="text/javascript" charset="utf8"
        src="https://cdn.datatables.net/1.10.19/js/dataTables.bootstrap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.10/js/select2.min.js"></script>



<script>
    $(document).ready(function () {
        $('.network-select2').select2({
            placeholder: "Select Network",
        });
        $('.repair-select2').select2({
            placeholder: "Select Repair Id",
        });
        $('.supplier-select2').select2({
            placeholder: "Select Supplier",
        });
        $('.filter-select2').select2({
            width:'520px',
            placeholder: 'Please Select',

        })
        $('.product-select2').select2({
            placeholder: "Select Product Id",
            height:'20%'
        });

        $(".average-select2").select2({
            width:'100%'
        })
        $(".purchase_order_number").select2({
            width:'320px',
        })



    });

    {{--$('.filter-select-model').select2({--}}
    {{--    placeholder: "Search Model...",--}}
    {{--    width: '550px',--}}
    {{--    ajax: {--}}
    {{--        url: "{{route('stock.all.model')}}",--}}
    {{--        data: function (params) {--}}
    {{--            return {--}}
    {{--                term: params.term,--}}
    {{--                page: params.page || 1,--}}
    {{--            };--}}
    {{--        },--}}
    {{--        processResults: function(data, params) {--}}
    {{--            console.log(data);--}}
    {{--            var page = params.page || 1;--}}
    {{--            return {--}}
    {{--                results: $.map(data, function (item)  {  return {id: item, text: item}}),--}}
    {{--                pagination: {--}}
    {{--                    // THE `10` SHOULD BE SAME AS `$resultCount FROM PHP, it is the number of records to fetch from table`--}}
    {{--                    more: (page * 10) <= data[0].total_count--}}
    {{--                }--}}
    {{--            };--}}
    {{--        },--}}
    {{--    }--}}
    {{--});--}}

    var unit = 0;
    // if user changes value in field
    $('.field').change(function () {
        unit = this.value;
    });
    $('.add').click(function () {
        var input = $(this).prevUntil('.add');
        var inputval = input.val();
        var addinputval = parseInt(inputval) + parseInt(1);
        $(this).prevUntil('.add').val(addinputval);
    });
    $('.sub').click(function () {
        var input = $(this).nextUntil('.sub');
        var inputval = input.val();
        if (inputval >= 1) {
            var addinputval = parseInt(inputval) - parseInt(1);
            $(this).nextUntil('.sub').val(addinputval);
        }
    });
</script>
@yield('scripts-footer')
@section('scripts')
@show
</body>
</html>

