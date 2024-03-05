<?php namespace App;

use App\Contracts\Invoicing as InvoicingContract;

use App\Models\User;
use Exception;
use Setting;

abstract class Invoicing implements InvoicingContract {

    /**
     * @var int Minutes until cached items should expire.
     */
    protected $cacheTime = 15;

    public function getRegisteredCustomers($ids = [])
    {
        $knownIds = array_filter(User::distinct('invoice_api_id')->lists('invoice_api_id'));
        $ids = $ids ? array_intersect($knownIds, $ids) : $knownIds;
        return $this->getCustomers($ids);
    }

    public function getRegisteredSelectedCustomers($ids = [])
    {
        if($ids) {
            return $this->getCustomers($ids);
        }
        else {
            $ids = array_filter(User::distinct('invoice_api_id')->pluck('invoice_api_id')->toArray());
            return $this->getCustomers($ids);
        }
    }

    public function getRegisteredCustomersWithBalance()
    {
        $ids = array_filter(User::distinct('invoice_api_id')->lists('invoice_api_id'));

        return $this->getCustomersWithBalance($ids);
    }

    public function getCustomer($id)
    {
        return $this->getCustomers([$id])->first();//->whereLoose('external_id', $id)->first();
    }

    public function getInvoice($id)
    {
        return $this->getInvoices([$id])->first();
    }

    public function setCacheTime($minutes)
    {
        $this->cacheTime = max(0, (int) $minutes);
    }

    public function getDeliveryForUser(User $user)
    {
//		if(Setting::get('free_delivery', false) == true) {
//			return null;
//		} elseif ($user->sales()->where('invoice_status', Invoice::STATUS_OPEN)->count() === 0) {
//			switch($user->location) {
//				case User::LOCATION_UK:
//					return self::DELIVERY_UK;
//				case User::LOCATION_EUROPE:
//					return self::DELIVERY_EUROPE;
//				case User::LOCATION_WORLD:
//					return self::DELIVERY_WORLD;
//			}
//			throw new Exception("User location not in the set of known values.");
//		}
//
//		// If the user has at least one more open invoice, they don't need delivery because the shipping will be combined.
//		return null;



        if(Setting::get('free_delivery', false) == true) {
            return null;
        } else  {
            switch($user->location) {
                case User::LOCATION_UK:
                    return self::DELIVERY_UK;
                case User::LOCATION_EUROPE:
                    return self::DELIVERY_EUROPE;
                case User::LOCATION_WORLD:
                    return self::DELIVERY_WORLD;
            }
            throw new Exception("User location not in the set of known values.");
        }

        // If the user has at least one more open invoice, they don't need delivery because the shipping will be combined.
        return null;


    }

    public function getSaleForUser(User $user)
    {
        switch($user->location) {
            case User::LOCATION_UK:
                return self::SALE_UK;
            case User::LOCATION_EUROPE:
                return self::SALE_EUROPE;
            case User::LOCATION_WORLD:
                return self::SALE_WORLD;
        }

        throw new Exception("User location not in the set of known values.");
    }

}
