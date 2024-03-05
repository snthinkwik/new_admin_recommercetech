class Auth
{
	constructor()
	{
		this.bindMethods();
		this.cacheDom();
		this.bindEvents();
	}


	check()
	{
		var location = this.$location;
		var country = this.$country;
		var postcode = this.$postcode;
		if(!location.val() || !country.val() || !postcode.val()) {
			if(!this.$validatePostcodeWrapper.hasClass('hide')) {
				this.$validatePostcodeWrapper.addClass('hide');
			}
			return;
		}
		if(location.val() === 'UK' && country.val() === 'United Kingdom') {
			this.$validatePostcodeWrapper.removeClass('hide');
		} else if(!this.$validatePostcodeWrapper.hasClass('hide')) {
			this.$validatePostcodeWrapper.addClass('hide');
		}
	}

	validatePostcode()
	{
		var location = this.$location;
		var country = this.$country;
		var postcode = this.$postcode;
		if(!location.val() || !country.val() || !postcode.val()) {
			return;
		}
		if(location.val() !== 'UK' || country.val() !== 'United Kingdom') {
			console.log("Invalid Country and/or location");
			return;
		}

		if(this.$validatePostcodeMessage.hasClass('alert alert-danger')) {
			this.$validatePostcodeMessage.removeClass('alert alert-danger');
			this.$validatePostcodeMessage.text('');
		}

		const formData = this.$registerForm.serialize();
		if (formData === this.lastFormData) {
			return;
		}
		this.lastFormData = formData;

		var data = { postcode: postcode.val(), country: country.val(), location: location.val() };
		$.ajax(Config.urls.auth.postcode, {
				data: data,
				error: APP.ajaxError,
				success: (res) => {
					if(res.status === "success") {
						this.$addressCity.val(res.address.town);
						this.$addressCounty.val(res.address.county);
						this.$addressLine1.val(res.address.line1);
						this.$addressLine2.val(res.address.line2);
					} else {
						this.$validatePostcodeMessage.addClass("alert alert-danger");
						this.$validatePostcodeMessage.text(res.message);
					}
			}
		});

	}

	bindMethods()
	{
		this.check = this.check.bind(this);
		this.validatePostcode = this.validatePostcode.bind(this);
	}

	cacheDom()
	{
		this.$registerForm = $('#register');
		this.$validatePostcodeWrapper = $('#validate-postcode-wrapper');
		this.$validatePostcodeButton = $('#validate-postcode-button');
		this.$validatePostcodeMessage = $('#validate-postcode-message');
		this.$location = $('select[name=location]', this.$registerForm);
		this.$country = $('#address_country', this.$registerForm);
		this.$postcode = $('input[name*=postcode]', this.$registerForm);
		this.$addressLine1 = $('input[name*=line1]', this.$registerForm);
		this.$addressLine2 = $('input[name*=line2]', this.$registerForm);
		this.$addressCity = $('input[name*=city]', this.$registerForm);
		this.$addressCounty = $('input[name*=county]', this.$registerForm);
	}

	bindEvents()
	{
		this.$location.on('change', this.check);
		this.$country.on('change', this.check);
		this.$postcode.on('change keyup', this.check);
		this.$validatePostcodeButton.click(this.validatePostcode);
	}
}