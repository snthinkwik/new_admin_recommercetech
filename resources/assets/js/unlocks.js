class Unlocks
{
	constructor()
	{
		this.bindMethods();
		this.cacheDom();
		this.bindEvents();
		this.initUserAutocomplete();
        this.initAwaitingPayment();
    }

    initAwaitingPayment()
    {
        if (!Data.unlocks.awaitingPaymentId) {
            return;
        }

        const check = () => {
            $.ajax(Config.urls.unlocks.statusCheck, {
                data: { ids: [Data.unlocks.awaitingPaymentId] },
                success: (res) => {
                    if (res[0].status === 'created') {
                        const token = $('meta[name=csrf-token]').attr('content');
                        const $form = $(
                            `<form action="${Config.urls.unlocks.pay}" method="post">
								<input type="hidden" name="id" value="${Data.unlocks.awaitingPaymentId}">
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
        if (!window.paymentCompleteOrder) {
            return;
        }

        clearInterval(this.paymentCheckInterval);
        const token = $('meta[name=csrf-token]').attr('content');
        const $form = $(
            `<form action="${Config.urls.unlocks.paymentComplete}" method="post">
				<input type="hidden" name="_token" value="${token}">
			</form>`
        );
        $form.appendTo('body').submit();
    }

	failUnlockSubmit()
	{
		const reason = this.$failReason.val().trim();
		if (!reason) {
			return alert("Please enter the reason.");
		}

		const $input = $('<input type="hidden" name="reason">');
		$input.val(reason);
		$input.appendTo(this.$failPendingForm);
		this.$failPendingForm.submit();
	}

	failUnlockShowModal(event)
	{
		const $button = $(event.target);
		this.$failPendingForm = $button.closest('form');
		this.$failModal.modal();
	}

	load()
	{
		const formData = this.$searchForm.serialize();
		if (formData === this.lastFormData) {
			return;
		}
		this.lastFormData = formData;

		$.ajax(Config.urls.unlocks.index, {
			data: formData,
			error: APP.ajaxError,
			success: (res) => {
				this.$tableWrapper.html(res.itemsHtml);
				this.$paginationWrapper.html(res.paginationHtml);
				$('[data-toggle=popover]').popover();
				$('[data-toggle=tooltip]').tooltip();
			}
		})
	}

	confirmOperation(event)
	{
		const $button = $(event.target);
		return confirm("Are you user you want to perform this operation: " + $button.val());
	}

	initUserAutocomplete()
	{
		this.$userSearch.autocomplete({
			source: Config.urls.admin.users.autocomplete,
			select: (event, ui) => {
				this.$userId.val(ui.item.value);
				this.$userSearch.val(ui.item.label)
				return false;
			},
			focus: (event, ui) => {
				return false;
			},
		});
	}

	bulkRetry()
	{
		var network = this.$bulkRetryNetwork.val();
		if(!network) {
			alert("Network is required!");
			return;
		}

		const $selected = $('input[name^="ids_to_retry["]:checked');
		if (!$selected.length) {
			return alert("You didn't select anything.");
		}
		else if (!confirm("Are you sure you want to retry these unlocks?")) {
			return;
		}

		const ids = $selected.map((i, el) => el.name.match(/ids_to_retry\[(.*?)\]/)[1]).get();
		console.log(ids);
		const $form = $(`<form method="post" action="${Config.urls.unlocks.bulkRetry}">`);
		$('<input type="hidden" name="_token" value="' + $('meta[name="csrf-token"]').attr('content') + '">').appendTo($form);
		$('<input type="hidden" name="network" value="' + network + '">').appendTo($form);
		for (let id of ids) {
			$(`<input type="hidden" name="ids[]" value="${id}">`).appendTo($form);
		}
		$form.appendTo('body').submit();
	}

	bindMethods()
	{
		this.confirmOperation = this.confirmOperation.bind(this);
		this.load = this.load.bind(this);
		this.failUnlockShowModal = this.failUnlockShowModal.bind(this);
		this.failUnlockSubmit = this.failUnlockSubmit.bind(this);
        this.checkPaymentComplete = this.checkPaymentComplete.bind(this);
        this.bulkRetry = this.bulkRetry.bind(this);
	}

	cacheDom()
	{
		this.$addForm = $('#unlocks-add-form');
		this.$userSearch = $('.user-search', this.$addForm);
		this.$userId = $('[name=user_id]', this.$addForm);
		this.$tableWrapper = $('#unlocks-table-wrapper');
		this.$paginationWrapper = $('#unlocks-pagination-wrapper');
		this.$searchForm = $('#unlock-search');
		this.$failModal = $('#unlock-fail-reason-modal');
		this.$failReason= $('[name=reason]', this.$failModal);
		this.$failSubmit = $('.fail', this.$failModal);
		this.$bulkRetryForm = $('#bulk-retry-form');
		this.$bulkRetryButton = $('#bulk-retry-button', this.$bulkRetryForm);
		this.$bulkRetryNetwork = $('select[name=network]', this.$bulkRetryForm);
	}

	bindEvents()
	{
		this.$tableWrapper.on('click', '.btn.confirmed', this.confirmOperation);
		this.$searchForm.on('change keyup', this.load);
		this.$tableWrapper.on('click', '.btn.fail', () => false); // No submit even if we have error in the other handler.
		this.$tableWrapper.on('click', '.btn.fail', this.failUnlockShowModal);
		this.$failSubmit.click(this.failUnlockSubmit);
		this.$failModal.on('shown.bs.modal', () => this.$failReason.focus());
        this.paymentCheckInterval = setInterval(this.checkPaymentComplete, 500);
        this.$bulkRetryButton.click(this.bulkRetry);
	}
}
