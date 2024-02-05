class EbayRefund
{
    constructor()
    {
        this.loadXhr = null;
        this.searchFormSerialized = null;

        this.cacheDom();
        this.bindMethods();
        this.bindEvents();

    }
    search()
    {
        const serialized = this.$queryForm.serialize();
        if (serialized !== this.searchFormSerialized) {
            this.searchFormSerialized = serialized;
            this.load();
        }
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

        if(this.$queryForm.hasClass('spinner')) {
            $(".universal-loader").show();
            $(".universal-spinner").show();
        }
        this.loadXhr = $.ajax({
            url: CURRENT_URL,
            data: data,
            success: (res) => {
                $(".universal-loader").hide();
                $(".universal-spinner").hide();
                this.$itemsWrapper.html(res.itemsHtml);
                this.$paginationWrapper.html(res.paginationHtml);
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
    cacheDom()
    {
        this.$queryForm = $('#refund-ebay-order-search-form');
        this.$queryInput = $('#refund-ebay-sales-record-search-term');
        this.$querySort = $('#refund-ebay-fee-sort');
        this.$itemsWrapper = $('#refund-ebay-order-items-wrapper');
        this.$paginationWrapper = $('#refund-ebay-order-pagination-wrapper');

        this.checkSort();
    }
    bindMethods()
    {
        this.search = this.search.bind(this);
        this.sort = this.sort.bind(this);
        this.load = this.load.bind(this);
        this.makeSearch = this.makeSearch.bind(this);
    }
    bindEvents()
    {
        this.$queryInput.keyup(this.search);
        this.$queryForm.change(this.search);
        this.$itemsWrapper.on('click', 'th', this.sort);
    }
}