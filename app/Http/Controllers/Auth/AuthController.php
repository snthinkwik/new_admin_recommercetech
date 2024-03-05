<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\Invoicing;
use App\Events\User\Registered as RegisteredEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserFullRequest;
use App\User;
use App\User\Address;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    protected $redirectPath = '/stock';



    /**
     * Create a new authentication controller instance.
     *
     * @param  \Illuminate\Contracts\Auth\Guard $auth
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;

        $this->middleware('guest', ['except' => ['getLogout', 'postPrevious']]);
    }

    public function redirectPath()
    {
        /*if($this->auth->user()->type == 'user')
            return '/home';*/

        return '/stock';
    }

    public function postPrevious()
    {
        $user = session('users.previous');
        $this->auth->login($user);
        return redirect()->route('admin.users')->with('messages.success', "You're now logged in as $user->full_name.");
    }

    public function getRegister(Request $request)
    {

        $user = $request->id && $request->token
            ? User::unregistered()->with('address')
                ->where('id', $request->id)
                ->where('registration_token', $request->token)
                ->first()
            : null;

        $user = $user ?: new User;

        return view('auth.register', compact('user'));
    }

    public function getLogin(Request $request)
    {

        return view('auth.login');
        if($request->userhash && !Auth::user()) {
            $hash = $request->userhash;
            $user = User::where(\DB::raw('substr(md5(concat(id, email)),1, 32)'), $hash)->first();
            if($user && ($user->type == 'admin')) {
                return view('auth.login');
            }
        }
        //Auth::login(User::first());
        //dd(config('services.trg_uk.url'));
        return \Redirect::away(config('services.trg_uk.url'));
        return redirect()->away(config('services.trg_uk.url'));
    }

    public function postRegister(UserFullRequest $request, Invoicing $invoicing)
    {
        $user = User::withUnregistered()->where('email', $request->email)->first() ?: new User();
        $user->fill($request->except('password'));
        $user->password = bcrypt($request->password);
        $user->email_confirmation = md5(rand());
        $user->registered = true;
        $user->registration_token = null;

        $customer = $invoicing->getCustomers()->where('email', $user->email)->first();
        if ($customer) $user->invoice_api_id = $customer->external_id;

        $user->save();

        if (array_filter($request->address)) {
            $address = new Address(convert_special_characters($request->address));
            $address->user()->associate($user);
            $address->save();
        }

        event(new RegisteredEvent($user));

        $this->auth->login($user);
        return redirect($this->redirectPath());
    }

    public function getEmailConfirm($userId, $code)
    {
        $user = User::findOrFail($userId);
        if ($user->email_confirmation === $code) {
            $user->email_confirmed = true;
            $user->email_confirmation = '';
            $user->save();
            return view('auth.email-confirm', [
                'messageType' => 'success',
                'message' => "Your email has been confirmed.",
            ]);
        }
        elseif ($user->email_confirmed) {
            return view('auth.email-confirm', [
                'messageType' => 'success',
                'message' => "Your email has already been confirmed.",
            ]);
        }
        else {
            return view('auth.email-confirm', [
                'messageType' => 'danger',
                'message' => "The confirmation code doesn't match our records. Please check the email you received.",
            ]);
        }
    }

    public function getPostcode(Request $request)
    {

        if(!$request->postcode || !$request->country || !$request->location) {
            return [
                'status' => 'error',
                'message' => 'Missing Parameter'
            ];
        }

        if($request->country != "United Kingdom" || $request->location != "UK") {
            return [
                'status' => 'error',
                'message' => 'Unable to validate Postcode'
            ];
        }

        $account = config('services.postcode.account');
        $password = config('services.postcode.password');

        $postcode = $request->postcode;

        $URL = "http://ws1.postcodesoftware.co.uk/lookup.asmx/getAddress?account=" . $account . "&password=" . $password . "&postcode=" . $postcode;

        $xml = simplexml_load_file(str_replace(' ','', $URL)); // Removes unnecessary spaces
        $address = [];
        $premise = false;

        If ($xml->ErrorNumber <> 0) // If an error has occured show message
        {
            return [
                'status' => 'error',
                'message' => (string) $xml->ErrorMessage
            ];
        }
        else
        {
            if ($xml->PremiseData <> "" && $premise)
            {

                $chunks = explode (";", $xml->PremiseData); // Splits up premise data

                foreach ($chunks as $v) { // Adds premises to combo box
                    if ($v <> "") {
                        list($organisation, $building , $number) = explode ('|', $v); // Splits premises into organisation, building and number

                        $line = "";
                        if ($organisation <> "") {
                            $line .= $organisation.", ";
                        }
                        if ($building <> "") {
                            $line .= str_replace("/", ", ", $building);
                        }
                        if ($number <> "") {
                            $line .= $number." ";
                        }

                        $line .= $xml->Address1;

                        $address['line1'][] = $line;
                    }
                }
            } else {
                $address['line1'] = (string) $xml->Address1;
            }

            if ($xml->Address2 <> "")
                $address['line2'] = (string) $xml->Address2;

            if ($xml->Address3 <> "")
                $address['address3'] = (string) $xml->Address3;
            if ($xml->Address4 <> "")
                $address['address4'] = (string) $xml->Address4;

            $address['town'] = (string) $xml->Town;
            $address['county'] = (string) $xml->County;

        }

        return response()->json([
            'status' => 'success',
            'address' => $address,
        ]);
    }
    public function postLogin(Request $request) {

        $previousUser = Auth::user();
        $user = \App\Models\User::findOrFail($request->id);
        $this->auth->login($user);
        session(['users.previous' => $previousUser]);
        return redirect('stock')->with('messages.success', "You're now logged in as $user->full_name.");
    }
    public function getLogout()
    {

        $this->auth->logout();

        return redirect(property_exists($this, 'redirectAfterLogout') ? $this->redirectAfterLogout : $this->redirectPath);
    }
}
