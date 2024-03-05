class Sales
{
	constructor(props) 
	{
		// Initial state of the customer data used to determine if the user modified anything.


			$('.read-more-content').addClass('hide_content')
			$('.read-more-show, .read-more-hide').removeClass('hide_content')

			// Set up the toggle effect:
			$('.read-more-show').on('click', function(e) {
				$(this).next('.read-more-content').removeClass('hide_content');
				$(this).addClass('hide_content');
				e.preventDefault();
			});

			// Changes contributed by @diego-rzg
			$('.read-more-hide').on('click', function(e) {
				var p = $(this).parent('.read-more-content');
				p.addClass('hide_content');
				p.prev('.read-more-show').removeClass('hide_content'); // Hide only the preceding "Read More"
				e.preventDefault();
			});

		this.customerInitialState = '';
		
		this.cacheDom();
		this.bindMethods()
		this.bindEvents();
		this.initUserAutocomplete();
		this.initAwaitingPayment();
		if(this.$otherRecyclerInput.length) this.salesOtherAccountName();
		if(this.$summaryBatchCustomerId.length) this.summaryBatchCustomerButton();


	}

	initAwaitingPayment()
	{
		if (!Data.sales.awaitingPaymentId) {
			return;
		}

		const check = () => {
			$.ajax(Config.urls.sales.statusCheck, {
				data: { ids: [Data.sales.awaitingPaymentId] },
				success: (res) => {
					if (res[0].status === 'created') {
						const token = $('meta[name=csrf-token]').attr('content');
						const $form = $(
							`<form action="${Config.urls.sales.pay}" method="post">
								<input type="hidden" name="id" value="${Data.sales.awaitingPaymentId}">
								<input type="hidden" name="_token" value="${token}">
							</form>`
						);
						$form.appendTo('body').submit();
					}
					else {
						setTimeout(check, 100);
					}
				}
			});
		};

		setTimeout(check, 100);
	}

	checkPaymentComplete()
	{
		if (!window.paymentCompleteSale) {
			return;
		}

		clearInterval(this.paymentCheckInterval);
		const token = $('meta[name=csrf-token]').attr('content');
		const $form = $(
			`<form action="${Config.urls.sales.paymentComplete}" method="post">
				<input type="hidden" name="_token" value="${token}">
			</form>`
		);
		$form.appendTo('body').submit();
	}

	initUserAutocomplete()
	{
		this.$manualSaleUserSearch.autocomplete({
			source: Config.urls.admin.users.autocomplete,
			select: (event, ui) => {
				this.$manualSaleUserId.val(ui.item.value);
				this.$manualSaleUserSearch.val(ui.item.label)
				return false;
			},
			focus: (event, ui) => {
				return false;
			},
		});
	}

	createSaleManual()
	{
		this.$manualSaleModal.modal();
	}

	saveTrackingNumber(event)
	{
		var $form = $(event.target);
		$.ajax($form.attr('action'), {
			type: 'post',
			data: $form.serialize(),
			success: (res) => {
				this.$tableRows.
					filter('tr[data-sale-id=' + $form[0].sale_id.value + ']').
					html(
						res.newRowHtml.replace(/^\s*<tr[^>]*>|<\/tr>\s*$/g, '')
					);
				this.$trackingNumberModal.modal('hide');
				if(res.status !== "success") {
					alert("Status: " + res.status + "\n" + res.message);
				}
			}
		});
		return false;
	}

	showTrackingNumberModal(event)
	{
		var $button = $(event.target);
		var saleId = $button.closest('[data-sale-id]').data('sale-id');
		this.$trackingNumberModal.modal();
		$('[name=sale_id]').val(saleId);
	}

	customerSelected(event, id)
	{
		var $field = $(event.target);
		$field.val('').blur();
		$field.parent().find('[name=customer_external_id]').val(id);
		if(this.$summaryBatchCustomerId.length) {
			this.$summaryBatchCustomerId.trigger('change');
		}
		this.$customerFormWrapper.html('<img src="' + url('/img/ajax-loader.gif') + '">')
		this.getCustomerDetails(id, true).then(this.showCustomerDetails);
	}
	customerAuctionSelected(id)
	{
		$("input[name=customer_external_id]").val(id);
		this.$customerFormWrapper.html('<img src="' + url('/img/ajax-loader.gif') + '">')
		this.getCustomerDetails(id, true).then(this.showCustomerDetails);
	}
	
	changeStatus(event)
	{
		var $target = $(event.target);
		var $tr = $target.closest('tr')
		var saleId = $tr.data('sale-id');
		$.ajax(Config.urls.sales.changeStatus, {
			type: 'post',
			data: { id: saleId, status: $target.data('status') },
			success: (res) => {
				console.log(res.status + " - " + res.message);
				$tr.html(res.newRowHtml.replace(/^\s*<tr[^>]*>|<\/tr>\s*$/g, ''));
				if(res.message !== "Status changed") {
					alert("Status: " + res.status + "\n" + res.message);
				}
			},
			error: APP.ajaxError
		});
	}
	
	checkCustomerModified()
	{
		this.$customerModifiedField.val(this.customerInitialState !== this.$customerFormWrapper.serialize() ? 1 : 0);
		this.$summaryFormSubmitButton.prop('disabled', true);
		this.$summaryFormSubmitButton.hide();
	}
	
	showCustomerDetails(data)
	{
		var formHtml = data.formHtml;
		this.$customerFormWrapper.html(formHtml);
		this.customerInitialState = this.$customerFormWrapper.serialize();
	}
	
	/**
	 * @param string external_id
	 * @param bool|int delay Should we delay returning the response if it arrives very quickly. Used to avoid the loading
	 *                       indicator flashing before the content is shown. You can use boolean true for default value
	 *                       or a number of milliseconds. Leave empty or send a falsy value if you don't want a delay.
	 * @returns {Promise}
	 */
	getCustomerDetails(external_id, delay)
	{
		delay = delay === true ? 350 : delay;
		var startTime = new Date().getTime();

		return new Promise((resolve) => {
			$.ajax(Config.urls.customers.details, {
				data: { external_id },
				success: (res) => {
					const endTime = new Date().getTime();
					if (delay && endTime - startTime < delay) {
						setTimeout(() => resolve(res), delay - (endTime - startTime));
					}
					else {
						resolve(res);
					}
				}
			});
		});
	}

	auctionUserCheck()
	{
		if($('#auctionUser').text()){
			var id = $('#auctionUser').text();
			this.customerAuctionSelected(id);
		}
	}

	cancel()
	{
		return confirm("Are you sure you want to cancel this sale?");
	}
	
	statusCheck()
	{
		const idsToCheck = [];

		this.$tableStatusTds.filter((i, td) => !$(td).data('creation-status-finished'))
			.each((i, td) => {
				idsToCheck.push($(td).closest('tr').data('sale-id'));
			});

		if (!idsToCheck.length) {
			return;
		}

		$.ajax(Config.urls.sales.statusCheck, {
			data: { ids: idsToCheck },
			success: (res) => {
				for (let i in res) {
					const saleInfo = res[i];
					const $row = this.$tableRows.filter('[data-sale-id=' + saleInfo.id + ']');

					const $statusTd = $('td[data-creation-status]', $row);
					$('.status', $statusTd).html(saleInfo.invoice_link || saleInfo.status);

					const $amountTd = $('td.invoice-total-amount', $row);
					$amountTd.text(saleInfo.amount);
                    const $amountExVat = $('td.invoice-total-amount-ex-vat', $row);
                    $amountExVat.text(saleInfo.ex_vat);

                    const $amountTotalProfit = $('td.total-profit', $row);
                    $amountTotalProfit.removeClass('text-danger');
                    $amountTotalProfit.text(saleInfo.total_profit);


                    const $amountTotalProfitPer = $('td.total-profit-per', $row);
                    $amountTotalProfitPer.removeClass('text-danger');
                    $amountTotalProfitPer.text(saleInfo.profit_per);

                    const $amountTotalVatMargin = $('td.vat-margin', $row);
                    $amountTotalVatMargin.removeClass('text-danger');
                    $amountTotalVatMargin.text(saleInfo.vat_margin);

                    const $amountTotalTrueProfit = $('td.true-profit', $row);
                    $amountTotalTrueProfit.removeClass('text-danger');
                    $amountTotalTrueProfit.text(saleInfo.true_profit);

                    const $platformSales = $('td.platform', $row);
                    $platformSales.text(saleInfo.platform);


                    const $amountTotalTrueProfitPre = $('td.true-profit-pre', $row);
                    $amountTotalTrueProfitPre.text(saleInfo.true_profit_per);

                    // const $amountTotalPlatformFee = $("#value_"+saleInfo.id);
                    // console.log($amountTotalPlatformFee);
                    // $amountTotalPlatformFee.text(saleInfo.seller_fees);

                    $("#fee_"+saleInfo.id).val(saleInfo.seller_fees);


                    // const $amountTotalShippingCost = $('td.shipping_cost', $row);
                    // $amountTotalShippingCost.val(saleInfo.shipping_cost);

                    $("#shipping_"+saleInfo.id).val(saleInfo.shipping_cost);


                    const $amountTotalEstProfit = $('td.estProfit', $row);
                    $amountTotalEstProfit.removeClass('text-danger');
                    $amountTotalEstProfit.text(saleInfo.est_net_profit);

                    const $amountTotalEstProfitPre = $('td.estProfitPre', $row);
                    $amountTotalEstProfitPre.text(saleInfo.est_net_profit_per);




















                    $statusTd.data('creation-status', saleInfo.status);
					$statusTd.data('creation-status-finished', saleInfo.status_finished);

					if (saleInfo.status_finished) {
						$('.invoice-in-progress', $statusTd).remove();
						this.$saleCreatedAlertAmount.text(saleInfo.amount);
					}
				}
			}
		});
	}
	
	intervalForStatusCheck()
	{
		const statusCheck = () => {
			if (this.$tableStatusTds.filter((i, td) => !$(td).data('creation-status-finished')).length) {
				this.statusCheck();
				setTimeout(statusCheck, 2000);
			}
		}
		setTimeout(statusCheck);
	}

	search()
	{
		const serialized = this.$queryForm.serialize();
		if (serialized !== this.searchFormSerialized) {
			this.searchFormSerialized = serialized;

			this.load();
		}
	}

	load()
	{
		if (this.loadXhr) this.loadXhr.abort();
		this.loadXhr = $.ajax({
			url: CURRENT_URL,
			data: this.$queryForm.serialize(),
			success: (res) => {

				$(document).ready(function() {
					$('.read-more-content').addClass('hide_content')
					$('.read-more-show, .read-more-hide').removeClass('hide_content')

					// Set up the toggle effect:
					$('.read-more-show').on('click', function(e) {
						$(this).next('.read-more-content').removeClass('hide_content');
						$(this).addClass('hide_content');
						e.preventDefault();
					});

					// Changes contributed by @diego-rzg
					$('.read-more-hide').on('click', function(e) {
						var p = $(this).parent('.read-more-content');
						p.addClass('hide_content');
						p.prev('.read-more-show').removeClass('hide_content'); // Hide only the preceding "Read More"
						e.preventDefault();
					});
				});
				this.$itemsWrapper.html(res.itemsHtml);
				this.$paginationWrapper.html(res.paginationHtml);
				$('[data-toggle=popover]', this.$itemsWrapper).popover();
			}
		});
	}

    salesUnlock()
    {
        const $selected = $('input[name^="ids_to_unlock["]:checked');
        if (!$selected.length) {
            return alert("You didn't select anything.");
        }
        else if (!confirm("Are you sure you want to unlock these items?")) {
            return;
        }

        const ids = $selected.map((i, el) => el.name.match(/ids_to_unlock\[(.*?)\]/)[1]).get();
        console.log(ids);
        const $form = $(`<form method="post" action="${Config.urls.unlocks.addByStock}">`);
        $('<input type="hidden" name="_token" value="' + $('meta[name="csrf-token"]').attr('content') + '">').appendTo($form);
        for (let id of ids) {
            $(`<input type="hidden" name="ids[]" value="${id}">`).appendTo($form);
        }
        $form.appendTo('body').submit();
    }

    salesOtherAccountName()
    {
    	var recycler = this.$otherRecyclerInput.val();
    	if(recycler === 'Music Magpie') {
    		this.$accountNameInput.removeClass('hide');
	    } else {
    		this.$accountNameInput.addClass('hide');
	    }
    }

    summaryBatchCustomerButton()
    {
    	var customer_id = this.$summaryBatchCustomerId.val();

    	if(customer_id) {
    		this.$summaryBatchCreateSaleButton.show();
	    } else {
		    this.$summaryBatchCreateSaleButton.hide();
	    }

    }

    checkSummaryBatchSubmit(event)
    {
	    var id = $(event.target).attr('id');
	    console.log(id);
	    if(id !== 'summary-batch-create-sale-button') {
	    	console.log("error");
		    return false;
	    } else {
	    	console.log("correct");
            this.$summaryBatchForm.unbind('submit');
			this.$summaryBatchCreateSaleButton.prop('disabled', true);
			this.$summaryBatchCreateSaleButton.hide();
            this.$summaryBatchForm.submit();
		}

    }

    summaryCustomerLoad()
    {
		var id = this.$summaryCustomerLoadInput.val();
		console.log(id);
		if(!id) return false;
	    $("input[name=customer_external_id]").val(id);
	    this.$customerFormWrapper.html('<img src="' + url('/img/ajax-loader.gif') + '">');
	    this.getCustomerDetails(id, true).then(this.showCustomerDetails).then(this.summaryBatchCustomerButton);
    }

    summarySelectAlltoUnlock()
    {
	    var $checkboxes = $('input[name$="unlock]"]', this.$summaryForm);
	    $checkboxes.prop('checked', true);
    }

    setAllItemsPrice()
    {
		var price = this.$allItemsPrice.val();
		var inputs = $('input[name$="[price]"]').val(price);
    }

	cacheDom()
	{
		this.$queryForm = $('#universal-search-form');
		this.$queryInput = $('#item-search');
		this.$itemsWrapper = $('#universal-table-wrapper');
		this.$paginationWrapper = $('#stock-pagination-wrapper');
		this.$table = $('#sales');
		this.$tableRows = $('> tbody > tr', this.$table);
		this.$tableStatusTds = $('td[data-creation-status]', this.$tableRows);
		this.$customerFields = $('.customer-field');
		this.$customerFormWrapper = $('#customer-fieldset');
		this.$summaryForm = $('#sale-summary-form');
		this.$customerModifiedField = $('[name=customer_modified]', this.$summaryForm);
		this.$customerFields = $('#sale-summary-form .customer-field');
		this.$statusForm = $('#sale-status-form');
		this.$saleCreatedAlert = $('#sale-created');
		this.$saleCreatedAlertAmount = $('.amount', this.$saleCreatedAlert);
		this.$trackingNumberModal = $('#tracking-number-modal');
		this.$manualSaleModal = $('#manual-sale-modal');
		this.$manualSaleForm = $('#manual-sale-form');
		this.$manualSaleUserSearch = $('.user-search', this.$manualSaleForm);
		this.$manualSaleUserId = $('[name=user_id]', this.$manualSaleForm);
		this.$createSaleManualButton = $('#create-sale-manual');
		this.$salesUnlockButton = $('#sales-unlock-button');
		this.$summaryOtherForm = $('#sale-summary-other-form');
		this.$otherRecyclerInput = $('[name=recycler]', this.$summaryOtherForm);
		this.$accountNameInput = $('#sale-summary-other-form #account-name');
		this.$summaryBatchForm = $('.summary-batch-form');
		this.$summaryBatchCustomerId = $('#summary-batch-customer-id');
		this.$summaryBatchCreateSaleButton = $('#summary-batch-create-sale-button');
		this.$summaryCustomerLoadInput = $('#summary-customer-load-input');
		this.$summaryCustomerLoadButton = $('#summary-customer-load-button');
		this.$summarySelectAllToUnlockButton = $('#summary-select-all-to-unlock-button');
		this.$allItemsPrice = $('#all-items-price');
		this.$allItemsPriceButton = $('#all-items-price-button');
		this.$summaryFormSubmitButton = $('#summary-form-submit-button', this.$summaryForm);
	}

	bindMethods()
	{
		this.cancel = this.cancel.bind(this);
		this.search = this.search.bind(this);
		this.load = this.load.bind(this);
		this.intervalForStatusCheck = this.intervalForStatusCheck.bind(this);
		this.showCustomerDetails = this.showCustomerDetails.bind(this);
		this.checkCustomerModified = this.checkCustomerModified.bind(this);
		this.changeStatus = this.changeStatus.bind(this);
		this.customerSelected = this.customerSelected.bind(this);
		this.customerAuctionSelected = this.customerAuctionSelected.bind(this);
		this.showTrackingNumberModal = this.showTrackingNumberModal.bind(this);
		this.saveTrackingNumber = this.saveTrackingNumber.bind(this);
		this.createSaleManual = this.createSaleManual.bind(this);
		this.auctionUserCheck =  this.auctionUserCheck.bind(this);
		this.checkPaymentComplete = this.checkPaymentComplete.bind(this);
		this.salesUnlock = this.salesUnlock.bind(this);
		this.salesOtherAccountName = this.salesOtherAccountName.bind(this);
		this.summaryBatchCustomerButton = this.summaryBatchCustomerButton.bind(this);
		this.checkSummaryBatchSubmit = this.checkSummaryBatchSubmit.bind(this);
		this.summaryCustomerLoad = this.summaryCustomerLoad.bind(this);
		this.summarySelectAlltoUnlock = this.summarySelectAlltoUnlock.bind(this);
		this.setAllItemsPrice = this.setAllItemsPrice.bind(this);
	}

	bindEvents()
	{
		this.intervalForStatusCheck();
		this.auctionUserCheck();
		this.$queryForm.submit(() => false);
		this.$summaryBatchForm.submit(this.checkSummaryBatchSubmit);
		this.$summaryBatchCreateSaleButton.click(this.checkSummaryBatchSubmit);
		this.$queryInput.keyup(this.search);
		this.$queryForm.on('change keyup', this.search);
		this.$summaryForm.submit(this.checkCustomerModified);
		this.$summaryBatchCustomerId.change(this.summaryBatchCustomerButton);
		this.$itemsWrapper.on('click', '.change-status', this.changeStatus);
		this.$itemsWrapper.on('submit', '.cancel-sale', this.cancel);
		this.$itemsWrapper.on('click', '.add-tracking-button', this.showTrackingNumberModal);
		this.$customerFields.on('customer.selected', this.customerSelected);
		this.$statusForm.on('change', () => this.$statusForm.submit());
		this.$trackingNumberModal.on('shown.bs.modal', () => $('[name=number]', this.$trackingNumberModal).focus());
		this.$trackingNumberModal.on('submit', this.saveTrackingNumber);
		this.$createSaleManualButton.click(this.createSaleManual);
		this.paymentCheckInterval = setInterval(this.checkPaymentComplete, 500);
		this.$salesUnlockButton.click(this.salesUnlock);
		this.$otherRecyclerInput.change(this.salesOtherAccountName);
		this.$summaryCustomerLoadButton.click(this.summaryCustomerLoad);
		this.$summarySelectAllToUnlockButton.click(this.summarySelectAlltoUnlock);
		this.$allItemsPriceButton.click(this.setAllItemsPrice);
	}
}
