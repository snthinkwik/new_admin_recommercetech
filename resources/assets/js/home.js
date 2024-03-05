class Home
{
	constructor()
	{
		this.loadXhr = null;
		this.productFormSerialized = null;

		this.bindMethods();
		this.cacheDom();
		this.bindEvents();
		if(this.$tvStatsWrapper.length) setInterval(this.loadTvStats, 15000);
		if(this.$tv2StatsWrapper.length) setInterval(this.loadTv2Stats, 15000);
		if(this.$tv3StatsWrapper.length) setInterval(this.loadTv3Stats, 15000);
		if(this.$tv4StatsWrapper.length) setInterval(this.loadTv4Stats, 30000);
		if(this.$tv5StatsWrapper.length) setInterval(this.loadTv5Stats, 15000);
	}

	search()
	{
		if (this.loadXhr) this.loadXhr.abort();

		var data = this.$productSearchForm.serialize();

		var spinner = '<span id="product-search-spinner" class="text-center"><i class="fa fa-spinner fa-5x fa-spin"></i></span>';
		this.$productSearchSubmitWrapper.append(spinner);

		this.loadXhr = $.ajax({
			url: Config.urls.home.singleSearch,
			data: data,
			success: (res) => {
				$('#product-search-spinner').remove();
				this.$resultWrapper.html(res.resultHtml);
			}
		});
	}

	loadTvStats()
	{
		if (this.loadXhr) this.loadXhr.abort();

		this.loadXhr = $.ajax({
			url: CURRENT_URL,
			success: (res) => {
				this.$tvStatsWrapper.html(res.tvStatsItemsHtml);
			}
		});
	}

	loadTv2Stats()
	{
		if (this.loadXhr) this.loadXhr.abort();

		this.loadXhr = $.ajax({
			url: CURRENT_URL,
			success: (res) => {
				this.$tv2StatsWrapper.html(res.tv2StatsItemsHtml);
			}
		});
	}

	loadTv3Stats()
	{
		if (this.loadXhr) this.loadXhr.abort();

		this.loadXhr = $.ajax({
			url: CURRENT_URL,
			success: (res) => {
				this.$tv3StatsWrapper.html(res.tv3StatsItemsHtml);
			}
		});
	}

	loadTv4Stats()
	{
		if (this.loadXhr) this.loadXhr.abort();

		this.loadXhr = $.ajax({
			url: CURRENT_URL,
			success: (res) => {
				this.$tv4StatsWrapper.html(res.tv4StatsItemsHtml);
			}
		});
	}

	loadTv5Stats()
	{
		if (this.loadXhr) this.loadXhr.abort();

		this.loadXhr = $.ajax({
			url: CURRENT_URL,
			success: (res) => {
				this.$tv5StatsWrapper.html(res.tv5StatsItemsHtml);
			}
		});
	}



	bindMethods()
	{
		this.search = this.search.bind(this);
		this.loadTvStats = this.loadTvStats.bind(this);
		this.loadTv2Stats = this.loadTv2Stats.bind(this);
		this.loadTv3Stats = this.loadTv3Stats.bind(this);
		this.loadTv4Stats = this.loadTv4Stats.bind(this);
		this.loadTv5Stats = this.loadTv5Stats.bind(this);
	}

	cacheDom()
	{
		this.$productSearchForm =  $("#product-search-form");
		this.$productSearchFormSubmit = $('#product-search-submit', this.$productSearchForm);
		this.$resultWrapper = $('#result-wrapper');
		this.$productSearchSubmitWrapper = $('#product-search-submit-wrapper');
		this.$tvStatsWrapper = $('#tv-stats-wrapper');
		this.$tv2StatsWrapper = $('#tv2-stats-wrapper');
		this.$tv3StatsWrapper = $('#tv3-stats-wrapper');
		this.$tv4StatsWrapper = $('#tv4-stats-wrapper');
		this.$tv5StatsWrapper = $('#tv5-stats-wrapper');
	}

	bindEvents()
	{
		this.$productSearchForm.submit(() => false);
		this.$productSearchFormSubmit.click(this.search);
	}
}