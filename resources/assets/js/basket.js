class Basket
{

	constructor()
	{
		this.cacheDom();
		this.bindMethods();
		this.bindEvents();
	}

	refresh()
	{
		$.ajax(Config.urls.basket.getHtml, {
			success: (res) => {
				this.$basketWrapper.html(res.basketHtml);
			}
		});
	}

	getCount()
	{
		return $('#basket-count').data('count');
	}

	empty()
	{
		return confirm("Are you sure you want to empty your basket?");
	}

	toggleAll(event)
	{
		const $button = $(event.target).closest('a');
		const isSelected = $button.data('is-selected');
		$button.data('is-selected', !isSelected);
		if (!isSelected) $button.attr('disabled', true);

		const $checkboxes = $('[name="stock_ids[]"]', this.$itemsWrapper);
		$checkboxes.prop('checked', !isSelected);
		$('span', $button).text(!isSelected ? 'Unselect all' : 'Select all');

		const ids = $checkboxes.map((i, el) => $(el).val()).get();

		$.ajax(Config.urls.basket.toggle, {
			type: 'post',
			data: { ids, in_basket: isSelected ? 0 : 1 },
			success: (res) => {
				this.$basketWrapper.html(res.basketHtml);
				$button.attr('disabled', false);
			}
		});
	}

	toggleBasket(event)
	{
		var $checkbox = $(event.target);

		this.toggleBasketRequest($checkbox.val(), $checkbox.prop('checked')).then((res) => {
			this.$basketWrapper.html(res.basketHtml);
		});
	}

	toggleBasketRequest(stockId, inBasket)
	{
		return new Promise((resolve) => {
			$.ajax(Config.urls.basket.toggle, {
				type: 'post',
				data: { id: stockId, in_basket: inBasket ? 1 : 0 },
				success: (res) => resolve(res)
			});
		});
	}

	cacheDom()
	{
		this.$basketWrapper = $('#basket-wrapper');
		this.$itemsWrapper = $('#stock-items-wrapper');
		this.$singleItemWrapper = $('#stock-single-item-wrapper');
		this.$emptyForm = $('#basket-empty-form');
	}

	bindMethods()
	{
		this.toggleBasketRequest = this.toggleBasketRequest.bind(this);
		this.toggleBasket = this.toggleBasket.bind(this);
		this.empty = this.empty.bind(this);
		this.toggleAll = this.toggleAll.bind(this);
	}

	bindEvents()
	{
		this.$itemsWrapper.on('change', '[name="stock_ids[]"]', this.toggleBasket);
		this.$singleItemWrapper.on('change', '[name="stock_ids[]"]', this.toggleBasket);
		this.$emptyForm.submit(this.empty);
		this.$itemsWrapper.on('click', '#stock-toggle-all', this.toggleAll);
	}
}
