@extends('app')

@section('title', 'Quickbooks settings')

@section('content')

    <div class="container">
        @include('admin.settings.nav')
        @include('messages')

        <h3>Quickbooks settings</h3>

        {!! BsForm::open(['route' => 'sales.check-paid']) !!}
        {!! BsForm::groupSubmit('Force Payment Reconciliation') !!}
        {!! BsForm::close() !!}
        <hr>

        {{--		@if (Setting::get('quickbooks.oauth.access_token'))--}}
        {{--			<p>--}}
        {{--				OAuth Access token: {{ substr(Setting::get('quickbooks.oauth.access_token.oauth_token'), 0, 5) . str_repeat('*', 20) }} <br>--}}
        {{--				OAuth Access token secret: {{ substr(Setting::get('quickbooks.oauth.access_token.oauth_token_secret'), 0, 5) . str_repeat('*', 20) }} <br>--}}
        {{--				OAuth Realm ID: {{ Setting::get('quickbooks.oauth.realm_id') }} <br>--}}
        {{--				OAuth Data source: {{ Setting::get('quickbooks.oauth.dataSource') }} <br>--}}
        {{--				Company name: {{ Setting::get('quickbooks.oauth.company_name') }} <br>--}}
        {{--			</p>--}}
        {{--			<p class="text-success">The app is already connected to Quickbooks. Click below if you want to refresh the credentials.</p>--}}
        {{--		@endif--}}

        <hr/>



        @if(Setting::get('quickbooks.oauth2.access_token'))
            OAuth2 AccessToken: {{ substr(Setting::get('quickbooks.oauth2.access_token'), 0, 5).str_repeat('*', 20) }}<br/>
            OAuth2 AccessToken Expires At: {{ Setting::get('quickbooks.oauth2.access_token_expires_at') }}<br/>
            OAuth2 RefreshToken: {{ substr(Setting::get('quickbooks.oauth2.refresh_token'), 0, 5).str_repeat('*', 20) }}<br/>
            OAuth2 RefreshToken Expires At: {{ Setting::get('quickbooks.oauth2.refresh_token_expires_at') }}<br/>
            OAuth2 RealmID: {{ Setting::get('quickbooks.oauth2.realm_id') }}<br/>
        @endif

        <a class="imgLink" href="#" onclick="oauth.loginPopup()"><img src="{{ asset('img/qb_connect.png') }}" width="178" /></a>
        @if(Setting::get('quickbooks.oauth2.access_token'))
            <button  type="button" class="btn btn-success" onclick="apiCall.refreshToken()">Refresh Token</button>
            <hr />

            <pre id="apiCall"></pre>
            <button  type="button" class="btn btn-success" onclick="apiCall.getCompanyInfo()">Get Company Info</button>
        @endif
        <hr />
    </div>

@endsection

@section('scripts')
    {{--<script type="text/javascript" src="//appcenter.intuit.com/Content/IA/intuit.ipp.anywhere.js"></script>
    <script>
        intuit.ipp.anywhere.setup({
             menuProxy: '',
             grantUrl: '{{ route('admin.quickbooks.oauth.start') }}'
        });
    </script>--}}
    <script>
        var url = '{{ $authUrl }}';
        url = url.replace(/&amp;/g,'&');
        var OAuthCode = function(url) {
            this.loginPopup = function (parameter) {
                this.loginPopupUri(parameter);
            }
            this.loginPopupUri = function (parameter) {
                // Launch Popup
                $( '#apiCall' ).html( "<i class='fa fa-spin fa-spinner'></i>" );
                var parameters = "location=1,width=800,height=650";
                parameters += ",left=" + (screen.width - 800) / 2 + ",top=" + (screen.height - 650) / 2;
                var win = window.open(url, 'connectPopup', parameters);
                var pollOAuth = window.setInterval(function () {
                    try {
                        if (win.document.URL.indexOf("code") != -1) {
                            window.clearInterval(pollOAuth);
                            // win.close();
                            location.reload();
                        }
                    } catch (e) {
                        console.log(e)
                    }
                }, 100);
            }
        }
        var apiCall = function() {
            this.getCompanyInfo = function() {
                $( '#apiCall' ).html( "<i class='fa fa-spin fa-spinner'></i>" );
                /*
                AJAX Request to retrieve getCompanyInfo
                 */
                $.ajax({
                    type: "GET",
                    url: "{{ route('admin.quickbooks.oauth.company-info') }}",
                    data: {authUrl: url}
                }).done(function( msg ) {
                    $( '#apiCall' ).html( msg );
                }).fail(function(err) {
                    $('#apiCall').html("Something went wrong");
                });
            }
            this.refreshToken = function() {
                $( '#apiCall' ).html( "<i class='fa fa-spin fa-spinner'></i>" );
                $.ajax({
                    type: "POST",
                    url: "{{ route('admin.quickbooks.oauth.refresh-token') }}",
                }).done(function( msg ) {
                    $( '#apiCall' ).html( msg );
                }).fail(function(err) {
                    $('#apiCall').html("Something went wrong");
                });
            }
        }
        var oauth = new OAuthCode(url);
        var apiCall = new apiCall();
    </script>
@endsection
