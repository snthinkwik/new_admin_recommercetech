function url(path)
{
	return window.BASE_URL + '/' + (path || '').replace(/^\//, '');
}

if (!String.prototype.trim) {
	String.prototype.trim = function () {
		return this.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g, '');
	};
}

class App
{
	constructor()
	{
        this.loadXhr = null;
		this.cacheDom();
		this.bindMethods();
		this.bindEvents();
		this.initMiscellaneous();
		this.initCustomerSearch();
	}

	setCookie(cname, cvalue, exdays)
	{
		var d = new Date();
		d.setTime(d.getTime() + (exdays*24*60*60*1000));
		var expires = "expires="+ d.toUTCString();
		document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
	}

	getCookie(cname)
	{
		var name = cname + "=";
		var decodedCookie = decodeURIComponent(document.cookie);
		var ca = decodedCookie.split(';');
		for(var i = 0; i <ca.length; i++) {
			var c = ca[i];
			while (c.charAt(0) == ' ') {
				c = c.substring(1);
			}
			if (c.indexOf(name) == 0) {
				return c.substring(name.length, c.length);
			}
		}
		return undefined;
	}
	
	initMiscellaneous()
	{
		$.ajaxSetup({
			headers: {
				'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
			}
		});
		
		$('form').each((i, form) => {
			form.reset(); // When page is refreshed, prevent browser from showing old input that wasn't actually submitted.
		});
		
		$('.show-if-has-error').each((i, element) => {
			if ($('.has-error', element).length) {
				$(element).attr('aria-expanded', 'true').addClass('in');
			}
		});
		
		$('.click-select-all').each((i, element) => {
			$(element).on('click focus', () => $(element).select());
		});
		
		$('.has-datetimepicker').datetimepicker({
			sideBySide: true,
			format: 'YYYY-MM-DD HH:mm',
		});

		$('.has-datepicker').datetimepicker({
			format: 'YYYY-MM-DD',
		});

		$('.has-datemonthpicker').datetimepicker({
			viewMode: 'years',
			format: 'YYYY-MM'
		});

		$('.has-dateyearpicker').datetimepicker({
			viewMode: 'years',
			format: 'YYYY'
		});

		$('.has-datepicker-format').datetimepicker({
			format: 'DD/MM/YY'
		});

		$('[data-toggle=popover]').popover();
		$('[data-toggle=tooltip]').tooltip();

		$('.confirmed').click((event) => {
			return confirm($(event.target).data('confirm'));
		});

		$('#toggle-yes-no').bootstrapToggle({
			on: 'Yes',
			off: 'No'
		});

		$('.toggle-yes-no').bootstrapToggle({
			on: 'Yes',
			off: 'No'
		});

		$(document).ajaxComplete(function() {
			$('[data-toggle=popover]').popover();
			$('[data-toggle=tooltip]').tooltip();
		})
	}

	showCustomerAutocomplete(event)
	{
		$(event.target).autocomplete('search');
	}

	initCustomerSearch()
	{
		this.$customerFields.each((i, field) => {
			const $field = $(field);
			$field.autocomplete({
				source: Data.sales.customers,
				minLength: 0,
				classes: {
					'ui-autocomplete': 'customer-autocomplete',
				},
				select: (event, ui) => {
					$field.trigger('customer.selected', [ui.item.value, ui.item.label])
					return false;
				},
				focus: (event, ui) => {
					return false;
				},
			});
		});
	}
	
	/**
	 * Basic error handler that can be used in $.ajax if you don't expect any specific errors.
	 */
	ajaxError(res)
	{
		alert("An error occurred. Please contact tech support.");
	}

	universalSearch()
	{
		console.log('universal search');
		var formData = this.$universalSearchForm.serialize();

        if (this.loadXhr) this.loadXhr.abort();

		var spinner = "<div class='universal-spinner'></div>";

		if(this.$universalSearchForm.hasClass('spinner')) {
			this.$universalTableWrapper.prepend(spinner)
		}

        this.loadXhr = $.ajax(CURRENT_URL, {
			data: formData,
			success: (res) => {
				this.$universalTableWrapper.html(res.itemsHtml);
				this.$universalPaginationWrapper.html(res.paginationHtml);
			}
		})
	}

	universalSort(event)
	{
		var field = $(event.target);
		var sort = field.data('name');
		var sortO = "DESC";
        if(field.find('i').hasClass('fa-caret-down')) {
            field.find('i.fa-caret-down').remove();
            field.append(" <i class='fa fa-caret-up'></i>")
            sortO = 'ASC';
        } else if(field.find('i').hasClass('fa-caret-up')) {
            field.find('i.fa-caret-up').remove();
            sortO = '';
            sort = '';
        } else {
            field.append(" <i class='fa fa-caret-down'></i>")
		}

		console.log(sort + " " + sortO);

        if (this.loadXhr) this.loadXhr.abort();

        this.loadXhr = $.ajax(CURRENT_URL, {
            data: { sort: sort, sortO: sortO},
            success: (res) => {
				this.$universalSortResult.html(res.resultHtml);
            }
        })

	}

	cacheDom()
	{
		this.$customerFields = $('.customer-field');
		this.$messagesJs = $('#messages-js');
		this.$universalSearchForm = $('#universal-search-form');
		this.$universalTableWrapper = $('#universal-table-wrapper');
		this.$universalPaginationWrapper = $('#universal-pagination-wrapper');
		this.$universalSortRow = $('#universal-sort-row');
		this.$universalSortColumn = $('.sort-column', this. $universalSortRow);
		this.$universalSortResult = $('#universal-sort-result');
	}
	
	bindMethods()
	{
		this.ajaxError = this.ajaxError.bind(this);
		this.initCustomerSearch = this.initCustomerSearch.bind(this);
		this.showCustomerAutocomplete = this.showCustomerAutocomplete.bind(this);
		this.universalSearch = this.universalSearch.bind(this);
		this.universalSort = this.universalSort.bind(this);
	}

	bindEvents()
	{
		this.$customerFields.click(this.showCustomerAutocomplete);
		this.$universalSearchForm.submit(() => false);
		this.$universalSearchForm.on('change keyup submit dp.change', this.universalSearch);
		this.$universalSortColumn.click(this.universalSort);
	}
}

const APP = new App;
APP.users = new Users;
APP.stock = new Stock;
APP.sales = new Sales;
APP.basket = new Basket;
APP.unlocks = new Unlocks;
APP.auth = new Auth;
APP.home = new Home;
APP.emails = new Emails;
APP.parts = new Parts;
APP.stockTake = new StockTake;
APP.returns = new Returns;
APP.ebayorders = new eBayOrders;
APP.ebayfee= new eBayFee;
APP.readyForInvoice= new ReadyForInvoice;
APP.ebayFeeManualAssigned =new eBayFeeManualAssigned;
APP.unassigned = new Unassigned;
APP.dpdInvoice=new DpdInvoice;
APP.ebayRefund=new EbayRefund;
APP.lostitems=new LostItems;
APP.averagePrice= new AveragePrice;

$('.toggle-row').on('click', function(){
	$(this).toggleClass('toggle-icon');
});

$('#ebayFilter').on('change', function(){
	$("#ebay-sales-record-search-term").attr("placeholder",$("#ebayFilter option:selected").html());
});