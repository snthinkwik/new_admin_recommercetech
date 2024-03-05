class Returns
{
	constructor()
	{
		this.bindMethods();
		this.cacheDom();
		this.bindEvents();
		if(this.$returnsNewForm.length) this.userAutocomplete();
	}

	userAutocomplete()
	{
		console.log('user autocomplete');
		this.$returnsNewFormUserAutocomplete.autocomplete({
			source: Config.urls.admin.users.autocomplete,
			/*change: (event, ui) => {
				this.$returnsNewFormUserId.val(null);
			},*/
			search: (event, ui) => {
				this.$returnsNewFormUserId.val(null);
			},
			select: (event, ui) => {
				console.log(ui.item);
				this.$returnsNewFormUserId.val(ui.item.value);
				this.$returnsNewFormUserAutocomplete.val(ui.item.label);
				return false;
			},
			focus: (event, ui) => {
				return false;
			},
		});
	}


	bindMethods()
	{
		this.userAutocomplete = this.userAutocomplete.bind(this);
	}

	cacheDom()
	{
		this.$returnsNewForm = $('#returns-new-form');
		this.$returnsNewFormUserAutocomplete = $('[name=user_id_autocomplete]', this.$returnsNewForm);
		this.$returnsNewFormUserId = $('[name=user_id]', this.$returnsNewForm)
	}

	bindEvents()
	{
		//this.$returnsNewFormUserAutocomplete.on('change', this.userAutocomplete);	
	}
}