class StockTake
{
	constructor()
	{
		this.cacheDom();
		this.bindMethods();
		this.bindEvents();
	}

	submitScanner()
	{
		var ref = $('input[name=ref]', this.$scannerForm).val();

		this.$scannerResults.html("<h1><i class='fa fa-spin fa-spinner fa-lg'></i></h1>");

		if (this.loadXhr) this.loadXhr.abort();

		this.loadXhr = $.ajax({
			url: CURRENT_URL,
			data: {ref: ref},
			success: (res) => {
				this.$scannerResults.html(res.message);
				$('input[name=ref]').val('');
			}, error: () => {
				this.$scannerResults.html("<h1 class='text-danger'>Something went wrong.<br/>Please reload the page.</h1>");
				$('input[name=ref]').val('');
			}
		});

		return false;
	}

	cacheDom()
	{
		this.$scannerForm = $('#stock-take-scanner-form');
		this.$scannerResults = $('#stock-take-scanner-results');
	}

	bindMethods()
	{
		this.submitScanner = this.submitScanner.bind(this);
	}

	bindEvents()
	{
		this.$scannerForm.submit(this.submitScanner);
	}
}