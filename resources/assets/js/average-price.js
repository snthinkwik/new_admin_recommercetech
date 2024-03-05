class AveragePrice {



    constructor(props) {






        $("#ebay-sales-record-search-term").on('change',function () {

                $("#product").val($("#ebay-sales-record-search-term").val());


        })






            $("#categoryVal").on('change',function(e){
                if($("#categoryVal").val().split('-')[1]==="Tablets"){
                    $("#capacity").show();
                    $("#grade").show();
                    $("#fg").hide();
                    $("#connectivity").show();
                    $("#ram_size").hide();
                    $("#processor").hide();
                    $("#features").hide();
                    $("#operating_system").hide();
                    $("#condition_grade").show();
                }else if($("#categoryVal").val().split('-')[1]==="Mobile Phone"){
                    $("#category").show();
                    $("#capacity").show();
                    $("#fg").show();
                    $("#grade").hide();
                    $("#connectivity").show();
                    $("#ram_size").hide();
                    $("#processor").hide();
                    $("#features").hide();
                    $("#operating_system").hide();
                    $("#condition_grade").show();

                }else if($("#categoryVal").val().split('-')[1]==="Desktops"){

                    $("#category").hide();
                    $("#capacity").hide();
                    $("#fg").hide();
                    $("#grade").hide();
                    $("#connectivity").hide();
                    $("#ram_size").show();
                    $("#processor").show();
                    $("#features").show();
                    $("#operating_system").show();
                    $("#condition_grade").hide();







                }else if($("#categoryVal").val().split('-')[1]==="Laptop"){

                    $("#category").hide();
                    $("#capacity").hide();
                    $("#fg").hide();
                    $("#grade").hide();
                    $("#connectivity").hide();
                    $("#ram_size").show();
                    $("#processor").show();
                    $("#features").show();
                    $("#operating_system").show();
                    $("#condition_grade").hide();






                }

            });

        $("#filterSubmit").click(function(event){
            event.preventDefault();


            $("#secondTable").hide();
            $("#amountDisplay").hide();

            $("#filterSubmit").attr("disabled", 'disabled');
            $("#invoiceProgress").show();
            $("#term").attr("disabled", 'disabled');
            $("#product").attr("disabled", 'disabled');
            $("#conditionVal").attr("disabled", 'disabled');
            $("#categoryVal").attr("disabled", 'disabled');
            $("#capacityVal").attr("disabled", 'disabled');
            $("#colorVal").attr("disabled", 'disabled');
            $("#makeVal").attr("disabled", 'disabled');
            $("#gradeVal").attr("disabled", 'disabled');
            $("#operatingSystemVal").attr("disabled", 'disabled');
            $("#ramSizeVal").attr("disabled", 'disabled');
            $("#storageTypeVal").attr("disabled", 'disabled');
            $("#processorVal").attr("disabled", 'disabled');
            $("#sddCapacityVal").attr("disabled", 'disabled');
            $("#featuresVal").attr("disabled", 'disabled');
            $("#hardDriveCapacityVal").attr("disabled", 'disabled');
            $("#connectivityVal").attr("disabled", 'disabled');










            let product = $("input[name=product]").val();
            let condition = $("#conditionVal").val();




            let category = $("#categoryVal").val();
            let capacity = $("#capacityVal").val();
            let color = $("#colorVal").val();
            let make=$("#makeVal").val();
            let operatingSystemValue=$("#operatingSystemVal").val();
            let ramSizeVal=$("#ramSizeVal").val();
            let storageType=$("#storageTypeVal").val();
            let processor=$("#processorVal").val();
            let ssdCapacity=$("#sddCapacityVal").val();
            let features =$("#featuresVal").val();
            let hard_drive =$("#hardDriveCapacityVal").val();
            let grade=$("#gradeVal").val();
            let connectivity=$("#connectivityVal").val();

            let _token   = $('meta[name="csrf-token"]').attr('content');

            $.ajax({
                url: Config.urls.advancedSearch.search,
                type:"POST",
                data:{
                    product:product,
                    condition:condition,
                    category:category,
                    capacity:capacity,
                    color:color,
                    make:make,
                    grade:grade,
                    operating_system:operatingSystemValue,
                    ram_size:ramSizeVal,
                    storage_type:storageType,
                    processor:processor,
                    ssd_capacity:ssdCapacity,
                    features:features,
                    hard_drive:hard_drive,
                    connectivity:connectivity,

                    _token: _token
                },
                success:function(response){


                    $("#back-btn").show();
                    $("#term").attr("disabled", 'disabled');
                    $("#condition").attr("disabled", 'disabled');
                    $("#search").attr("disabled", 'disabled');
                    if(response) {


                        $("#exampleModal").modal('hide');
                        $("#advanceAmountDisplay").show();

                        $("#secondTable").hide();
                        $('#filterTable').show();

                        let table = '';

                        if (response.status === 400) {
                            $("#error").show();
                            $("#error-message").html("<h6>" + response.message[0] + "</h6>");
                        }
                        if (response.status === 500) {

                            $("#error").show();
                            $("#error-message").html("<h6>" + response.message + "</h6>");

                        }

                        if (response.status === 404) {
                            table += '<tr>';
                            table += '<td colspan="7" align="center"> <h5>' + response.message + '</h5></td>';

                            table += '</tr>';
                            $('#finalData').html(table);
                        }

                        $("#totalQty").html(response.total_qty);
                        $("#totalEst").html(response.total_est);

                        if (response.data.length > 0) {


                            response.data.forEach(value => {



                                table += '<tr style="font-size:12px; text-align: center">';
                                table += '<td>' + value.category + '</td>';
                                table += '<td>' + value.make + '</td>';
                                table += '<td>' + value.product_name + '</td>';
                                table += '<td>' + '-' + '</td>';
                                table += '<td>' + value.model_no + '</td>';
                                table += '<td>' + value.mpn + '</td>';
                                table += '<td>' + value.condition +'</td>';
                                table += '<td>' + value.average +'</td>';
                                table += '<td>' + value.vatStd +'</td>';
                                table += '<td>' + value.vatMRG +'</td>';
                                table += '<td>' + value.best_price_from_named_seller +'</td>';
                                table += '<td>' + value.best_price_network + '</td>';
                                table += '<td>' + value.best_seller + '</td>';
                                table += '<td>' + value.best_seller_listing_rank + '</td>';
                                table += '<td>' + value.first_best_price +'</td>';
                                table += '<td>' + value.first_network + '</td>';
                                table += '<td>' + value.first_seller + '</td>';
                                table += '<td>' + value.first_listing_rank + '</td>';
                                table += '<td>' + value.second_best_price +'</td>';
                                table += '<td>' + value.second_network + '</td>';
                                table += '<td>' + value.second_seller + '</td>';
                                table += '<td>' + value.second_listing_rank + '</td>';
                                table += '<td>' + value.third_best_price +'</td>';
                                table += '<td>' + value.third_network + '</td>';
                                table += '<td>' + value.third_seller + '</td>';
                                table += '<td>' + value.third_listing_rank + '</td>';
                                table += '<td>' + value.available_stock + '</td>';

                                table += '</tr>';


                            });


                            $('#finalData').html(table);


                            $("#term").prop( "disabled", false );
                            $("#product").prop( "disabled", false );
                            $("#conditionVal").prop( "disabled", false );
                            $("#categoryVal").prop( "disabled", false );
                            $("#capacityVal").prop( "disabled", false );
                            $("#colorVal").prop( "disabled", false );
                            $("#makeVal").prop( "disabled", false );
                            $("#gradeVal").prop( "disabled", false );
                            $("#operatingSystemVal").prop( "disabled", false );
                            $("#ramSizeVal").prop( "disabled", false );
                            $("#storageTypeVal").prop( "disabled", false );
                            $("#processorVal").prop( "disabled", false );
                            $("#sddCapacityVal").prop( "disabled", false );
                            $("#featuresVal").prop( "disabled", false );
                            $("#hardDriveCapacityVal").prop( "disabled", false );
                            $("#connectivityVal").prop( "disabled", false );

                            $("#filterSubmit").prop( "disabled", false );
                            $("#invoiceProgress").hide();



                            document.getElementById("advanced_filter").reset();


                        }
                    }


                },
                error: function(error) {
                    console.log(error);
                }
            });
        });


        $(document).on('click', 'li', function(){

            $('#ebay-sales-record-search-term').val($(this).text());

            if (this.loadXhr) this.loadXhr.abort();

            console.warn(CURRENT_URL)
            let data = $("#ebay-order-search-form").serialize();
            this.loadXhr = $.ajax({
                url: CURRENT_URL,
                data: data,
                success: (res) => {
                    console.log(res)
                    $(".universal-loader").hide();
                    $(".universal-spinner").hide();
                    $("#ebay-order-items-wrapper").html(res.itemsHtml);
                    $("#ebay-order-pagination-wrapper").html(res.paginationHtml);
                    $('[data-toggle=popover]', this.$itemsWrapper).popover();
                    if(res.sort) {
                        if(res.sortO == 'DESC')
                            $('th[name='+res.sort).append("<i class='fa fa-caret-down'></i>");
                        else if(res.sortO == 'ASC')
                            $('th[name='+res.sort).append("<i class='fa fa-caret-up'></i>");
                    }
                }
            });
            $('#productList').fadeOut();
        });




    }


}