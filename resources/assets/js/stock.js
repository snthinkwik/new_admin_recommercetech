class Stock
{
	constructor()
	{
		this.loadXhr = null;
		this.searchFormSerialized = null;


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

		this.cacheDom();
		this.bindMethods();
		this.bindEvents();
	}

	batchUnlock()
	{
		const $selected = $('input[name^="ids_to_unlock["]:checked');
		if (!$selected.length) {
			return alert("You didn't select anything.");
		}
		else if (!confirm("Are you sure you want to unlock these items?")) {
			return;
		}

		const ids = $selected.map((i, el) => el.name.match(/ids_to_unlock\[(.*?)\]/)[1]).get();
		const $form = $(`<form method="post" action="${Config.urls.unlocks.addByStock}">`);
		$('<input type="hidden" name="_token" value="' + $('meta[name="csrf-token"]').attr('content') + '">').appendTo($form);
		$('<input type="hidden" name="batch" value="y">').appendTo($form);
		for (let id of ids) {
			$(`<input type="hidden" name="ids[]" value="${id}">`).appendTo($form);
		}
		$form.appendTo('body').submit();
	}

	mobicodeCheck()
	{
		this.$mobicodeCheckFormSubmit.prop('disabled', true);
		const $form = this.$mobicodeCheckForm;
		this.$mobicodeCheckFormResult.addClass('loading');

		$.ajax($form.attr('action'), {
			data: $form.serialize(),
			type: $form.attr('method'),
			success: (res) => {
				this.$mobicodeCheckFormSubmit.prop('disabled', false);
				this.$mobicodeCheckFormResult.removeClass('text-warning text-success text-danger loading')
					.addClass(res.text_class)
					.html(res.status_long + (res.error ? '<br>' + res.error : ''));
			}
		});

		return false;
	}

	saveShownTo()
	{
		$.ajax(Config.urls.stock.shownToSave, {
			type: 'post',
			data: this.$shownToModalForm.serialize(),
			success: (res) => {
				this.$shownToModal.modal('hide');
				this.refresh();
				APP.basket.refresh();
				if(res.message) {
					APP.$messagesJs.append(
						'<div class="alert alert-danger" role="alert">\n' +
						'    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">X</span></button>\n' +
						res.message +
						'  </div>'
					);
				}
			}
		});
	}

	showShownToModal()
	{
		if (!APP.basket.getCount()) {
			return alert("You didn't select anything.");
		}

		this.$shownToModal.modal();
		this.$shownToModalCountInfo.text(`You've selected ${APP.basket.getCount()} items. Please select who they should be shown to:`);
	}

	repairStatusChangeBack()
	{
		if (!confirm("Are you sure you want to change the status?")) {
			return;
		}

		var id = $('[name=id]', this.$form).val();
		$.ajax(Config.urls.stock.inRepairChangeBack, {
			type: 'post',
			data: { id },
			success: (res) => {
				location = res.location;
			},
			error: APP.ajaxError,
		});
	}

	clearRepairModal()
	{
		this.$setRepairForm[0].reset();
		this.$setRepairInfo.html('');
	}

	setRepairStatus(event)
	{
		$.ajax(event.target.action, {
			type: 'post',
			data: $(event.target).serialize(),
			success: (res) => {
				if (res.status === 'error') {
					this.$setRepairInfo.html(`<div class="alert alert-danger">${res.message}</div>`);
				}
				else if (res.status === 'success') {
					this.$setRepairInfo.html(`<div class="alert alert-success">${res.message}</div>`);
				}
				else {
					alert("Unexpected server response.");
				}
			},
			error: APP.ajaxError,
		});
	}

	/**
	 * @param {bool} doCheck Should we do iCloud check.
	 */
	receiveBulk(doCheck)
	{
		doCheck = doCheck === undefined ? true : doCheck;
		const ids = this.getSelectedStockIds();
		const itemCount = ids.length;
		if (!ids || !ids.length) {
			return alert("You didn't select anything.");
		}

		this.$receiveBulkModalResults.html('');
		this.$receiveBulkProgressBar.css('width', 0);
		this.$receiveBulkProgressBarText.text('');
		this.$receiveBulkProgressBar.addClass('active');
		this.$receiveBulkModalFooter.addClass('hide');

		const receive = () => {
			if (!ids.length) {
				this.$receiveBulkModalFooter.removeClass('hide');
				this.$receiveBulkProgressBar.removeClass('active');
				return;
			}

			const id = ids.shift();

			$.ajax(Config.urls.stock.receive, {
				type: 'post',
				data: { id, session_flash: 0, skip_check: !doCheck ? 1 : 0 },
				success: (res) => {
					const progress = Math.ceil((itemCount - ids.length) / itemCount * 100);
					this.$receiveBulkProgressBar.css('width', '' + progress + '%');
					this.$receiveBulkProgressBarText.text('' + progress + '%');

					this.$receiveBulkModalResults.append(`<p>${res.ref}</p>`);
					this.$itemCheckboxes.filter(`[value=${id}]`).prop('checked', false).trigger('change');

					if (res.status === 'success') {
						this.$receiveBulkModalResults.append(
							`<p class="text-success">${res.message}</p>`
						)
					}
					else {
						this.$receiveBulkModalResults.append(
							`<p class="text-danger">${res.message}</p>`
						)
					}

					receive();
				}
			});
		};

		receive();

		this.$receiveBulkModal.modal();
	}

	getSelectedStockIds()
	{
		return this.$itemCheckboxes.filter(':checked').map((i, checkbox) => checkbox.value).get();
	}

	receiveStock(event)
	{
		var $form = $(event.target);
		this.$receiveLoading.removeClass('hide');
		this.$receiveButton.attr('disabled', true);
		this.$receiveMessage.html('');

		$.ajax($form.attr('action'), {
			data: $form.serialize(),
			type: 'post',
			success: (res) => {
				if (res.status === 'error') {
					this.$receiveMessage.html(`<div class="alert alert-danger">${res.message}</div>`);
				}
				else {
					location = res.redirect;
				}
				this.$receiveLoading.addClass('hide');
				this.$receiveButton.removeAttr('disabled');
			},
			error: App.ajaxError,
		});
		return false;
	}
	
	setLocationForRef()
	{
		const ref = this.$locationRefField.val();
		const location = this.$locationNameField.val();
		$.ajax(Config.urls.stock.locationSave, {
			type: 'post',
			data: { ref, location },
			success: (res) => {
				if (res.status === 'success') {
					this.$locationResponse.html(
						`<div class="alert alert-success">
							<button type="button" class="close" data-dismiss="alert" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
							${res.message}
						</div>`
					);
					this.$locationRecentActionsHeader.removeClass('hide').after(`<div class="text-success">${res.message}</div>`);
				}
				else {
					this.$locationResponse.html(
						`<div class="alert alert-danger">
							<button type="button" class="close" data-dismiss="alert" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
							${res.message}
						</div>`
					);
					this.$locationRecentActionsHeader.removeClass('hide').after(`<div class="text-danger">${res.message}</div>`);
				}
				
				this.$locationRefField.focus().select();
			},
			error: APP.ajaxError
		});
		return false;
	}
	
	findTrgItem()
	{
		const ref = this.$trgFindItemForm[0].ref.value;
		this.getTrgItem(ref).then(this.showTrgItem);
	}
	
	saveTrgItemButtonClick()
	{
		this.saveTrgItem().
			then((res) => {
				if (res.status === 'success') {
					this.getTrgItem(this.$trgSaveItemRefInput.val(), true).then(this.showTrgItem);
				}
				else {
					let errorHtmlParts = ['<div class="alert alert-danger">'];
					for (let field in res.errors) {
						errorHtmlParts.push(`<h5 class="text-danger">${field}</h5>`);
						for (let i = 0; i < res.errors[field].length; i++) {
							errorHtmlParts.push(`<p class="text-danger">${res.errors[field][i]}</p>`);
						}
					}
					errorHtmlParts.push('</div>');
					this.$trgItemInfo.html(errorHtmlParts.join(''));
				}
			});

		return false;
	}
	
	saveTrgItem()
	{
		return new Promise((resolve) => {
			$.ajax(this.$trgSaveItemForm.attr('action'), {
				type: 'post',
				data: this.$trgSaveItemForm.serialize(),
				success: (res) => resolve(res),
				error: APP.ajaxError,
			});
		})
	}
	
	/**
	 * @param string ref TRG item id.
	 * @param bool justSaved
	 * @returns {Promise}
	 */
	getTrgItem(ref, justSaved)
	{
		return new Promise((resolve) => {
			$.ajax(Config.urls.stock.trgItemImport, {
				data: { ref, just_saved: justSaved },
				success: (res) => resolve(res),
			})
		});
	}
	
	showTrgItem(itemData)
	{
		if (itemData.status === 'success' && itemData.html) {
			this.$trgItemInfo.html(itemData.html);
		}
		else if (itemData.status === 'success' && itemData.message) {
			this.$trgItemInfo.html('<div class="alert alert-success">' + itemData.message + '</div>')
		}
		else {
			this.$trgItemInfo.html('<div class="alert alert-danger">' + itemData.message + '</div>');
		}
		this.cacheDomDynamic();
	}

	createSale()
	{
		var stockIds = this.getSelectedStockIds();

		$.ajax(Config.urls.sales.redirect, {
			data: { ids: stockIds },
			success: (res) => {
				if (res.status === 'success') {
					location = res.url;
				}
				else {
					alert(res.message);
				}
			},
			error: APP.ajaxError,
		});

		return false;
	}

	createBatch()
	{
		var stockIds = this.getSelectedStockIds();
		console.log(stockIds);
		$.ajax(Config.urls.stock.batch, {
			data: { ids: stockIds },
			success: (res) => {
				if (res.status === 'success') {
					location = res.url;
				}
				else {
					alert(res.message);
				}
			},
			error: APP.ajaxError,
		});

	}

	createSaleOtherRecycler()
	{
		var stockIds = this.getSelectedStockIds();
		console.log(stockIds);
        $.ajax(Config.urls.sales.redirect, {
            data: { ids: stockIds, option: "otherRecycler" },
            success: (res) => {
                if (res.status === 'success') {
                    location = res.url;
                }
                else {
                    alert(res.message);
                }
            },
            error: APP.ajaxError,
        });

        return false;
	}
	
	search()
	{
		$(".universal-loader").show();
		const serialized = this.$queryForm.serialize();
		if (serialized !== this.searchFormSerialized) {
			this.searchFormSerialized = serialized;
			this.load();

		}
	}

	searchBatches()
	{
		if (this.loadXhr) this.loadXhr.abort();
		this.loadXhr = $.ajax({
				url: CURRENT_URL,
				data: this.$batchesStatusForm.serialize(),
				success: (res) => {
						this.$batchesWrapper.html(res.itemsHtml);
					}
				});
	}

	sort(event)
	{
		var field = $(event.target);
		$('#sort').val(field.attr('name'));
		$('#sortO').val('DESC');
		if(field.find('i').hasClass('fa-caret-down'))
			$('#sortO').val('ASC');
		else if(field.find('i').hasClass('fa-caret-up')) {
			$('#sortO').val('');
			$('#sort').val('');
		}


		this.search();

	}

	refresh()
	{
		this.load(true);
	}

	load(keepPage)
	{
		if (this.loadXhr) this.loadXhr.abort();

		let data = this.$queryForm.serialize();
		if (keepPage) {
			data += '&page=' + $('.active span', this.$paginationWrapper).text();
		}

		this.loadXhr = $.ajax({
			url: CURRENT_URL,
			data: data,
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
				$(".universal-loader").hide();
				this.$itemsWrapper.html(res.itemsHtml);
				this.$stockCopyWhatsAppItemsWrapper.html(res.copyItemsHtml);
				this.$paginationWrapper.html(res.paginationHtml);
				this.cacheDomDynamic();
				$('[data-toggle=popover]', this.$itemsWrapper).popover();
				if(res.sort) {
					if(res.sortO == 'DESC')
						$('th[name='+res.sort).append("<i class='fa fa-caret-down'></i>");
					else if(res.sortO == 'ASC')
						$('th[name='+res.sort).append("<i class='fa fa-caret-up'></i>");
				}
			}
		});
	}

	checkSort()
	{
		if($(location).attr('search')){
			var searchString = $(location).attr('search');
			var sort = searchString.split('&sort=').pop().split('&').shift();
			var sortO = searchString.split('&sortO=').pop().split('&').shift();
			if(sortO == 'DESC')
				$('#item-sort th[name='+sort).append("<i class='fa fa-caret-down'></i>");
			else if(sortO == 'ASC')
				$('#item-sort th[name='+sort).append("<i class='fa fa-caret-up'></i>");
		}
	}

	changeGradeSelectAll()
	{
		var check = this.$changeGradeSelectAllButton;
		if(check.attr('value') == 'none') {
			$('td.change-grade-checkbox :checkbox').prop('checked', true);
			check.attr('value', 'all');
		} else {
			$('td.change-grade-checkbox :checkbox').prop('checked', false);
			check.attr('value', 'none');
		}
	}

	changeGradeSubmit()
	{
        const $selected = $('input[name^="ids_to_change_grade["]:checked');
        if (!$selected.length) {
            return alert("You didn't select anything.");
        }
        else if (!confirm("Are you sure you want to change grade?")) {
            return;
        }
        const ids = $selected.map((i, el) => el.name.match(/ids_to_change_grade\[(.*?)\]/)[1]).get();
        console.log(ids);
        const $form = $(`<form method="post" action="${Config.urls.stock.changeGrade}">`);
        $('<input type="hidden" name="_token" value="' + $('meta[name="csrf-token"]').attr('content') + '">').appendTo($form);
        $('<input type="hidden" name="grade" value="' + $('#change-grade-grade').val()+ '">').appendTo($form);
        for (let id of ids) {
            $(`<input type="hidden" name="ids[]" value="${id}">`).appendTo($form);
        }
        $form.appendTo('body').submit();
	}

	copyToClipboard()
	{
		var data = this.$batchSummaryTextarea.select();
		try {
			var success = document.execCommand("Copy");
			var msg = success ? 'successful' : 'unsuccessful';
			alert("Copy to clipboard: " + msg);
		} catch (err) {
			alert('Unable to copy');
		}
	}

	createRepair()
	{
		console.log("Create Repair");
		var stockIds = this.getSelectedStockIds();
		console.log(stockIds);
		$.ajax(Config.urls.repairs.redirect, {
			data: { ids: stockIds },
			success: (res) => {
				if (res.status === 'success') {
					location = res.url;
				}
				else {
					alert(res.message);
				}
			},
			error: APP.ajaxError,
		});
	}

	createReturn()
	{
		console.log("Create Return");
		var stockIds = this.getSelectedStockIds();
		console.log(stockIds);
		$.ajax(Config.urls.suppliers.redirect, {
			data: { ids: stockIds },
			success: (res) => {
				if (res.status === 'success') {
					location = res.url;
				}
				else {
					alert(res.message);
				}
			},
			error: APP.ajaxError,
		});
	}

	createCustomerReturn(){

		console.log("Create Customer Return");
		var stockIds = this.getSelectedStockIds();
		console.log(stockIds);
		$.ajax(Config.urls.customers.customerReturn, {
			data: { ids: stockIds },
			success: (res) => {


				if (res.status === 'success') {

					if(res.message){

					}else{
						location = res.url;
					}


				}
				else {
					alert(res.message);
				}
			},
			error: APP.ajaxError,
		});

	}
	getEbayWhatsAppItems()
	{
		var fields = this.$ebayWhatsAppForm.serialize();

		$.ajax(CURRENT_URL, {
			data: fields,
			success: (res) => {
				this.$ebayWhatsAppItemsWrapper.html(res.itemsHtml);
				this.cacheDomDynamic();
			},
			error: APP.ajaxError,
		});

	}

	productSearch()
	{

		var term = this.$stockAssignProductAutocomplete.val();
		console.log(term);
		console.log("working");
		if(term.length < 3)
			return;

		$.ajax(CURRENT_URL, {
			data: {'term':term},
			error: APP.ajaxError,
			success: (res) => {
				var products = $.map(res, function(item){
					return { value: item.product_name+' '+item.slug, product_id: item.id }
				});
				this.$stockAssignProductAutocomplete.autocomplete({
					source: products,
					minLength: 1,
					maxShowItems: 20,
					dataType: "json",
					select: (event, ui) => {
						console.log(ui.item);
						this.$stockAssignProductProductId.val(ui.item.product_id);
					}
				});
			}
		});
	}

	makeSearch()
	{
		var term = this.$formMakeInput.val();

		if(term.length < 2)
			return;

		var makes = Data.stock.productMakes;

		this.$formMakeInput.autocomplete({
			source: makes,
			minLength: 2,
		})
	}

	batchesSendEmail()
	{

		var selectedBatches = $('input[name^="batch_ids["]:checked');
		if(!selectedBatches.length) return;

		const ids = selectedBatches.map((i, el) => el.name.match(/batch_ids\[(.*?)\]/)[1]).get();

		if (!confirm("Are you sure you want to send batches email?")) {
			return;
		}

		const $form = $(`<form method="post" action="${Config.urls.batches.sendBatches}">`);
		$('<input type="hidden" name="_token" value="' + $('meta[name="csrf-token"]').attr('content') + '">').appendTo($form);
		for (let id of ids) {
			$(`<input type="hidden" name="ids[]" value="${id}">`).appendTo($form);
		}
		$form.appendTo('body').submit();
	}

	batchesSelectAll()
	{
		console.log('batches select all');
		const $checkboxes = $('[name^="batch_ids["]');
		$checkboxes.prop('checked', true);
	}

	readyForSaleCopy()
	{
		console.log('copy');
		$("#ready-for-sale-textarea").select();
		try {
			var success = document.execCommand("Copy");
			var msg = success ? 'successful' : 'unsuccessful';
			alert("Copy to clipboard: " + msg);
		} catch (err) {
			alert('Unable to copy' + err);
		}
	}

	readyForSaleSearch()
	{
		var formData = this.$readyForSaleSearchForm.serialize();

		if (this.loadXhr) this.loadXhr.abort();

		var spinner = "<div class='universal-spinner'></div>";

		if(this.$readyForSaleSearchForm.hasClass('spinner')) {
			APP.$universalTableWrapper.prepend(spinner)
		}

		this.loadXhr = $.ajax(CURRENT_URL, {
			data: formData,
			success: (res) => {
				APP.$universalTableWrapper.html(res.itemsHtml);
				APP.$universalPaginationWrapper.html(res.paginationHtml);
				this.cacheDomDynamic();
				this.bindEvents();
			}
		})
	}
	
	readyForSaleUnlockSelected()
	{
		console.log("unlock selected");

		const $selected = $('input[name^="unlock_items["]:checked');
		if (!$selected.length) {
			return alert("You didn't select anything.");
		}
		else if (!confirm("Are you sure you want to unlock these items?")) {
			return;
		}

		console.log("count: " + $selected.length);

		const ids = $selected.map((i, el) => el.name.match(/unlock_items\[(.*?)\]/)[1]).get();
		console.log(ids);
		const $form = $(`<form method="post" action="${Config.urls.unlocks.addByStock}">`);
		$('<input type="hidden" name="_token" value="' + $('meta[name="csrf-token"]').attr('content') + '">').appendTo($form);
		for (let id of ids) {
			$(`<input type="hidden" name="ids[]" value="${id}">`).appendTo($form);
		}
		$form.appendTo('body').submit();
	}
	
	cacheDom()
	{
		this.$createSaleButton = $('#create-sale');
		this.$createSaleButtonBasket = $('#create-sale-basket');
		this.$createBatchButton = $('#create-batch');
		this.$createSaleOtherRecyclerButton = $('#create-sale-other-recycler');
		this.$createRepairButton = $('#create-repair');
		this.$createReturnButton = $('#create-return');
		this.$createCustomerReturnButton = $('#customer-return');
		this.$queryForm = $('#item-search-form');
		this.$queryInput = $('#item-search');
		this.$querySort = $('#item-sort');
		this.$gradeInput = $('#item-search-grade');
		this.$statusInput = $('#item-search-status');
		this.$networkInput = $('#item-search-network');
		this.$unsoldInput = $('#items-unsold');
        this.$touchIdWorking = $('#item-search-touch-id-working');
        this.$crackedBack=$("#item-search-cracked-back");
        this.$itemCondition=$("#item-search-condition");
        this.$itemColour=$("#item-search-colour");
        this.$productType=$("#item-search-product-type");
        this.$itemCapacity=$("#item-search-capacity");
        this.$productMapping=$("#product_mapping");
        this.$testStatus=$("#item-search-test-status");
        this.$cosmeticType=$("#item-search-cosmetic_type");
		this.$mpnMapping=$("#mpa_map");
		this.$itemsWrapper = $('#stock-items-wrapper');
		this.$itemCountry=$("#item-search-country");
		this.$itemVat=$("#item-search-vat");
		this.$paginationWrapper = $('#stock-pagination-wrapper');
		this.$importTrgModal = $('#stock-import-trg');
		this.$trgRefField = $('[name=ref]', this.$importTrgModal);
		this.$trgFindItemForm = $('form', this.$importTrgModal).attr('action', 'javascript:');
		this.$trgItemInfo = $('.item-info', this.$importTrgModal);
		this.$locationConfigForm = $('#location-config');
		this.$locationRefField = $('[name=ref]', this.$locationConfigForm).focus();
		this.$locationNameField = $('[name=location]', this.$locationConfigForm);
		this.$locationResponse = $('#location-response');
		this.$locationRecentActions = $('#location-recent-actions');
		this.$locationRecentActionsHeader = $('h5', this.$locationRecentActions);
		this.$receiveForm = $('#stock-receive');
		this.$receiveMessage = $('#stock-receive-message');
		this.$receiveLoading = $('#stock-receive-loading');
		this.$receiveButton = $(':submit', this.$receiveForm);
		this.$receiveBulkButton = $('#receive-stock-bulk');
		this.$receiveBulkNoCheckButton = $('#receive-stock-bulk-no-check');
		this.$receiveBulkModal = $('#stock-receive-bulk-modal');
		this.$receiveBulkModalFooter = $('.modal-footer', this.$receiveBulkModal);
		this.$receiveBulkProgressBar = $('.progress-bar', this.$receiveBulkModal);
		this.$receiveBulkProgressBarText = $('span', this.$receiveBulkProgressBar);
		this.$receiveBulkModalResults = $('.results', this.$receiveBulkModal);
		this.$setRepairModal = $('#stock-set-in-repair-modal');
		this.$setRepairForm = $('form', this.$setRepairModal);
		this.$setRepairRefField = $('input[name=ref]', this.$setRepairModal);
		this.$setRepairInfo = $('.info', this.$setRepairModal);
		this.$form = $('#stock-form');
		this.$shownToButton = $('#mark-shown-to');
		this.$shownToModal = $('#stock-shown-to-modal');
		this.$shownToModalCountInfo = $('.count-info', this.$shownToModal);
		this.$shownToModalSaveButton = $('.save', this.$shownToModal);
		this.$shownToModalForm = $('form', this.$shownToModal);
		this.$batchesStatusForm = $('#batches-status-form');
		this.$batchesWrapper = $('#batches-wrapper');
		this.$mobicodeCheckForm = $('#mobicode-check');
		this.$mobicodeCheckFormSubmit = $(':submit', this.$mobicodeCheckForm);
		this.$mobicodeCheckFormResult = $('.result', this.$mobicodeCheckForm);
		this.$batchUnlockButton = $('#batch-unlock-selected');
		this.$changeGradeSelectAllButton = $('#change-grade-select-all');
		this.$changeGradeSubmit = $('#change-grade-submit');
		//this.$batchSummaryCopyButton = $('.batch-summary-copy-button');
		//this.$batchSummaryTextarea = $('.batch-summary-textarea');
		this.$ebayWhatsAppForm = $('#ebay-whats-app-form');
		this.$ebayWhatsAppFormButton = $('#get-items', this.$ebayWhatsAppForm);
		this.$ebayWhatsAppItemsWrapper = $('#ebay-whats-app-items-wrapper');
		this.$stockCopyWhatsAppButton = $('.stock-copy-whats-app-button');
		this.$stockCopyWhatsAppItemsWrapper = $('.stock-copy-whats-app-items-list-wrapper');
		this.$stockAssignProductForm = $('#stock-assign-product-form');
		this.$stockAssignProductProductId = $('input[name=product_id]', this.$stockAssignProductForm);
		this.$stockAssignProductAutocomplete = $('input[name=trg_product]', this.$stockAssignProductForm);
		this.$formMakeInput = $('input[name=make]');
		this.$readyForSaleSearchForm = $("#ready-for-sale-search-form");
		this.cacheDomDynamic();
		this.checkSort();
	}

	cacheDomDynamic()
	{
		this.$itemCheckboxes = $('input[name="stock_ids[]"]');
		this.$trgSaveItemForm = $('form', this.$trgItemInfo);
		this.$trgSaveItemRefInput = $('[name=ref]', this.$trgSaveItemForm);
		$('.confirmed').click((event) => {
			return confirm($(event.target).data('confirm'));
		});
		this.$batchSummaryCopyButton = $('.batch-summary-copy-button');
		this.$batchSummaryTextarea = $('.batch-summary-textarea');
		this.$batchesEmailSendButton = $('.batches-email-send-button');
		this.$batchesSelectAllButton = $('.batches-select-all-button');
		this.$readyForSaleCopyButton = $("#ready-for-sale-copy-button");
		this.$readyForSaleUnlockSelectedButton = $("#ready-for-sale-unlock-selected")
	}

	bindMethods()
	{
		this.createSale = this.createSale.bind(this);
		this.createBatch = this.createBatch.bind(this);
		this.createSaleOtherRecycler = this.createSaleOtherRecycler.bind(this);
		this.createRepair = this.createRepair.bind(this);
		this.createReturn = this.createReturn.bind(this);
		this.createCustomerReturn=this.createCustomerReturn.bind(this);
		this.search = this.search.bind(this);
		this.sort = this.sort.bind(this);
		this.findTrgItem = this.findTrgItem.bind(this);
		this.showTrgItem = this.showTrgItem.bind(this);
		this.saveTrgItemButtonClick = this.saveTrgItemButtonClick.bind(this);
		this.saveTrgItem = this.saveTrgItem.bind(this);
		this.setLocationForRef = this.setLocationForRef.bind(this);
		this.receiveStock = this.receiveStock.bind(this);
		this.load = this.load.bind(this);
		this.getSelectedStockIds = this.getSelectedStockIds.bind(this);
		this.receiveBulk = this.receiveBulk.bind(this);
		this.setRepairStatus = this.setRepairStatus.bind(this);
		this.clearRepairModal = this.clearRepairModal.bind(this);
		this.repairStatusChangeBack = this.repairStatusChangeBack.bind(this);
		this.showShownToModal = this.showShownToModal.bind(this);
		this.saveShownTo = this.saveShownTo.bind(this);
		this.searchBatches = this.searchBatches.bind(this);
		this.mobicodeCheck = this.mobicodeCheck.bind(this);
		this.batchUnlock = this.batchUnlock.bind(this);
		this.changeGradeSelectAll = this.changeGradeSelectAll.bind(this);
		this.changeGradeSubmit = this.changeGradeSubmit.bind(this);
		this.copyToClipboard = this.copyToClipboard.bind(this);
		this.getEbayWhatsAppItems = this.getEbayWhatsAppItems.bind(this);
		this.batchesSendEmail = this.batchesSendEmail.bind(this);
		this.productSearch = this.productSearch.bind(this);
		this.batchesSelectAll = this.batchesSelectAll.bind(this);
		this.makeSearch = this.makeSearch.bind(this);
		this.readyForSaleCopy = this.readyForSaleCopy.bind(this);
		this.readyForSaleSearch = this.readyForSaleSearch.bind(this);
		this.readyForSaleUnlockSelected = this.readyForSaleUnlockSelected.bind(this);
	}

	bindEvents()
	{
		this.$createSaleButton.click(this.createSale);
		this.$createSaleButtonBasket.click(this.createSale);
		this.$createBatchButton.click(this.createBatch);
		this.$createSaleOtherRecyclerButton.click(this.createSaleOtherRecycler);
		this.$createRepairButton.click(this.createRepair);
		this.$createReturnButton.click(this.createReturn);
		this.$createCustomerReturnButton.click(this.createCustomerReturn);
		this.$queryForm.submit(() => false);
		this.$queryInput.keyup(this.search);
        this.$touchIdWorking.change(this.search);
        this.$crackedBack.change(this.search);
        this.$productType.change(this.search);
        this.$testStatus.change(this.search);
        this.$cosmeticType.change(this.search);
		this.$mpnMapping.change(this.search);
        this.$gradeInput.change(this.search);
        this.$itemCondition.change(this.search);
        this.$statusInput.change(this.search);
        this.$networkInput.change(this.search);
		this.$unsoldInput.change(this.search);
        this.$itemColour.change(this.search);
        this.$itemCapacity.change(this.search);
        this.$productMapping.change(this.search);
        this.$itemCountry.change(this.search);
        this.$itemVat.change(this.search);
		// this.$queryForm.change(this.search);
		//this.$querySort.on('click', 'th', function(){var elem = $(this); alert(elem.attr('name'))});
		this.$itemsWrapper.on('click', 'th', this.sort);
		this.$importTrgModal.on('shown.bs.modal', () => this.$trgRefField.focus());
		this.$trgFindItemForm.submit(this.findTrgItem);
		this.$trgItemInfo.on('submit', 'form', this.saveTrgItemButtonClick);
		this.$locationConfigForm.submit(this.setLocationForRef);
		this.$receiveForm.on('submit', this.receiveStock);
		this.$receiveBulkButton.click(this.receiveBulk);
		this.$receiveBulkNoCheckButton.click(() => this.receiveBulk(false));
		this.$setRepairModal.on('shown.bs.modal', () => this.$setRepairRefField.focus());
		this.$setRepairModal.on('hide.bs.modal', this.clearRepairModal);
		this.$setRepairForm.submit(() => false);
		this.$setRepairForm.submit(this.setRepairStatus);
		this.$form.on('click', 'a.in-repair-change-back', this.repairStatusChangeBack);
		this.$shownToButton.click(this.showShownToModal);
		this.$shownToModalSaveButton.click(this.saveShownTo);
		this.$batchesStatusForm.change(this.searchBatches);
		this.$mobicodeCheckForm.submit(this.mobicodeCheck);
		this.$batchUnlockButton.click(this.batchUnlock);
		this.$changeGradeSelectAllButton.click(this.changeGradeSelectAll);
		this.$changeGradeSubmit.click(this.changeGradeSubmit);
		this.$batchSummaryCopyButton.click(this.copyToClipboard);
		this.$ebayWhatsAppForm.submit(() => false);
		this.$ebayWhatsAppFormButton.click(this.getEbayWhatsAppItems);
		this.$stockCopyWhatsAppButton.click(this.copyToClipboard);
		this.$batchesEmailSendButton.click(this.batchesSendEmail);
		this.$stockAssignProductAutocomplete.on('change keyup', this.productSearch);
		this.$batchesSelectAllButton.click(this.batchesSelectAll);
		this.$formMakeInput.on('keyup change', this.makeSearch);
		this.$readyForSaleSearchForm.on('keyup change submit dp.submit', this.readyForSaleSearch);
		this.$readyForSaleCopyButton.click(this.readyForSaleCopy);
		this.$readyForSaleUnlockSelectedButton.click(this.readyForSaleUnlockSelected);
	}
}
