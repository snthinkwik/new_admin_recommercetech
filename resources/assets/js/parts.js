class Parts {

    constructor() {
        this.bindMethods();
        this.cacheDom();
        this.bindEvents();


    }
    searchPart() {
        alert("This Part js file");
        var term = this.$partsSearchInput.val();



        $.ajax(Config.urls.parts.search, {
            data: { 'term': term },
            success: (res) => {
                var parts = $.map(res, function (item) {
                    console.log('item:--' + item);

                    if ($.isNumeric(term)) {
                        return { value: item.id + "-" + item.name, data: item.name, lable: "id" }
                    } else {
                        return { value: item.id + "-" + item.name, data: item.id, lable: "name" }
                    }

                });

                this.$partsSearchInput.autocomplete({
                    source: parts,
                    minLength: 1,
                    maxShowItems: 20,
                    select: (event, ui) => {
                        if (ui.item.lable == "name") {
                            var str = ui.item.value.split("-").pop();
                            this.$partsAddFormPartsList.append("<tr><td>" +
                                "" + ui.item.data + "" +
                                "</td><td>" +
                                "" + str + "" +
                                "</td>" +
                                "<td><input type='radio' name='parts[" + ui.item.data + "]' value='RCT' required> RCT  &nbsp;</td>" +
                                "<td><a  class='remove' onclick='$(this).parent().parent().remove();'> <span class='btn-xs btn-danger btn-block confirmed btn btn-primary'> <i class='fa fa-remove'></i></span></a></span></td>" +
                                "</tr>");
                        } else if (ui.item.lable == "id") {
                            var str = ui.item.value.substr(0, ui.item.value.lastIndexOf("-"));
                            this.$partsAddFormPartsList.append("<tr><td>" +
                                "" + str + "" +
                                "</td><td>" +
                                "" + ui.item.data + "" +
                                "</td>" +
                                "<td><input type='radio' name='parts[" + str + "]' value='RCT' required> RCT  &nbsp;</td>" +
                                "<td><a  class='remove' onclick='$(this).parent().parent().remove();'> <span class='btn-xs btn-danger btn-block confirmed btn btn-primary'> <i class='fa fa-remove'></i></span></a></span></td>" +
                                "</tr>");
                        }

                    }
                });
            }
        });

    }
    refresh() {
        this.load(true);
    }
    load(keepPage) {
        if (this.loadXhr) this.loadXhr.abort();
        let data = this.$queryForm.serialize();
        if (keepPage) {
            data += '&page=' + $('.active span', this.$paginationWrapper).text();
        }
        this.loadXhr = $.ajax({
            url: CURRENT_URL,
            data: data,
            success: (res) => {
                this.$itemsWrapper.html(res.itemsHtml);
                this.$paginationWrapper.html(res.paginationHtml);
                $('[data-toggle=popover]', this.$itemsWrapper).popover();
                if (res.sort) {
                    if (res.sortO == 'DESC')
                        $('th[name=' + res.sort).append("<i class='fa fa-caret-down'></i>");
                    else if (res.sortO == 'ASC')
                        $('th[name=' + res.sort).append("<i class='fa fa-caret-up'></i>");


                }
            }
        });
    }
    checkSort() {
        if ($(location).attr('search')) {
            var searchString = $(location).attr('search');
            var sort = searchString.split('&sort=').pop().split('&').shift();
            var sortO = searchString.split('&sortO=').pop().split('&').shift();
            if (sortO == 'DESC')
                $('#item-sort th[name=' + sort).append("<i class='fa fa-caret-down'></i>");
            else if (sortO == 'ASC')
                $('#item-sort th[name=' + sort).append("<i class='fa fa-caret-up'></i>");
        }
    }

    search() {
        const serialized = this.$queryForm.serialize();
        if (serialized !== this.searchFormSerialized) {
            this.searchFormSerialized = serialized;
            this.load();
        }

    }
    makeSearch() {
        var term = this.$formMakeInput.val();
        if (term.length < 2)
            return;

        var makes = Data.stock.productMakes;
        this.$formMakeInput.autocomplete({
            source: makes,
            minLength: 2,
        })
    }
    sort(event) {
        var field = $(event.target);

        $('#sort').val(field.attr('name'));
        $('#sortO').val('DESC');

        if (field.find('i').hasClass('fa-caret-down'))

            $('#sortO').val('ASC');
        else if (field.find('i').hasClass('fa-caret-up')) {
            $('#sortO').val('');
            $('#sort').val('');
        }
        this.search();

    }

    bindMethods() {
        this.searchPart = this.searchPart.bind(this);
        this.search = this.search.bind(this);
        this.sort = this.sort.bind(this);
        this.load = this.load.bind(this);
        this.makeSearch = this.makeSearch.bind(this);
    }

    cacheDom() {
        this.$partsForm = $('#parts-form');
        this.$partsSearchInput = $('#parts-search-input', this.$partsForm);
        this.$partsAddForm = $('#parts-add-form');
        this.$partsAddFormPartsList = $('#parts-add-form-parts-list', this.$partsAddForm);
        this.$createPartsSearchInput = $('.parts-search-input');
        this.$queryForm = $('#part-search-form');
        this.$queryInput = $('#parts-search-term');
        this.$querySort = $('#item-sort');
        this.$paginationWrapper = $('#parts-pagination-wrapper');
        this.$querySort = $('#item-sort');
        this.$itemsWrapper = $('#parts-items-wrapper');
        this.checkSort();

    }

    bindEvents() {
        this.$partsForm.submit(() => false);
        this.$partsSearchInput.on('change keyup', this.searchPart);
        this.$queryInput.keyup(this.search);
        this.$queryForm.change(this.search);
        this.$itemsWrapper.on('click', 'th', this.sort);

    }
}
