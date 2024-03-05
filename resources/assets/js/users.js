class Users
{
	constructor()
	{
		this.cacheDom();
		this.bindMethods();
		this.bindEvents();
		if (this.$userEmailPreviewFrame.length) this.showPreview();
		this.addHtmlEditor();
		if(this.$customerBalance.length) this.fetchCustomerBalance()

		this.accountSettingsData = this.$accountSettingsForm.serialize();
		this.searchFormSerialized = null;
		this.emailsSearchFormSerialized = null;

        alert(this.$loginForm);
		if (this.$loginForm && this.$loginForm[0]) {
			this.$loginForm[0].email.focus();
		}
	}

	registerFormSubmit()
	{
		$(':submit', this.$registerForm).attr('disabled', true).closest('div').find('.hide').removeClass('hide');
	}

	login(event)
	{
		var name = $(event.target).closest('tr').find('td.name').text().replace(/^\s+|\s+$/, '');
		return confirm(`You're about to log in as ${name}. Continue?`);
	}

	search()
	{
		const serialized = this.$searchForm.serialize();

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
			data: this.$searchForm.serialize(),
			success: (res) => {
				this.$usersWrapper.html(res.usersHtml);
				this.$paginationWrapper.html(res.paginationHtml);
			}
		});

	}

	searchEmails()
	{
		const serialized = this.$emailsSearchForm.serialize();
		if (serialized !== this.emailsSearchFormSerialized) {
			this.searchFormSerialized = serialized;

			if (this.loadXhr) this.loadXhr.abort();

			this.loadXhr = $.ajax({
				url: CURRENT_URL,
				data: this.$emailsSearchForm.serialize(),
				success: (res) => {
					this.$emailsWrapper.html(res.emailsHtml);
					this.$emailsPaginationWrapper.html(res.emailsPaginationHtml);
				}
			});

		}
	}

	customerSelected(event, id, name)
	{
		var $field = $(event.target);
		$field.val(name).blur();
		$field.parent().find('[name=invoice_api_id]').val(id);
	}

	saveAccountSettings()
	{
		if (this.$accountSettingsForm.serialize() !== this.accountSettingsData) {
			this.$accountSaveResponseWrapper.html('');
			$.ajax(this.$accountSettingsForm.attr('action'), {
				data: this.$accountSettingsForm.serialize(),
				type: 'post',
				success: (res) => {
					if (res.status === 'success') {
						this.$accountSaveResponseWrapper.html(
							`<div class="text-success">${res.message}</div>`
						);
					}
				},
				error: (error) => {
					this.$accountSaveResponseWrapper.html(
						`<div class="text-danger">ERROR - Marketing Emails Settings have not been updated!</div>`
					);
				}
			});
		}
		this.accountSettingsData = this.$accountSettingsForm.serialize();
	}

	deleteUser()
	{
		return confirm('Are you sure you want to delete this user?');
	}

	addHtmlEditor()
	{
		if (this.$userEmailFormBody.length && !this.$userEmailFieldset.prop('disabled')) {
			const bodyId = this.$userEmailFormBody.attr('id');
			if (!bodyId) {
				throw "Body textarea has to have an id for it to work with CKEditor!";
			}

			CKEDITOR.replace(bodyId, {
				removePlugins: 'sourcearea, elementspath',
				extraPlugins: 'colorbutton',
			});

			CKEDITOR.instances[bodyId].on('change', () => {
				CKEDITOR.instances[bodyId].updateElement();
				this.showPreview();
			});
		}
	}

	showPreview()
	{
		const data = this.getFormData();
		if (data === this.userEmailFormData) {
			return;
		}

		if (this.previewXhr) this.previewXhr.abort();

		this.previewXhr = $.ajax(Config.urls.admin.users.emails.preview, {
			type: 'post',
			data, //$('[name=user_id]', this.$userEmailForm),
			success: (res) => {
				this.$userEmailPreviewFrame.contents().find('html').html(res);
			},
		})
		this.userEmailFormData = data;
	}

	getFormData(asArray)
	{
		// Temporarily enable the fieldset so we can serialize the form. The user won't see it.
		const fieldsetWasDisabled = this.$userEmailFieldset.prop('disabled');
		this.$userEmailFieldset.prop('disabled', false);
		const data = asArray ? this.$userEmailForm.serializeArray() : this.$userEmailForm.serialize();
		this.$userEmailFieldset.prop('disabled', fieldsetWasDisabled);
		return data;
	}

	saveBodyBeforeAddingField()
	{
		this.lastBody = this.$userEmailFormBody.val();
	}

	addFieldToBody(event)
	{
		const $target = $(event.target);
		CKEDITOR.instances[this.$userEmailFormBody.attr('id')].insertText($target.data('field'));
		this.showPreview();
	}

	showSelectedAttachmentSection()
	{
		const section = this.$attachmentRadios.filter(':checked').val();
		$('[data-attachment-type]').addClass('hide')
			.filter('[data-attachment-type=' + section + ']')
			.removeClass('hide');
	}

	fetchCustomerBalance()
	{
		var spinner = '<span id="customer-balance-spinner"><i class="fa fa-spinner fa-lg fa-spin"></i> Fetching Customer\'s Balance</span>';
		this.$customerBalance.append(spinner);
		this.$customerBalanceButton.remove();

		$.ajax({
			url: CURRENT_URL,
			success: (res) => {
				$('#customer-balance-spinner').remove();
				if(res.balance) {
					this.$customerBalance.append(res.balance)
				} else {
					this.$customerBalance.append('<b class="text-danger"><i class="fa fa-warning"></i> Something went wrong! <i class="fa fa-warning"></i></b>')
				}

			}, error: function() {
				this.$customerBalance.append('<b><i class="fa fa-warning"></i> Something went wrong! <i class="fa fa-warning"></i></b>');
			}
		});

	}

	whatsAppUsersRemoveFromList(event)
	{

		var btn = $(event.target);

		if(btn.hasClass('active')) return;

        btn.addClass('active');
        btn.addClass('btn-success');
        btn.removeClass('btn-primary');

        var id = btn.data('user-id');
        console.log(id);
        if(id === undefined) return;
        if (this.loadXhr) this.loadXhr.abort();

        this.loadXhr = $.ajax({
            url: Config.urls.admin.users.whatsAppUsersAdded,
			type: 'post',
            data: { id: id },
            success: (res) => {
            	console.log(res);
                var row = $(event.target).closest('tr');
                this.$whatsAppUsersAdded.animate({fontSize: "120%"});
                var usersAdded = parseInt(this.$whatsAppUsersAdded.html());

                this.$whatsAppUsersAdded.html(usersAdded+1);
                row.addClass('bg-success');
                setTimeout(function(){
                    row.fadeOut('slow');
                }, 500);
                this.$whatsAppUsersAdded.animate({fontSize: "100%"});
            }
        });


	}

	sendReminders()
	{
		var users = $('[name^="users["]:checked').get();

        if(!users.length) {
        	alert("Nothing Selected");
        	return;
        }

        const $form = $(`<form method="post" action="${Config.urls.admin.users.customersWithBalanceReminders}">`);
        $('<input type="hidden" name="_token" value="' + $('meta[name="csrf-token"]').attr('content') + '">').appendTo($form);
        $.each(users, function(x, user) {
            $(`<input type="hidden" name="${user.name}" value="${user.value}">`).appendTo($form);
        });
        $form.appendTo('body').submit();
	}

	balanceHide()
	{
        var users = $('[name^="users["]:checked').get();

        if(!users.length) {
            alert("Nothing Selected");
            return;
        }

        const $form = $(`<form method="post" action="${Config.urls.admin.users.customersWithBalanceHide}">`);
        $('<input type="hidden" name="_token" value="' + $('meta[name="csrf-token"]').attr('content') + '">').appendTo($form);
        $.each(users, function(x, user) {
            $(`<input type="hidden" name="${user.name}" value="${user.value}">`).appendTo($form);
        });
        $form.appendTo('body').submit();
	}

	cacheDom()
	{
		this.$table = $('#users-table');
		this.$accountSettingsForm = $('#account-settings-form');
		this.$accountSaveResponseWrapper = $('.response', this.$accountSettingsForm);
		this.$customerFields = $('#user-form .customer-field');
		this.$loginForm = $('#login');
		this.$searchForm = $('#user-search-form');
		this.$searchTerm = $('#user-search-term');
		this.$usersWrapper = $('#users-wrapper');
		this.$paginationWrapper = $('#users-pagination-wrapper');
		this.$registerForm = $('#register');
		this.$userEmailForm = $('#email-user-create-form');
		this.$userEmailFieldset = $('fieldset', this.$userEmailForm);
		this.$userEmailFormBody = $('[name=body]', this.$userEmailForm);
		this.$userEmailFormFields = $('.field', this.$userEmailForm);
		this.$userEmailPreviewFrame = $('#email-user-preview');
		this.$attachmentRadios = $('[name=attachment]', this.$userEmailForm);
		this.$customerBalanceButton = $('#customer-balance-button');
		this.$customerBalance = $('#customer-balance');
		this.$emailsSearchForm = $('#emails-search-form');
		this.$emailsSearchTerm = $('#emails-search-term');
		this.$emailsWrapper = $('#emails-wrapper');
		this.$emailsPaginationWrapper = $('#emails-pagination-wrapper');
		this.$whatsAppUsersWrapper = $('#whatsapp-users-wrapper');
		this.$whatsAppUserRemoveFromList = $('.remove-from-list', this.$whatsAppUsersWrapper);
		this.$whatsAppUsersAdded = $('.users-added', this.$whatsAppUsersWrapper);
		this.$sendRemindersButton = $('#send-reminders-button');
		this.$balanceHideButton = $('#balance-hide-button');
	}

	bindEvents()
	{
		this.$usersWrapper.on('click', '.btn.delete', this.deleteUser);
		this.$usersWrapper.on('click', '.btn.login', this.login);
		this.$accountSettingsForm.on('change', this.saveAccountSettings);
		this.$customerFields.on('customer.selected', this.customerSelected);
		this.$searchForm.submit(() => false);
		this.$searchTerm.keyup(this.search);
		this.$searchForm.change(this.search);
		this.$registerForm.submit(this.registerFormSubmit);
		this.$userEmailForm.on('change keyup', this.showPreview);
		this.$userEmailFormBody.on('keyup', this.saveBodyBeforeAddingField);
		this.$userEmailFormFields.click(this.addFieldToBody);
		this.$attachmentRadios.change(this.showSelectedAttachmentSection);
		this.$customerBalanceButton.click(this.fetchCustomerBalance);
		this.$emailsSearchTerm.keyup(this.searchEmails);
		this.$emailsSearchForm.change(this.searchEmails);
		this.$whatsAppUserRemoveFromList.click(this.whatsAppUsersRemoveFromList);
		this.$sendRemindersButton.click(this.sendReminders);
		this.$balanceHideButton.click(this.balanceHide);
	}

	bindMethods()
	{
		this.saveAccountSettings = this.saveAccountSettings.bind(this);
		this.deleteUser = this.deleteUser.bind(this);
		this.login = this.login.bind(this);
		this.customerSelected = this.customerSelected.bind(this);
		this.search = this.search.bind(this);
		this.registerFormSubmit = this.registerFormSubmit.bind(this);
		this.addFieldToBody = this.addFieldToBody.bind(this);
		this.saveBodyBeforeAddingField = this.saveBodyBeforeAddingField.bind(this);
		this.showPreview = this.showPreview.bind(this);
		this.getFormData = this.getFormData.bind(this);
		this.showSelectedAttachmentSection = this.showSelectedAttachmentSection.bind(this);
		this.fetchCustomerBalance = this.fetchCustomerBalance.bind(this);
		this.searchEmails = this.searchEmails.bind(this);
		this.whatsAppUsersRemoveFromList = this.whatsAppUsersRemoveFromList.bind(this);
		this.sendReminders = this.sendReminders.bind(this);
		this.balanceHide = this.balanceHide.bind(this);
	}
}
