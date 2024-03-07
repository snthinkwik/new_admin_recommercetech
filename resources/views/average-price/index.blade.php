@extends('app')

@section('title', 'Ebay Average Price')

@section('content')



    <div class="container-fluid">


        @include('messages')
        <a class="btn btn-success" href="{{route('average_price.master')}}" >Master</a>
        <a class="btn btn-success" href="{{route('average_price.ebay')}}" >eBay</a>
{{--        <a class="btn btn-success" href="{{route('average_price.back_market')}}"> Back Market</a>--}}



        <div class="row p10">
        @include('average-price.search-form')
        </div>

        <div class="alert alert-danger font-2 d-flex flex-row" style="display: none !important;" id="error">
            <div class="p-2" ><i class="fa fa-exclamation-circle fa-2x" aria-hidden="true"></i></div>
            <div class="p-2" id="error-message"> </div>
        </div>


        <div id="ebay-order-items-wrapper">

            @include('average-price.list')
        </div>
        <div id="ebay-order-pagination-wrapper">{!! $averagePrice->appends(Request::all())->render() !!}</div>

        <div class="modal fade bd-example-modal-lg" id="exampleModal"  role="dialog" aria-labelledby="exampleModalLabel"
             aria-hidden="true">
            <form method="post" action="#" id="advanced_filter" >
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Advanced Search</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">

                            <div class="row">
                                <div class="col-md-12">
                                    <small><strong> <i class="fa fa-asterisk text-danger" style="font-size: 8px !important;" aria-hidden="true"></i> Required</strong></small>
                                </div>
                            </div>

                            <div class="row">

                                <div class="col-md-6">
                                    <label>Product <i class="fa fa-asterisk text-danger" style="font-size: 5px !important;" aria-hidden="true"></i> </label>
                                    <input type="text" name="product" class="form-control"
                                      id="product" required />
                                </div>

                                <div class="col-md-3">
                                    <label for="Categories"> Categories <i class="fa fa-asterisk text-danger" style="font-size: 5px !important;" aria-hidden="true"></i></label>

                                    <select class="average-select2 form-control" name="category" id="categoryVal" required >

                                        <option value="">Select Category</option>
                                        <option value="9355-Mobile Phone" >Mobile Phone</option>
                                        <option value="171485-Tablets" >Tablets</option>
                                        <option value="179-Desktops">PC Desktops & All-In-Ones</option>
                                        <option value="177-Laptop"> Laptops</option>


                                    </select>


                                </div>

                                <div class="col-md-3" id="make">
                                    <label>Make <i class="fa fa-asterisk text-danger" style="font-size: 5px !important;" aria-hidden="true"></i>
                                    </label>

                                    <select class="average-select2 form-control" name="make" id="makeVal" required>

                                        <option value="">Select Make</option>
                                        @foreach($productMake as $make)
                                        <option value="{{$make->make}}">{{$make->make}}</option>
                                        @endforeach
                                    </select>
                                </div>



                            </div>

                            <hr>
                            <div class="row" id="capacity" style="display: none">
                                <div class="col-md-6">
                                    <label>Capacity</label>

                                    <select class="average-select2 form-control" name="capacityVal" id="capacityVal" >
                                        <option value="">Select Capacity</option>
                                        <option value="8 GB">8 GB</option>
                                        <option value="16 GB">16 GB</option>
                                        <option value="32G B">32 GB</option>
                                        <option value="64 GB">64 GB</option>
                                        <option value="128 GB">128 GB</option>
                                        <option value="256 GB">256 GB</option>
                                        <option value="512 GB">512 GB</option>

                                    </select>
                                </div>
                                <div class="col-md-6" >
                                    <label>Colour</label>
                                    <select class="average-select2" name="color" id="colorVal" >
                                        <option value="">Select Color</option>
                                        <option value="Black"> Black</option>
                                        <option value="Grey"> Grey</option>
                                        <option value="Silver">Silver</option>
                                        <option value="Clear">Clear</option>
                                        <option value="Gold">Gold</option>
                                        <option value="Pink">Pink</option>
                                        <option value="Blue">Blue</option>
                                        <option value="Red">Red</option>
                                    </select>


                                </div>

                            </div>
                            <div class="row" style="display: none" id="condition_grade">

                                <div class="col-md-6" id="fg" style="display: none">
                                    <label>Condition</label>
                                    <select name="condition"  class="average-select2 form-control" @if(isset($advance)) disabled @endif id="conditionVal"  >
                                        <option value="">Select Condition Filter</option>


                                            <option value="">Select Condition Filter</option>
                                            <option value="1000-New"> New
                                            <option value="1500-Open box"> Open box
                                            <option value="1750-New with defects">New with defects
                                            <option value="2000-Certified - Refurbished">Certified - Refurbished
                                            <option value="2010-Excellent - Refurbished"> Excellent - Refurbished
                                            <option value="2020-Very Good - Refurbished"> Very Good - Refurbished
                                            <option value="2030-Good - Refurbished"> Good - Refurbished
                                            <option value="2500-Seller refurbished"> Seller refurbished
                                            <option value="2750-Like New"> Like New
                                            <option value="3000-Used"> Used
                                            <option value="4000-Very Good"> Very Good
                                            <option value="5000-Good"> Good
                                            <option value="6000-Acceptable"> Acceptable
                                            <option value="7000-For parts or not working"> For parts or not working
                                            <option value="Seller refurbished Grade A-Excellent" > Seller refurbished Grade A-Excellent</option>
                                            <option value="Seller refurbished Grade B- Very Good" > Seller refurbished Grade B- Very Good</option>
                                            <option value="Seller refurbished Grade C-Good" >Seller refurbished Grade C-Good</option>




                                    </select>
                                </div>
                                <div class="col-md-6" style="display:none" id="grade" >
                                    <label>Grade</label>
                                    <select class="average-select2 form-control" name="grade" id="gradeVal" style="width: 100%">
                                        <option value="">Select Grade</option>
                                        <option value="Grade A-Excellent">A</option>
                                        <option value="Grade B- Very Good">B</option>
                                        <option value="Grade C-Good">C</option>
                                    </select>
                                </div>

                                <div class="col-md-6" style="display: none" id="connectivity">
                                    <label>Connectivity</label>

                                    <select class="average-select2 form-control"   name="connectivity" id="connectivityVal" >

                                        <option value="">Select Connectivity</option>
                                        <option value="2G">2G</option>
                                        <option value="3G">3G</option>
                                        <option value="4G">4G</option>
                                        <option value="4G+">4G+</option>
                                        <option value="5G">5G</option>
                                        <option value="Bluetooth">Bluetooth</option>
                                        <option value="DLNA">DLNA</option>
                                        <option value="Dual-Band">Dual-Band</option>
                                        <option value="GPRS">GPRS</option>
                                        <option value="GPS">GPS</option>
                                        <option value="Lightning">Lightning</option>
                                        <option value="LTE">LTE</option>
                                        <option value="NFC">NFC</option>
                                        <option value="Quad-Band">Quad-Band</option>
                                        <option value="Tri-Band">Tri-Band</option>
                                        <option value="Wi-Fi">Wi-Fi</option>


                                    </select>
                                </div>


                            </div>





                            <div class="row" style="display: none" id="ram_size">
                                <div class="col-md-6" >
                                    <label>Ram Size</label>
                                    <select class="average-select2 form-control" name="ram_size" id="ramSizeVal" >
                                        <option value="">Select Ram Size</option>
                                        <option value="1 GB">1 GB</option>
                                        <option value="1.5 GB">1.5 GB</option>
                                        <option value="2 GB">2 GB</option>
                                        <option value="3 GB">3 GB</option>
                                        <option value="4 GB">4 GB</option>
                                        <option value="5 GB">5 GB</option>
                                        <option value="6 GB">6 GB</option>
                                        <option value="8 GB">8 GB</option>
                                        <option value="9 GB">9 GB</option>
                                        <option value="10 GB">10 GB</option>
                                        <option value="12 GB">12 GB</option>
                                        <option value="16 GB">16 GB</option>
                                        <option value="24 GB">24 GB</option>
                                        <option value="32 GB">32 GB</option>
                                        <option value="128 GB">128 GB</option>
                                        <option value="64 GB">64 GB</option>
                                        <option value="256 GB">256 GB</option>
                                        <option value="512 GB">512 GB</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label>Storage Type</label>
                                    <select class="average-select2 form-control" name="storage_type" id="storageTypeVal" >
                                        <option value="">Select Storage Type</option>

                                        <option value="HDD (Hard Disk Drive)">HDD (Hard Disk Drive)</option>
                                        <option value="HDD + SSD">HDD + SSD</option>
                                        <option value="SSD (Solid State Drive)">SSD (Solid State Drive)</option>
                                        <option value="eMMC">eMMC</option>



                                    </select>
                                </div>


                            </div>

                            <div class="row" style="display:none" id="processor">
                                <div class="col-md-6" >
                                    <label>Processor</label>
                                    <select class="average-select2 form-control" name="processor" id="processorVal" >
                                        <option value="">Select Processor</option>
                                        @foreach(getProcessor() as $processor)
                                        <option value="{{$processor}}">{{$processor}}</option>
                                        @endforeach

                                    </select>
                                </div>

                                <div class="col-md-6" >
                                    <label>SSD Capacity</label>
                                    <select class="average-select2 form-control" name="ssd_capacity" id="sddCapacityVal" >
                                        <option value="">Select SSD Capacity</option>

                                        <option value="4 GB">4 GB</option>
                                        <option value="8 GB">8 GB</option>
                                        <option value="32 GB">32 GB</option>
                                        <option value="64 GB">64 GB</option>
                                        <option value="320 GB">320 GB</option>
                                        <option value="480 GB">480 GB</option>
                                        <option value="500 GB">500 GB</option>
                                        <option value="512 GB">512 GB</option>
                                        <option value="5 TB">5 TB</option>

                                    </select>
                                </div>


                            </div>


                            <div class="row" style="display: none" id="features">
                                <div class="col-md-6" >
                                    <label>Features</label>
                                    <select class="average-select2 form-control" name="features" id="featuresVal" >
                                        <option value="">Select Features</option>
                                        <option value="Blu-ray Player">Blu-ray Player</option>
                                        <option value="Built-in Bluetooth Adapter">Built-in Bluetooth Adapter</option>
                                        <option value="Built-in Camera">Built-in Camera</option>
                                        <option value="Built-in Microphone">Built-in Microphone</option>
                                        <option value="Built-in Speakers">Built-in Speakers</option>
                                        <option value="Built-in Wi-Fi Adapter">Built-in Wi-Fi Adapter</option>
                                        <option value="Dust Shield">Dust Shield</option>
                                        <option value="Fanless">Fanless</option>
                                        <option value="Liquid Cooling">Liquid Cooling</option>
                                        <option value="Memory Card(s) Reader">Memory Card(s) Reader</option>
                                        <option value="Memory Card Reader">Memory Card Reader</option>
                                        <option value="Optane Memory">Optane Memory</option>
                                        <option value="Optical Drive">Optical Drive</option>
                                        <option value="SD Card Slot">SD Card Slot</option>
                                        <option value="Tiltable">Tiltable</option>
                                        <option value="Touchscreen">Touchscreen</option>
                                        <option value="Ultra-Slim">Ultra-Slim</option>
                                        <option value="VESA Mount">VESA Mount</option>
                                        <option value="Virtual Reality Ready">Virtual Reality Ready</option>
                                    </select>
                                </div>

                                <div class="col-md-6" >
                                    <label>Hard Drive Capacity</label>
                                    <select class="average-select2 form-control" name="hard_drive_capacity" id="hardDriveCapacityVal">
                                        <option value="">Select Hard Drive Capacity</option>

                                        <option value="1 GB">1 GB</option>
                                        <option value="4 GB">4 GB</option>
                                        <option value="6 GB">6 GB</option>
                                        <option value="8 GB">8 GB</option>
                                        <option value="10 GB">10 GB</option>
                                        <option value="12 GB">12 GB</option>
                                        <option value="16 GB">16 GB</option>
                                        <option value="64 GB">64 GB</option>
                                        <option value="Less than 32 GB">Less than 32 GB</option>
                                        <option value="32 GB">32 GB</option>
                                        <option value="32 GB-119 GB">32 GB-119 GB</option>
                                        <option value="128 GB">128 GB</option>
                                        <option value="120 GB-249 GB">120 GB-249 GB</option>
                                        <option value="250 GB-499 GB">250 GB-499 GB</option>
                                        <option value="256 GB">256 GB</option>
                                        <option value="512 GB">512 GB</option>
                                        <option value="525 GB">525 GB</option>
                                        <option value="1 TB">1 TB</option>
                                        <option value="1-2 TB">1-2 TB</option>
                                        <option value="More than 2 TB">More than 2 TB</option>

                                    </select>
                                </div>


                            </div>

                            <div class="row" style="display: none"  id="operating_system" >


                                <div class="col-md-6">
                                    <label>Operating System</label>
                                    <select class="average-select2 form-control" name="operating_system" id="operatingSystemVal">
                                        <option value="">Select Operating System</option>

                                        <option value="Android">Android</option>
                                        <option value="Chrome">Chrome</option>
                                        <option value="DOS">DOS</option>
                                        <option value="FreeDOS">FreeDOS</option>
                                        <option value="Linux">Linux</option>
                                        <option value="Ubuntu">Ubuntu</option>
                                        <option value="Windows">Windows</option>
                                        <option value="Windows 10">Windows 10</option>
                                        <option value="Windows 10 Home">Windows 10 Home</option>
                                        <option value="Windows 10 Pro">Windows 10 Pro</option>
                                        <option value="Windows 10 Pro for Workstation">Windows 10 Pro for Workstation</option>
                                        <option value="Windows 10 S">Windows 10 S</option>
                                        <option value="Windows 11 Home">Windows 11 Home</option>
                                        <option value="Windows 11 Pro">Windows 11 Pro</option>
                                        <option value="Windows 2000">Windows 2000</option>
                                        <option value="Windows 7">Windows 7</option>
                                        <option value="Windows 8">Windows 8</option>
                                        <option value="Windows 8.1">Windows 8.1</option>
                                        <option value="Windows 98">Windows 98</option>
                                        <option value="Windows ME">Windows ME</option>
                                        <option value="Windows NT">Windows NT</option>
                                        <option value="Windows Vista">Windows Vista</option>
                                        <option value="Windows XP">Windows XP</option>


                                    </select>
                                </div>


                            </div>

                        </div>


                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <div class="modal-footer">

                            <button class="btn btn-default btn-block" type="button" id="filterSubmit" >

                                Search
                                <img class="invoice-in-progress" style="display: none" src="{{ asset('/img/ajax-loader.gif') }}" aria-hidden="true" id="invoiceProgress">
                            </button>



                        </div>
                    </div>
                </div>
            </form>
        </div>

    </div>

@endsection
