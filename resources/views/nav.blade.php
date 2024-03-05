<nav class="navbar navbar-default navbar-fixed-top">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                <span class="sr-only">Toggle Navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
{{--            <a class="navbar-brand" href="{{ route('stock') }}" style="padding: 15px">--}}
{{--                <img src="{{ asset('img') }}/Recomm.png" aria-hidden="true" width="108px">--}}
{{--            </a>--}}
        </div>

        @if(!in_array(Route::currentRouteName(), ['stock-take.scanner']))
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                @section('nav-left')
                @show
                <ul class="nav navbar-nav">
                    @if (Auth::user() && Auth::user()->type == 'admin')
                        @if(Auth::user()->admin_type != 'LCD Buyback')
                            @if(!in_array(Auth::user()->admin_type, ['admin', 'manager']))
                                <li @active><a href="{{ route('stock') }}">Stock</a></li>
                            @else
                                <li class="dropdown">
                                    <a href="javascript:" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                        Stock <span class="caret"></span>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li @active><a href="{{ route('stock') }}">Stock</a></li>
                                        <li @active><a href="{{ route('repairs') }}">Repairs</a></li>
                                        <li @active><a href="{{ route('batches') }}">Batches</a></li>
                                        <li @active><a href="{{ route('stock.ready-for-sale') }}">Ready for Sale</a></li>
                                        <li @active><a href="{{ route('stock.retail-stock') }}">Retail Stock</a></li>
                                        <li @active><a href="{{ route('stock.overview') }}">Inventory</a></li>
                                        @if (Auth::user()->type === 'admin' && in_array(Auth::user()->admin_type, ['admin', 'manager']))
                                            <li @active><a href="{{ route('parts') }}">Parts</a></li>
                                        @endif
                                    </ul>
                                </li>
                            @endif
                            @if(in_array(Auth::user()->admin_type, ['admin', 'manager']))
                                <li class="dropdown">
                                    <a href="javascript:" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                        Sales <span class="caret"></span>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li @active><a href="{{ route('sales.dashboard') }}">Dashboard</a></li>
                                        <li @active><a href="{{ route('sales') }}">Sales</a></li>
                                        <li @active><a href="{{ route('sales.custom-order') }}">Custom Invoice</a></li>
                                        <li @active><a href="{{ route('saved-baskets') }}">Saved Baskets</a></li>
                                        <li @active><a href="{{ route('admin.ebay-orders') }}">Retail orders</a></li>
                                        <li @active><a href="{{ route('seller_fees.index') }}">Seller Fees</a></li>
                                        <li @active><a href="{{ route('customer.return.index') }}">Customer Return</a></li>
                                        <li @active><a href="{{ route('average_price.master') }}">Average Price</a></li>
                                        <li @active><a href="{{route('sales.customer_return')}}">Credit Notes </a> </li>
                                    </ul>
                                </li>
                            @endif
                        @endif






                        @if (Auth::user()->type === 'admin' && in_array(Auth::user()->admin_type, ['admin', 'manager']))
                            <li class="dropdown">
                                <a href="javascript:" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                    Unlocks <span class="caret"></span>
                                </a>
                                <ul class="dropdown-menu">
                                    <li @active><a href="{{ route('unlocks') }}">Unlocks</a></li>
                                    <li @active><a href="{{ route('unlocks.add') }}">Add Unlocks</a></li>
                                    <li @active><a href="{{ route('unlock-mapping') }}">Unlock Mapping</a></li>
                                </ul>
                            </li>
                        @endif
                        @if (Auth::user()->type === 'admin' && in_array(Auth::user()->admin_type, ['admin', 'manager']))
                            <li @active><a href="{{ route('admin.users') }}">Users</a></li>
                        @endif
                    @endif

















                    @if (Auth::user() && Auth::user()->type !== 'user')
                        @if(in_array(Auth::user()->admin_type, ['admin', 'manager']))
                            <li @active><a href="{{ route('stats') }}">Stats</a></li>
                        @endif

                        <li class="dropdown">
                            <a href="javascript:" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                Purchase <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu">
                                @if(Auth::user()->type == 'admin' && in_array(Auth::user()->admin_type, ['admin', 'manager']))
                                    <li @active><a href="{{ route('suppliers') }}">Suppliers</a></li>
                                    <li @active><a href="{{ route('suppliers.returns') }}">Suppliers Returns</a></li>
                                @endif
                            </ul>
                        </li>

{{--                        <li @active><a href="{{ route('products') }}">Products</a></li>--}}

                        @if(in_array(Auth::user()->admin_type, ['admin', 'manager']))
                            <li class="dropdown">
                                <a href="javascript:" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                    Admin <span class="caret"></span>
                                </a>
                                <ul class="dropdown-menu">
                                    <li @active><a href="{{ route('admin.settings') }}">Settings</a></li>
                                    <li @active><a href="{{ route('emails') }}">Emails</a></li>
                                    <li @active><a href="{{ route('category.index') }}">Category</a></li>
                                    <li @active><a href="{{route('admin.testing-result')}}">Testing Results</a></li>
                                    <li @active><a href="{{ route('ebay-seller.index') }}">Ebay Seller</a></li>
                                    <li class="dropdown-submenu">
                                        <a class="test" tabindex="-1" href="javascript:">Tools <span class="caret"></span></a>
                                        <ul class="dropdown-menu">
                                            <li @active><a href="{{ route('engineer.index') }}">Repair Engineer</a></li>
                                            <li @active><a href="{{ route('stock-take') }}">Stock Take</a></li>
                                            <li @active><a href="{{ route('stock-take.scanner') }}">Stock Take Scanner</a></li>
                                            <li @active><a href="{{ route('stock-take.missing-items') }}">Missing Items</a></li>
                                            <li @active><a href="{{ route('stock-take.mark-as-lost') }}">Mark as Lost</a></li>
                                            <li @active><a href="{{ route('stock-take.view-lost-items') }}">View Lost Items</a></li>
                                            <li @active><a href="{{ route('stock-take.view-deleted-items') }}">View Deleted Items</a></li>
                                        </ul>
                                    </li>
                                    <li @active><a href="{{ route('stock-stats') }}">Stock Stats</a></li>
                                    <li @active><a href="{{ route('admin.email-format') }}">Email Format</a></li>
                                    <li @active><a href="{{ route('exception-logs') }}">Exception Logs</a></li>
                                    <li @active><a href="{{ route('colour.index') }}">Colour</a></li>
                                    <li @active><a href="{{ route('average_price.back_market.raw-data') }}">Back Market Raw Data</a></li>
                                </ul>
                            </li>
                        @endif
                    @endif







                </ul>

                @section('nav-middle')
                @show
                <ul class="nav navbar-nav navbar-right">
                    @if(Auth::user() && Auth::user()->type == 'admin' && in_array(Auth::user()->admin_type, ['admin', 'manager']))
                        <li>
                            <a  href="{{ route('notifications') }}">
                                <i class="fa fa-bell-o fa-lg"></i>
                                <span class="notify"> <span class="heartbit"></span> <span class="point"></span> </span>
                                {{ \App\Models\Notification::getNotificationsCount() }}
                            </a>
                        </li>
                    @endif

                    @if(session('engineer'))
                        <li><a href="{{ route('repairs.logout') }}">Logout</a></li>
                    @elseif (Auth::guest())
                        <li @active><a href="{{ route('auth.login') }}">Login</a></li>
                        <li @active><a href="{{ route('auth.register') }}">Register</a></li>
                    @else
                        <li class="dropdown">
                            <a href="javascript" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                                {{ Auth::user()->first_name }} <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu" role="menu">
                                @if (session('users.previous'))
                                    <li>
                                        {!! BsForm::open(['route' => 'auth.previous']) !!}
                                        {!! BsForm::submit('Switch back to admin', ['class' => 'btn btn-link']) !!}
                                        {!! BsForm::close() !!}
                                    </li>
                                @endif
                                <li @active><a href="{{ route('account') }}">Account</a></li>
                                @if (Auth::user()->type === 'user')
                                    <li @active><a href="{{ route('account.balance') }}">My Balance</a></li>
                                @endif
                                <li><a href="{{ route('auth.logout') }}">Logout</a></li>
                            </ul>
                        </li>
                    @endif
                </ul>

                @section('nav-right')
                @show
            </div>
        @endif
    </div>
</nav>
