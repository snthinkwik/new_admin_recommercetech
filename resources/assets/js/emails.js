class Emails
{
	constructor()
	{
		this.bindMethods();
		this.cacheDom();
		this.bindEvents();
		this.lastBody = this.$formBody.val();
		if (this.$previewFrame.length) this.showPreview();
		this.updateStatuses();
		this.addHtmlEditor();
		this.$testSendModalEmail.val(APP.getCookie('emails.test-send.last-recipient'));
		this.showSelectedAttachmentSection();
		if(this.$deliverySummaryChart.length) this.renderDeliverySummaryChart();
	}

	showSelectedAttachmentSection()
	{
		const section = this.$attachmentRadios.filter(':checked').val();
		$('[data-attachment-type]').addClass('hide')
			.filter('[data-attachment-type=' + section + ']')
			.removeClass('hide');
	}

	saveLastTestSendEmail()
	{
		APP.setCookie('emails.test-send.last-recipient', this.$testSendModalEmail.val());
	}

	showTestSendModal()
	{
		this.$testSendModal.modal();
	}

	showSaveDraftModal()
	{
		this.$saveDraftModal.modal();
	}

	testSendSubmit()
	{
		const data = this.getFormData(true);
		data.push({ name: 'recipient', value: this.$testSendModalEmail.val() });

		$.ajax(Config.urls.emails.testSend, {
			type: 'post',
			data,
			success: (res) => {
				alert(typeof res === 'object' ? res.message : res);
				if (typeof res === 'object' && res.status === 'success') {
					this.$testSendModal.modal('hide');
				}
			},
		});
		this.formData = data;
	}

	saveDraftSubmit()
	{
		const data = this.getFormData(true);
		if(!this.$saveDraftModalTitle.val()) {
			alert('Draft Title is required');
			return;
		}
		data.push({ name: 'title', value: this.$saveDraftModalTitle.val() });

		$.ajax(Config.urls.emails.saveDraft, {
			type: 'post',
			data,
			success: (res) => {
				alert(typeof res === 'object' ? res.message : res);
				if (typeof res === 'object' && res.status === 'success') {
					this.$saveDraftModal.modal('hide');
				}
			}
		})
	}

	addHtmlEditor()
	{
		if (this.$formBody.length && !this.$fieldset.prop('disabled')) {
			const bodyId = this.$formBody.attr('id');
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

	updateStatuses()
	{
		let ids = [];
		$('[data-status]', this.$table).each((i, element) => {
			if (['New', 'Sending'].indexOf($(element).data('status')) !== -1) {
				ids.push($(element).closest('tr').data('id'));
			}
		});

		if (!ids.length) {
			return;
		}

		$.ajax(Config.urls.emails.checkStatuses, {
			data: { ids },
			success: (res) => {
				for (let emailData of res) {
					let $td = $('[data-id=' + emailData.email_id + '] td.status');
					$('[data-status]', $td).data('status', emailData.status)
					$td.html(emailData.html);
				}
				setTimeout(this.updateStatuses, 2000);
			},
			error: APP.ajaxError
		});
	}

	/**
	 * @param {bool} asArray Should the data be returned as array.
	 * @returns {mixed} String or array, depending on the asArray parameter.
	 */
	getFormData(asArray)
	{
		// Temporarily enable the fieldset so we can serialize the form. The user won't see it.
		const fieldsetWasDisabled = this.$fieldset.prop('disabled');
		this.$fieldset.prop('disabled', false);
		const data = asArray ? this.$form.serializeArray() : this.$form.serialize();
		this.$fieldset.prop('disabled', fieldsetWasDisabled);
		return data;
	}

	showPreview()
	{
		const data = this.getFormData();
		if (data === this.formData) {
			return;
		}

		if (this.previewXhr) this.previewXhr.abort();

		this.previewXhr = $.ajax(Config.urls.emails.preview, {
			type: 'post',
			data,
			success: (res) => {
				this.$previewFrame.contents().find('html').html(res);
			},
		})
		this.formData = data;
	}

	saveBodyBeforeAddingField()
	{
		this.lastBody = this.$formBody.val();
	}

	addFieldToBody(event)
	{
		const $target = $(event.target);
		CKEDITOR.instances[this.$formBody.attr('id')].insertText($target.data('field'));
		this.showPreview();
	}

	addFieldToSubject(event)
	{
		const $target = $(event.target);
		var dataField = $target.data('field');
		//console.log(dataField);
		//console.log(this.$formSubject.val());
		this.$formSubject.val(this.$formSubject.val() + dataField);
	}

	renderDeliverySummaryChart()
	{
		console.log("delivery summary chart");
		var total = parseInt($('.chart-data-total').data('count'));
		var delivered = parseFloat($('.chart-data-delivered').data('count'));
		var opened = parseFloat($('.chart-data-opened').data('count'));
		var clicked = parseFloat($('.chart-data-clicked').data('count'));
		var failed = parseFloat($('.chart-data-failed').data('count'));
		var spam = parseFloat($('.chart-data-spam').data('count'));

		console.log("total "+total);
		console.log("delivered "+delivered);
		console.log("opened "+opened);
		console.log("clicked "+clicked);
		console.log("failed "+failed);
		console.log("spam "+spam);

		var chart = new CanvasJS.Chart("delivery-summary-chart", {
			animationEnabled: true,
			title: {
				text: "Delivery Summary"
			},
			data: [{
				type: "pie",
				startAngle: 240,
				yValueFormatString: "##0.00\"%\"",
				//indexLabel: "{label} {y}",
				//indexLabelRadius: 0,
				dataPoints: [
					{y: delivered, label: "Delivered"},
					{y: opened, label: "Opened"},
					{y: clicked, label: "Clicked"},
					{y: failed, label: "Failed"},
					{y: spam, label: "Marked as Spam"}
				]
			}]
		});
		chart.render();
	}

	bindMethods()
	{
		this.addFieldToBody = this.addFieldToBody.bind(this);
		this.addFieldToSubject = this.addFieldToSubject.bind(this);
		this.saveBodyBeforeAddingField = this.saveBodyBeforeAddingField.bind(this);
		this.showPreview = this.showPreview.bind(this);
		this.getFormData = this.getFormData.bind(this);
		this.updateStatuses = this.updateStatuses.bind(this);
		this.testSendSubmit = this.testSendSubmit.bind(this);
		this.showTestSendModal = this.showTestSendModal.bind(this);
		this.saveLastTestSendEmail = this.saveLastTestSendEmail.bind(this);
		this.showSelectedAttachmentSection = this.showSelectedAttachmentSection.bind(this);
		this.showSaveDraftModal = this.showSaveDraftModal.bind(this);
		this.saveDraftSubmit = this.saveDraftSubmit.bind(this);
		this.renderDeliverySummaryChart = this.renderDeliverySummaryChart.bind(this);
	}

	cacheDom()
	{
		this.$form = $('#email-create-form');
		this.$fieldset = $('fieldset', this.$form);
		this.$formBody = $('[name=body]', this.$form);
		this.$formFields = $('.field', this.$form);
		this.$subjectFields = $('.subject-field', this.$form);
		this.$formSubject = $('input[name=subject]', this.$form);
		this.$previewFrame = $('#email-preview');
		this.$table = $('#emails-table');
		this.$testSendButton = $('.btn.test-send', this.$form);
		this.$testSendModal = $('#test-send-modal');
		this.$testSendModalSubmit = $('.btn.send', this.$testSendModal);
		this.$testSendModalEmail = $('input[name=email]', this.$testSendModal);
		this.$saveDraftButton = $('.btn.save-draft', this.$form);
		this.$saveDraftModal = $('#save-draft-modal');
		this.$saveDraftModalSubmit = $('.btn.send', this.$saveDraftModal);
		this.$saveDraftModalTitle = $('input[name=title]', this.$saveDraftModal);
		this.$attachmentRadios = $('[name=attachment]', this.$form);
		this.$deliverySummaryChart = $('#delivery-summary-chart');
	}

	bindEvents()
	{
		this.$formBody.on('keyup', this.saveBodyBeforeAddingField);
		this.$formFields.click(this.addFieldToBody);
		this.$subjectFields.click(this.addFieldToSubject);
		this.$form.on('change keyup', this.showPreview);
		setTimeout(this.updateStatuses, 2000);
		this.$testSendModalSubmit.click(this.testSendSubmit);
		this.$testSendButton.click(this.showTestSendModal);
		this.$testSendModal.on('shown.bs.modal', () => this.$testSendModalEmail.focus());
		this.$testSendModalEmail.change(this.saveLastTestSendEmail);
		this.$saveDraftButton.click(this.showSaveDraftModal);
		this.$saveDraftModal.on('shown.bs.modal', () => this.$saveDraftModalTitle.focus());
		this.$saveDraftModalSubmit.click(this.saveDraftSubmit);
		this.$attachmentRadios.change(this.showSelectedAttachmentSection);
	}
}