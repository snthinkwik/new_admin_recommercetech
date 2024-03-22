@extends('app')

@section('title', 'Ebay settings')

@section('content')

    <div class="container">
        @include('admin.settings.nav')
        @include('messages')

        <h3>Ebay settings</h3>
        <hr/>

            <h3>Ebay Developer Account 1</h3>
        <small class="text-info">This account use for searching product Mobile Phone(console->ebay->AddDynamicPriceForeBay)</small><br>
            AccessToken: <textarea class="form-control">{{ !is_null($accessToken) ? $accessToken->access_token:'' }}</textarea><br/>
            OAuth2 AccessToken Expires At: {{!is_null($accessToken) ?  \Carbon\Carbon::parse($accessToken->updated_at)->addSecond($accessToken->expires_in):'' }}<br/>
            OAuth2 RefreshToken: <textarea class="form-control">{{!is_null($accessToken)? $accessToken->refresh_token:'' }}</textarea><br/>
            OAuth2 RefreshToken Expires At: {{!is_null($accessToken)? \Carbon\Carbon::now()->addSecond($accessToken->refresh_token_expires_in):'' }}<br/>



        <a class="imgLink" style="border: 1px" href="https://auth.ebay.com/oauth2/authorize?client_id={{config('services.ebay.client_id')}}&response_type=code&redirect_uri={{config('services.ebay.RU_Name')}}&scope=https://api.ebay.com/oauth/api_scope https://api.ebay.com/oauth/api_scope/sell.marketing.readonly https://api.ebay.com/oauth/api_scope/sell.marketing https://api.ebay.com/oauth/api_scope/sell.inventory.readonly https://api.ebay.com/oauth/api_scope/sell.inventory https://api.ebay.com/oauth/api_scope/sell.account.readonly https://api.ebay.com/oauth/api_scope/sell.account https://api.ebay.com/oauth/api_scope/sell.fulfillment.readonly https://api.ebay.com/oauth/api_scope/sell.fulfillment https://api.ebay.com/oauth/api_scope/sell.analytics.readonly https://api.ebay.com/oauth/api_scope/sell.finances https://api.ebay.com/oauth/api_scope/sell.payment.dispute https://api.ebay.com/oauth/api_scope/commerce.identity.readonly https://api.ebay.com/oauth/api_scope/commerce.notification.subscription https://api.ebay.com/oauth/api_scope/commerce.notification.subscription.readonly">
            <img class="btn btn-default" style="border: 4px solid #e4e0e0;
    box-shadow: -1px 4px 7px -2px #f3ecec;" src="{{ asset('img/ebay.png') }}" width="100" height="50" /></a>

            <a href="{{route('refresh.ebay.access-token')}}" class="btn btn-default">Refresh Token</a>
            <hr />
        <hr />


        <h3>Ebay Developer  Account 2</h3>
        <small class="text-info">This account use for searching product Tablet and other Category(console->ebay->AddDynamicPriceForTabletAndComputer)</small><br>
        AccessToken: <textarea class="form-control">{{ !is_null($accessTokenSecond) ? $accessTokenSecond->access_token:'' }}</textarea><br/>
        OAuth2 AccessToken Expires At: {{!is_null($accessTokenSecond) ?  \Carbon\Carbon::parse($accessTokenSecond->updated_at)->addSecond($accessTokenSecond->expires_in):'' }}<br/>
        OAuth2 RefreshToken: <textarea class="form-control">{{!is_null($accessTokenSecond)? $accessTokenSecond->refresh_token:'' }}</textarea><br/>
        OAuth2 RefreshToken Expires At: {{!is_null($accessTokenSecond)? \Carbon\Carbon::now()->addSecond($accessTokenSecond->refresh_token_expires_in):'' }}<br/>




        <a class="imgLink" style="border: 1px" href="https://auth.ebay.com/oauth2/authorize?client_id={{config('services.ebay2.client_id')}}&response_type=code&redirect_uri={{config('services.ebay2.RU_Name')}}&scope=https://api.ebay.com/oauth/api_scope https://api.ebay.com/oauth/api_scope/sell.marketing.readonly https://api.ebay.com/oauth/api_scope/sell.marketing https://api.ebay.com/oauth/api_scope/sell.inventory.readonly https://api.ebay.com/oauth/api_scope/sell.inventory https://api.ebay.com/oauth/api_scope/sell.account.readonly https://api.ebay.com/oauth/api_scope/sell.account https://api.ebay.com/oauth/api_scope/sell.fulfillment.readonly https://api.ebay.com/oauth/api_scope/sell.fulfillment https://api.ebay.com/oauth/api_scope/sell.analytics.readonly https://api.ebay.com/oauth/api_scope/sell.finances https://api.ebay.com/oauth/api_scope/sell.payment.dispute https://api.ebay.com/oauth/api_scope/commerce.identity.readonly https://api.ebay.com/oauth/api_scope/commerce.notification.subscription https://api.ebay.com/oauth/api_scope/commerce.notification.subscription.readonly">
            <img class="btn btn-default" style="border: 4px solid #e4e0e0;
    box-shadow: -1px 4px 7px -2px #f3ecec;" src="{{ asset('img/ebay.png') }}" width="100" height="50" /></a>

        <a href="{{route('refresh.ebay.access-token-second')}}" class="btn btn-default">Refresh Token</a>
        <hr />
        <hr />



        <h3>Ebay Developer  Account 3</h3>
        <small class="text-info">This account use for get ebay Product information (helpers.php-> getEbayProductDetatils())</small><br>
        AccessToken: <textarea class="form-control">{{ !is_null($accessTokenThird) ? $accessTokenThird->access_token:'' }}</textarea><br/>
        OAuth2 AccessToken Expires At: {{!is_null($accessTokenThird) ?  \Carbon\Carbon::parse($accessTokenThird->updated_at)->addSecond($accessTokenThird->expires_in):'' }}<br/>
        OAuth2 RefreshToken: <textarea class="form-control">{{!is_null($accessTokenThird)? $accessTokenThird->refresh_token:'' }}</textarea><br/>
        OAuth2 RefreshToken Expires At: {{!is_null($accessTokenThird)? \Carbon\Carbon::now()->addSecond($accessTokenThird->refresh_token_expires_in):'' }}<br/>

        <a class="imgLink" style="border: 1px" href="https://auth.ebay.com/oauth2/authorize?client_id={{config('services.ebay3.client_id')}}&response_type=code&redirect_uri={{config('services.ebay3.RU_Name')}}&scope=https://api.ebay.com/oauth/api_scope https://api.ebay.com/oauth/api_scope/sell.marketing.readonly https://api.ebay.com/oauth/api_scope/sell.marketing https://api.ebay.com/oauth/api_scope/sell.inventory.readonly https://api.ebay.com/oauth/api_scope/sell.inventory https://api.ebay.com/oauth/api_scope/sell.account.readonly https://api.ebay.com/oauth/api_scope/sell.account https://api.ebay.com/oauth/api_scope/sell.fulfillment.readonly https://api.ebay.com/oauth/api_scope/sell.fulfillment https://api.ebay.com/oauth/api_scope/sell.analytics.readonly https://api.ebay.com/oauth/api_scope/sell.finances https://api.ebay.com/oauth/api_scope/sell.payment.dispute https://api.ebay.com/oauth/api_scope/commerce.identity.readonly https://api.ebay.com/oauth/api_scope/commerce.notification.subscription https://api.ebay.com/oauth/api_scope/commerce.notification.subscription.readonly">
            <img class="btn btn-default" style="border: 4px solid #e4e0e0;
    box-shadow: -1px 4px 7px -2px #f3ecec;" src="{{ asset('img/ebay.png') }}" width="100" height="50" /></a>

        <a href="{{route('refresh.ebay.access-token-third')}}" class="btn btn-default">Refresh Token</a>
        <hr />
        <hr />
        <h3>Ebay Developer  Account 4</h3>
        <small class="text-info">This account use for get ebay Available Stock(helpers.php-> getAvailableStock)</small><br>
        AccessToken: <textarea class="form-control">{{ !is_null($accessTokenForth) ? $accessTokenForth->access_token:'' }}</textarea><br/>
        OAuth2 AccessToken Expires At: {{!is_null($accessTokenForth) ?  \Carbon\Carbon::parse($accessTokenForth->updated_at)->addSecond($accessTokenForth->expires_in):'' }}<br/>
        OAuth2 RefreshToken: <textarea class="form-control">{{!is_null($accessTokenForth)? $accessTokenForth->refresh_token:'' }}</textarea><br/>
        OAuth2 RefreshToken Expires At: {{!is_null($accessTokenForth)? \Carbon\Carbon::now()->addSecond($accessTokenForth->refresh_token_expires_in):'' }}<br/>

        <a class="imgLink" style="border: 1px" href="https://auth.ebay.com/oauth2/authorize?client_id={{config('services.ebay4.client_id')}}&response_type=code&redirect_uri={{config('services.ebay4.RU_Name')}}&scope=https://api.ebay.com/oauth/api_scope https://api.ebay.com/oauth/api_scope/sell.marketing.readonly https://api.ebay.com/oauth/api_scope/sell.marketing https://api.ebay.com/oauth/api_scope/sell.inventory.readonly https://api.ebay.com/oauth/api_scope/sell.inventory https://api.ebay.com/oauth/api_scope/sell.account.readonly https://api.ebay.com/oauth/api_scope/sell.account https://api.ebay.com/oauth/api_scope/sell.fulfillment.readonly https://api.ebay.com/oauth/api_scope/sell.fulfillment https://api.ebay.com/oauth/api_scope/sell.analytics.readonly https://api.ebay.com/oauth/api_scope/sell.finances https://api.ebay.com/oauth/api_scope/sell.payment.dispute https://api.ebay.com/oauth/api_scope/commerce.identity.readonly https://api.ebay.com/oauth/api_scope/commerce.notification.subscription https://api.ebay.com/oauth/api_scope/commerce.notification.subscription.readonly">
            <img class="btn btn-default" style="border: 4px solid #e4e0e0;
    box-shadow: -1px 4px 7px -2px #f3ecec;" src="{{ asset('img/ebay.png') }}" width="100" height="50" /></a>

        <a href="{{route('refresh.ebay.access-token-forth')}}" class="btn btn-default">Refresh Token</a>
        <hr />
        <hr />

    </div>

@endsection

