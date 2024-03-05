<?php

use App\Error;
use Illuminate\Mail\Message;
use Illuminate\Mail\Transport\MailgunTransport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Stock;
use Money\Money;
use Money\Currency;

use Money\Currencies\ISOCurrencies;
use Money\Parser\DecimalMoneyParser;


/**
 * For instance artisan_call_background('command:subcommand', ['house', 'animal' => 'dog']) would result in this command:
 * php artisan command:subcommand 'house' --animal 'dog'
 * (full absolute path to artisan would be seen in this command)
 * @param $command
 * @param array $argumentsAndOptions
 */
function artisan_call_background($command, $argumentsAndOptions = [])
{
    if ($argumentsAndOptions && !is_array($argumentsAndOptions)) {
        $argumentsAndOptions = [$argumentsAndOptions];
    }

    $cmd = 'php ' . base_path('artisan') . '  ' . $command;
    foreach ($argumentsAndOptions as $k => $param) {
        if (is_int($k)) {
            $cmd .= ' ' . escapeshellarg($param);
        } else {
            $cmd .= ' --' . $k . ' ' . escapeshellarg($param);
        }
    }
    exec("$cmd > /dev/null 2> /dev/null &");
}

function convert_file_encoding($path, $in = 'UTF-8', $out = 'ISO-8859-1')
{
    shell_exec("iconv -f $in -t $out $path > $path.converted");
    unlink($path);
    rename("$path.converted", $path);
}

$progress_start_time;
$progress_longest_msg_length;

function progressReset()
{
    global $progress_start_time;
    $progress_start_time = null;
}

/**
 * @author http://snipplr.com/view/29548/
 * @author Netblink - some small modifications.
 *
 * @param int $done Number of items done (percent will be calculated)
 * @param int $total Number of items total
 * @param int $size Size of the progress bar.
 * @param string $file Path of the file where the progress should be output. Optional.
 */
function progress($done, $total, $currentMsg = null, $size = 30, $file = null, $indent = '')
{
    global $progress_start_time, $progress_longest_msg_length;
    $currentMsg and strlen($currentMsg) > $progress_longest_msg_length and $progress_longest_msg_length = strlen($currentMsg);
    $currentMsg = str_pad($currentMsg, $progress_longest_msg_length);

    // if we go over our bound, just ignore it
    if ($done > $total) {
        return;
    }

    static $last100 = false;
    static $prevDone = false; // It's possible to report the same $done many times, we need to know when that happens.
    if (empty($progress_start_time)) {
        $progress_start_time = microtime(true);
    }
    if (empty($progress_start_time) || !$last100) {
        $last100 = array();
    } // To get better remaining time we'll calculate the rate based just on the last 100 iterations.
    $now = microtime(true);

    $perc = $total ? (double)($done / $total) : 1;

    $bar = floor($perc * $size);

    $status_bar = "\r{$indent}[";
    $status_bar .= str_repeat('=', $bar);
    if ($bar < $size) {
        $status_bar .= '>';
        $status_bar .= str_repeat(' ', $size - $bar);
    } else {
        $status_bar .= '=';
    }

    $disp = number_format($perc * 100, 0);

    $status_bar .= "] $disp% $done/$total";

    $done !== $prevDone and array_push($last100, microtime(true));
    count($last100) > 100 and array_shift($last100);

    $rate = $done ? ($now - $last100[0]) / count($last100) : 0;
    $rate === 0 and $done and $rate = ($now - $progress_start_time) / $done; // In case the tested code is so fast that $last100 keeps giving us a rate of 0.
    $left = $total - $done;
    $eta = floor($rate * $left);
    $prevDone = $done;

    $elapsed = $now - $progress_start_time;

    $eta = intval(floor($eta / 60 / 60)) . 'h' . str_pad(intval(floor($eta / 60)) % 60, 2, '0', STR_PAD_LEFT) . 'm' . str_pad($eta % 60 % 60, 2, '0', STR_PAD_LEFT) . 's';
    $elapsed = intval(floor($elapsed / 60 / 60)) . 'h' . str_pad(intval(floor($elapsed / 60)) % 60, 2, '0', STR_PAD_LEFT) . 'm' . str_pad($elapsed % 60 % 60, 2, '0', STR_PAD_LEFT) . 's';

    $status_bar .= ' remaining: ' . $eta . '. elapsed: ' . $elapsed . '.';
    $currentMsg and $status_bar .= ' [' . preg_replace('/((?<! )[^ ](?= *$))/', '$1]', $currentMsg);

    echo "$status_bar ";

    flush();

    // when done, send a newline
    if ($done == $total) {
        echo "\n";
    }

    if ($file) {
        $info = "$disp% | $done/$total | $eta | $elapsed";
        $currentMsg and $info .= " | $currentMsg";
        file_put_contents($file, $info);
    }
}

/**
 * Checks if the given text is one of common honorifics.
 * @param $text
 * @return bool
 */
function is_honorific($text)
{
    $honorifics = ['mr', 'ms', 'mrs', 'miss', 'sir', 'sire', 'madam', 'lord', 'lady', 'dr', 'professor'];
    $honorificsString = implode('|', $honorifics);
    return !!preg_match("/^\s*($honorificsString)\.?\s*$/i", $text);
}

function ordinal_suffix($number)
{
    $ends = array('th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th');
    return ($number % 100) >= 11 && ($number % 100) <= 13 ? $number . 'th' : $number . $ends[$number % 10];
}

/**
 * DEBUG ONLY - not safe for use in constructing queries that will then be made on the real database, because it does
 * simple, dumb substitutions that may not always work. Also it returns an error message instead of SQL if it detects
 * that something's wrong. Only use it when debugging queries and be aware that in the current form it might or might
 * not work.
 *
 * This function makes a full SQL out of a query builder instance. It takes the bindings and puts them directly in the SQL.
 *
 * @param Illuminate\Database\Eloquent\Builder $builder
 *
 * @return $sql
 */
function full_sql($builder)
{
    $pdo = DB::connection()->getPdo();
    $sql = $builder->toSql();
    $bindings = $builder->getBindings();

    preg_match_all('/\?/', $sql, $m);
    if (count($m[0]) !== count($bindings)) {
        return "The number of placeholders doesn't match the number of bindings.";
    }

    foreach ($bindings as $binding) {
        $pos = strpos($sql, '?');
        $sql = substr_replace($sql, $pdo->quote($binding), $pos, 1);
    }

    return $sql;
}

/**
 * Removes some quirky looking strings ands adds some new lines. Just for debugging.
 *
 * @param string|Illuminate\Database\Query\Builder $sql If it's not a string, the full_sql() function will be used
 *                                                      to conver it to one.
 *
 * @return string
 */
function format_sql($sql)
{
    if (!is_string($sql)) {
        $sql = full_sql($sql);
    }

    $sql = preg_replace("/(?<!\n)(?<!\w)(FROM|((LEFT|INNER) )?JOIN|WHERE|GROUP BY|HAVING|ORDER|LIMIT|\(SELECT|AND)(?!\w)/i", "\n$1", $sql);
    $sql = str_replace(' , ', ', ', $sql);

    return $sql;
}

/**
 * Get available ENUM values.
 * @param string $table
 * @param string $column
 * @param string|null $connection Database connection, if not default.
 * @return array
 */
function enum_values($table, $column, $connection = null)
{
    $pdo = DB::connection()->getPdo();
    $sTable = '`' . trim($pdo->quote($table), "'") . '`';
    $sColumn = $pdo->quote($column);
    $sql = "show columns from $sTable where field = $sColumn";
    $res = $connection ? DB::connection($connection)->select($sql) : DB::select($sql);
    $enumDef = substr($res[0]->Type, 5, -1);
    $enumVals = array_map(function ($a) {
        return trim($a, "'");
    }, explode(',', $enumDef));
    return $enumVals;
}

function alert($message, $recipients = null)
{
    if ($recipients !== null) {
        $recipients = is_array($recipients) ? $recipients : array_map('trim', explode(',', $recipients));
    } else {
        $recipients = array_map('trim', explode(',', config('app.alerts.recipients')));
    }
    $recipients = array_filter($recipients);

    if ($recipients) {
        if (config('app.env') === 'production') {
            $backup = Mail::getSwiftMailer();
            $mailgunForAlerts = new MailgunTransport(
                config('services.mailgun.alerts_secret'), config('services.mailgun.alerts_domain')
            );
            $mailer = new Swift_Mailer($mailgunForAlerts);
            Mail::setSwiftMailer($mailer);
        }

        Mail::raw($message, function (Message $mail) use ($recipients, $message) {
            $mail->subject(str_limit($message, 50));
            $mail->from(config('mail.from.address'), config('mail.from.name'))
                ->to($recipients);
        });

        if (config('app.env') === 'production') {
            Mail::setSwiftMailer($backup);
        }
    }
}

function error_save($key, $message, $alert = true)
{
    Error::create(['key' => $key, 'text' => $message]);
    if ($alert) {
        alert($message);
    }
}

/**
 * Get form HTML but without open and close tag and other things specific to this form.
 * Purpose: get all the fields inside the form for inserting into another form.
 * @param string $formHtml
 * @param string $namePrefix Prefix that should be added to inputs, for instance if we have <input name="surname">
 *                           and $namePrefix is "customer" then the input will be rewritten to <input name="customer[surname]">.
 * @return string
 */
function strip_form($formHtml, $namePrefix = null)
{
    $stripped = preg_replace('#^<form[^>]*>|</form>$#', '', trim($formHtml));
    $stripped = preg_replace('#<input[^>]*name="_token"[^>]*>#', '', $stripped);
    if ($namePrefix) {
        $stripped = preg_replace('#( name=")([^\["]+)#', '$1' . $namePrefix . '[$2]', $stripped);
    }
    return $stripped;
}

/**
 * List values from collection. Unlike $collection->lists('key'), this function allows using multiple keys.
 *
 * In newer Laravel versions things like that can be done with collection macros, but not in 5.0.
 *
 * @param Collection $collection
 * @param array|string $properties
 * @param string $id
 * @return array
 */
function lists(Collection $collection, $properties, $id = null)
{
    $result = [];
    $i = 0;

    foreach ($collection as $item) {
        $k = $id ? $item->$id : $i;
        if (is_array($properties)) {
            $result[$k] = [];
            foreach ($properties as $property) {
                $result[$k][$property] = $item->$property;
            }
        } else {
            $result[$k] = $item->$properties;
        }
        $i++;
    }

    return $result;
}

/**
 * Quickbooks doesn't accept characters like é etc.
 *
 * @param string $string
 * @return string
 */
function convert_special_characters($data)
{
    if (is_array($data)) {
        foreach ($data as &$s) {
            $s = str_replace("&ndash;", "-", $s);
            $s = str_replace("–", "-", $s);
            $s = str_replace("º", "'", $s);
            $s = str_replace("’", "'", $s);
            $s = str_replace("°", "'", $s);
            if (strpos($s = htmlentities($s, ENT_QUOTES, 'UTF-8'), '&') !== false) {
                $s = html_entity_decode(preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|tilde|uml);~i', '$1', $s), ENT_QUOTES, 'UTF-8');
            }
        }
    } else {
        $data = str_replace("&ndash;", "-", $data);
        $data = str_replace("–", "-", $data);
        $data = str_replace("º", "'", $data);
        $data = str_replace("’", "'", $data);
        $data = str_replace("°", "'", $data);
        if (strpos($data = htmlentities($data, ENT_QUOTES, 'UTF-8'), '&') !== false) {
            $data = html_entity_decode(preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|tilde|uml);~i', '$1', $data), ENT_QUOTES, 'UTF-8');
        }
    }

    return $data;
}

if (!function_exists('database_path')) {

    /**
     * Get the database path.
     *
     * @param string $path
     * @return string
     */
    function database_path($path = '')
    {
        return app()->databasePath() . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }

}

function checkUpdatedFields($current, $original)
{
    if ($original == null && $current == '') {
        return true;
    }

    if (!is_numeric($current) || !is_numeric($original)) {
        return false;
    }

    // If one is numeric and one is float, e.g 5 and 5.00
    if (
        (strpos($original, '.') !== false || strpos($current, '.') !== false) &&
        (strpos($original, '.') === false || strpos($current, '.') === false)
    ) {
        if (strpos($original, '.') === false)
            $original .= '.00';
        if (strpos($current, '.') === false)
            $current .= '.00';
    }

    return strcmp((string)$current, (string)$original) === 0;
}

function strpos_array($haystack, $needle, $offset = 0)
{
    if (!is_array($needle))
        $needle = array($needle);
    foreach ($needle as $n) {
        if (strpos($haystack, $n, $offset) !== false)
            return true;
    }
    return false;
}

function array_diff_recursive($arr1, $arr2)
{
    $outputDiff = [];

    foreach ($arr1 as $key => $value) {
        //if the key exists in the second array, recursively call this function
        //if it is an array, otherwise check if the value is in arr2
        if ($arr2 && array_key_exists($key, $arr2)) {
            if (is_array($value)) {
                $recursiveDiff = array_diff_recursive($value, $arr2[$key]);

                if (count($recursiveDiff)) {
                    $outputDiff[$key] = $recursiveDiff;
                }
            } else if (!in_array($value, $arr2)) {
                $outputDiff[$key] = $value;
            }
        }
        //if the key is not in the second array, check if the value is in
        //the second array (this is a quirk of how array_diff works)
        else if ($arr2 && !in_array($value, $arr2)) {
            $outputDiff[$key] = $value;
        }
    }

    return $outputDiff;
}

/* * *
 * @param $cars
 * @param $position
 * @param $keyName
 * @return bool|int|string
 */

function find_price_in_scrape($array, $keyName, $value)
{
    foreach ($array as $index => $single) {
        if ($single[$keyName] == $value)
            return $single['currency_symbol'] . " " . $single['price'];
    }
    return FALSE;
}

function multiPropertySort(Collection $collection, array $sorting_instructions)
{

    return $collection->sort(function ($a, $b) use ($sorting_instructions) {

        //stuff starts here to answer question...

        foreach ($sorting_instructions as $sorting_instruction) {

            $a[$sorting_instruction['column']] = (isset($a[$sorting_instruction['column']])) ? $a[$sorting_instruction['column']] : '';
            $b[$sorting_instruction['column']] = (isset($b[$sorting_instruction['column']])) ? $b[$sorting_instruction['column']] : '';

            if (empty($sorting_instruction['order']) or strtolower($sorting_instruction['order']) == 'asc') {
                $x = ($a[$sorting_instruction['column']] == $b[$sorting_instruction['column']] ? 0 : ($a[$sorting_instruction['column']] < $b[$sorting_instruction['column']] ? -1 : 1));
            } else {
                $x = ($b[$sorting_instruction['column']] == $a[$sorting_instruction['column']] ? 0 : ($b[$sorting_instruction['column']] < $a[$sorting_instruction['column']] ? -1 : 1));
            }

            if ($x != 0) {
                return $x;
            }
        }

        return 0;
    })->values();
}

function getAccessToken($client_id, $client_secret)
{
    $content = "grant_type=client_credentials";
    $authorization = base64_encode("$client_id:$client_secret");
    $header = array("Authorization: Basic {$authorization}", "Content-Type: application/x-www-form-urlencoded");

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.ebay.com/identity/v1/oauth2/token",
        CURLOPT_HTTPHEADER => $header,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $content
    ));
    $response = curl_exec($curl);
    curl_close($curl);

    return json_decode($response);
}

function getOrderData($header, $offset = 1)
{
    $newDate = strtotime('-90 days', strtotime(date('Y-m-d')));
    $finalDate = date('Y-m-j', $newDate);

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.orderhub.io/orders?purchasedAfter=" . $finalDate . "T00:00:00Z&purchasedBefore=" . date('Y-m-d', strtotime("+1 day")) . "T00:00:00Z&page=" . $offset,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_HTTPHEADER => $header,
    ));
    $result = curl_exec($curl);
    if (!$result) {
        die("Connection Failure");
    }
    $data = (array)json_decode($result);

    return $data; //return the results for use
}

function getEbayOrderData($header, $offset)
{
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.ebay.com/sell/fulfillment/v1/order?limit=50&offset=" . $offset,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_HTTPHEADER => $header,
    ));
    $result = curl_exec($curl);
    if (!$result) {
        die("Connection Failure");
    }
    $data = (array)json_decode($result);

    return $data; //return the results for use

}

function getMobileAdvantageData($header, $offset)
{

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.mobileadvantage.co.uk/v1/ext/order?q=&page=" . $offset . "&limit=50&sortMode=desc&sortBy=createdAt&startOrderTime=&endOrderTime=",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_HTTPHEADER => $header,
    ));
    $result = curl_exec($curl);
    if (!$result) {
        die("Connection Failure");
    }
    $data = (array)json_decode($result);

    return $data; //return the results for use

}

function updateIMEINumberMobileAdvantage($header, $data)
{
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.mobileadvantage.co.uk/v1/ext/order/imeiNumber",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_HTTPHEADER => $header,
        CURLOPT_CUSTOMREQUEST => "PUT",
        CURLOPT_POSTFIELDS => $data
    ));
    $result = curl_exec($curl);

    if (!$result) {
        die("Connection Failure");
    }
    $data = (array)json_decode($result);

    return $data; //return the results for use
}

function findEbayProduct($header, $offset, $ean)
{
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.ebay.com/buy/browse/v1/item_summary/search?gtin=" . $ean . "&offset=" . $offset,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_HTTPHEADER => $header,
    ));
    $result = curl_exec($curl);
    if (!$result) {
        die("Connection Failure");
    }
    $data = (array)json_decode($result);

    return $data; //return the results for use

}


function getBackMarketOrderData($header, $offset = 1)
{


    $ua = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.A.B.C Safari/525.13';

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://www.backmarket.fr/ws/orders?page=" . $offset,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_HTTPHEADER => $header,
        CURLOPT_USERAGENT => $ua
    ));

    $result = curl_exec($curl);

    if (!$result) {
        die("Connection Failure");
    }
    $data = (array)json_decode($result);

    return $data; //return the results for use


}


function getBuyBoxData($header, $offset = 1)
{

    $ua = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.A.B.C Safari/525.13';

    $curl = curl_init();

    curl_setopt_array($curl, array(

        CURLOPT_URL => "https://www.backmarket.fr/ws/listings_bi/?page=" . $offset,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_HTTPHEADER => $header,
        CURLOPT_USERAGENT => $ua
    ));

    $result = curl_exec($curl);


    if (!$result) {
        die("Connection Failure");
    }
    $data = (array)json_decode($result);

    if (isset($data['error']->code)) {
        //$this->error($data['error']->message);

        return ['status' => "error", 'message' => $data['error']->message];


    }


    return $data; //return the results for use

}

function getMaxPrice($header, $offset = 1)
{

    $ua = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.A.B.C Safari/525.13';

    $curl = curl_init();

    curl_setopt_array($curl, array(

        CURLOPT_URL => "https://www.backmarket.fr/ws/listings/?page=" . $offset,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_HTTPHEADER => $header,
        CURLOPT_USERAGENT => $ua
    ));

    $result = curl_exec($curl);


    if (!$result) {
        die("Connection Failure");
    }
    $data = (array)json_decode($result);

    if (isset($data['error']->code)) {
        //$this->error($data['error']->message);

        return ['status' => "error", 'message' => $data['error']->message];


    }


    return $data; //return the results for use

}

function getAllProductsFromBackMarket($header, $page = 1)
{
    $ua = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.A.B.C Safari/525.13';

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://www.backmarket.fr/ws/products/?page=" . $page,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
        CURLOPT_HTTPHEADER => $header,
        CURLOPT_USERAGENT => $ua
    ));

    $result = curl_exec($curl);


    if (!$result) {
        die("Connection Failure");
    }
    $data = (array)json_decode($result);

    if (isset($data['error']->code)) {
        //$this->error($data['error']->message);

        return ['status' => "error", 'message' => $data['error']->message];


    }


    return $data; //return the results for use
}

function calculationOfProfit($salePrice, $totalCosts, $vatType, $purchasePrice = null)
{


    if ($vatType === "Standard" && $salePrice) {

        $total_price_ex_value = ($salePrice / 1.2);
        $vat = ($salePrice - $total_price_ex_value);
        return [
            'sale_vat' => $vat,
            'total_price_ex_vat' => $total_price_ex_value,
            'profit' => $total_price_ex_value - $totalCosts,
            'true_profit' => $total_price_ex_value - $totalCosts,
            'marg_vat' => null,

        ];
    } elseif ($salePrice) {
        $margVat = ((($salePrice - $purchasePrice) * 16.67) / 100);

        return [
            'profit' => $salePrice - $totalCosts,
            'marg_vat' => $margVat,
            'true_profit' => ($salePrice - $totalCosts) - $margVat,
            'sale_vat' => null,
            'total_price_ex_vat' => null
        ];

    }
}


function calculationOfProfitEbay($taxRate, $salePrice, $totalCosts, $vatType, $purchasePrice)
{


    if ($vatType === "Standard" && $salePrice) {
        $total_price_ex_value = ($salePrice / ($taxRate + 1));
        $vat = ($salePrice - $total_price_ex_value);

        return [
            'sale_vat' => $vat,
            'total_price_ex_vat' => number_format($total_price_ex_value, 2),
            'profit' => $total_price_ex_value - $totalCosts,
            'true_profit' => $total_price_ex_value - $totalCosts,
            'marg_vat' => null,
            'pp' => $purchasePrice,

        ];
    } elseif ($salePrice) {
        $margVat = ((($salePrice - $purchasePrice) * 16.67) / 100);
        return [
            'profit' => $salePrice - $totalCosts,
            'marg_vat' => $margVat,
            'true_profit' => ($salePrice - $totalCosts) - $margVat,
            'sale_vat' => null,
            'total_price_ex_vat' => null,
            'pp' => $purchasePrice
        ];

    }
}

function getQuickBookServiceProductName($quickBooksCategory, $vat_type, $customerLocation, $platform)
{


    if ($platform === Stock::PLATFROM_MOBILE_ADVANTAGE) {
        if ($vat_type === "Standard") {
            $vat = "Vatable";
        } else {
            $vat = "Marginal";
        }
    } else {
        if ($vat_type === "Standard") {
            $vat = "Vatable";
        } else {
            $vat = "Vat Margin";
        }
    }


    if ($customerLocation === "Europe") {
        $location = 'EU';
    } else {
        $location = $customerLocation;
    }

    if ($platform === Stock::PLATFROM_MOBILE_ADVANTAGE) {
        return $quickBooksCategory . ' ' . "(" . $vat . ")";
    } else {
        return $location . ' ' . $quickBooksCategory . ' ' . "(" . $vat . ")";
    }

}

function getQuickBookServiceProductNameForMobileAdvantage($quickBooksCategory, $vat_type)
{


    if ($vat_type === "Standard") {
        $vat = "Vatable";
    } else {
        $vat = "Marginal";
    }


    return $quickBooksCategory . ' ' . "(" . $vat . ")";
}


function getCheckValidVatType($country, $vatType, $tax_percentage)
{


    if ($country === "United Kingdom") {

        if ($vatType === "Standard" && !($tax_percentage * 100)) {
            return "Invalid VAT Type  can allow the order to progress with manager approval only";
        } else if ($vatType === "Margin" && ($tax_percentage * 100)) {

            return "Invalid VAT Type  can allow the order to progress with manager approval only";
        }

    } else if ($country !== "United Kingdom") {

        if ($vatType === "Marginal" && $tax_percentage * 100) {

            return "Invalid VAT Type  can allow the order to progress with manager approval only";

        }

    }


}

function getCount($id, $type)
{

    return \App\RepairsItems::where('repair_id', $id)->where('type', $type)->count();

}

function getStatus($data)
{

    $flag = 0;
    foreach ($data as $tt) {
        if ($tt->status === \App\RepairsItems::STATUS_OPEN) {
            $flag++;
        }
    }
    return $flag ? \App\RepairsItems::STATUS_OPEN : \App\RepairsItems::STATUS_CLOSE;
}

function getLastDate($data)
{


    $flag = 0;
    $closeDate = '';
    foreach ($data as $tt) {
        if ($tt->status === \App\RepairsItems::STATUS_OPEN) {
            $flag++;
        }
    }
    if (!$flag) {
        foreach ($data as $date) {

            $closeDate = $date->closed_at;
        }
    }


    return $closeDate;


}


function getTotalSellingCost($platform, $total, $country)
{
    $shippingCost = getShippingCost($country, $total, $platform);
    $totalSellCost = $shippingCost;
    return $totalSellCost;

}

function getShippingCost($country, $sales_price, $platform)
{


    $setting = \App\SellerFees::where('platform', $platform)->first();


    if ($country === "UK" || $country === "United Kingdom" || $country === "Great Britain") {

        if ($sales_price < 20) {
            $rate = $setting->uk_shipping_cost_under_20;
            return $rate;
        } else {
            $rate = $setting->uk_shipping_cost_above_20;
            return $rate;
        }
    } else {
        if ($sales_price < 20) {
            $rate = $setting->uk_non_shipping_cost_under_20;
            return $rate;
        } else {
            $rate = $setting->uk_non_shipping_above_under_20;
            return $rate;
        }
    }


}

function getStockDetatils($id)
{

    $stock = Stock::find($id);
    if (is_null($stock)) {
        return null;
    }
    return $stock;
}

function ebayBasicToken($ebayClientId, $ebayClientSecret)
{
    // $authorization = base64_encode(config('services.ebay.client_id').':'.config('services.ebay.client_secret'));
    $authorization = base64_encode($ebayClientId . ':' . $ebayClientSecret);
    $header = array("Authorization: Basic {$authorization}", "Content-Type: application/x-www-form-urlencoded");

    return $header;
}


function getEbayProduct($authToken, $itemId)
{

    $client = new Client();
    $client->setDefaultOption('headers', array('Authorization' => "Bearer {$authToken}"));

    try {
        $response = $client->get("https://api.ebay.com/buy/browse/v1/item/v1|" . $itemId . "|0?fieldgroups=PRODUCT");
        $data = $response->json();


        if (count($data) > 0) {
            $ebayOrderImage = \App\EbayImage::firstOrNew([
                'items_id' => $itemId
            ]);

            $ebayOrderImage->items_id = $itemId;
            $ebayOrderImage->image_path = count($data['image']) > 0 ? $data['image']['imageUrl'] : null;
            $ebayOrderImage->save();


        }
    } catch (Exception $e) {
        return null;
    }


}

function getFindProduct($accessToken, $productName, $categoryId, $key)
{


    $client = new Client();
    $client->setDefaultOption('headers', array('Authorization' => "Bearer {$accessToken}", 'X-EBAY-C-MARKETPLACE-ID' => "EBAY_GB"));


    try {
        $response = $client->get("https://api.ebay.com/buy/browse/v1/item_summary/search?q=" . $productName . " &category_ids=" . $categoryId . "&filter=conditionIds:{" . $key . "},deliveryCountry:GB,itemLocationCountry:GB,buyingOptions:{FIXED_PRICE},price:[20],priceCurrency:GBP&sort=price");
        $data = $response->json();

        return $data;
    } catch (Exception $e) {

        return null;
    }


}


function getFindProductForTablet($accessToken, $productName, $categoryId)
{


    $client = new Client();
    $client->setDefaultOption('headers', array('Authorization' => "Bearer {$accessToken}"));


    try {
        $response = $client->get("https://api.ebay.com/buy/browse/v1/item_summary/search?q=" . $productName . " &category_ids=" . $categoryId . "&filter=deliveryCountry:GB,itemLocationCountry:GB,buyingOptions:{FIXED_PRICE},price:[20],priceCurrency:GBP&auto_correct=KEYWORD&sort=price");
        $data = $response->json();

        return $data;
    } catch (Exception $e) {

        return null;
    }


}


function getCountry($code)
{


    $country = [
        'AD' => 'Andorr',
        "AE" => "United Arab Emirates",
        "AF" => "Afghanistan",
        "AG" => "Antigua and Barbuda",
        "AI" => "Anguilla",
        "AL" => "Albania",
        "AM" => "Armenia",
        "AN" => "Netherlands Antilles",
        "AO" => "Angola",
        "AQ" => "Antarctica",
        "AR" => "Argentina",
        "AS" => "American Samoa",
        "AT" => "Austria.",
        "AU" => "Australia.",
        "AW" => "Aruba.",
        "AZ" => "Azerbaijan.",
        "BA" => "Bosnia and Herzegovina.",
        "BB" => "Barbados.",
        "BD" => "Bangladesh.",
        "BE" => "Belgium.",
        "BF" => "Burkina Faso.",
        "BG" => "Bulgaria.",
        "BH" => "Bahrain.",
        "BI" => "Burundi.",
        "BJ" => "Benin.",
        "BM" => "Bermuda.",
        "BN" => "Brunei Darussalam.",
        "BO" => "Bolivia.",
        "BR" => "Brazil.",
        "BS" => "Bahamas.",
        "BT" => "Bhutan.",
        "BV" => "Bouvet Island.",
        "BW" => "Botswana.",
        "BY" => "Belarus.",
        "BZ" => "Belize.",
        "CA" => "Canada.",
        "CC" => "Cocos (Keeling) Islands.",
        "CD" => "Congo, The Democratic Republic of the.",
        "CF" => "Central African Republic.",
        "CG" => "Congo.",
        "CH" => "Switzerland.",
        "CI" => "Cote d'Ivoire.",
        "CK" => "Cook Islands",
        "CL" => "Chile.",
        "CM" => "Cameroon",
        "CN" => "China",
        "CO" => "Colombia",
        "CR" => "Costa Rica",
        "CU" => "Cuba",
        "CV" => "Cape Verde",
        "CX" => "Christmas Island",
        "CY" => "Cyprus",
        "CZ" => "Czech Republic",
        "DE" => "Germany",
        "DJ" => "Djibouti",
        "DK" => "Denmark",
        "DM" => "Dominica",
        "DO" => "Dominican Republic",
        "DZ" => "Algeria",
        "EC" => "Ecuador",
        "EE" => "Estonia",
        "EG" => "Egypt",
        "EH" => "Western Sahara",
        "ER" => "Eritrea",
        "ES" => "Spain",
        "ET" => "Ethiopia",
        "FI" => "Finland",
        "FJ" => "Fiji",
        "FK" => "Falkland Islands (Malvinas)",
        "FM" => "Federated States of Micronesia",
        "FO" => "Faroe Islands",
        "FR" => "France",
        "GA" => "Gabon",
        "GB" => "United Kingdom",
        "GD" => "Grenada",
        "GE" => "Georgia",
        "GF" => "French Guiana",
        "GG" => "Guernsey",
        "GH" => "Ghana",
        "GI" => "Gibraltar",
        "GL" => "Greenland",
        "GM" => "Gambia",
        "GN" => "Guinea",
        "GP" => "Guadeloupe",
        "GQ" => "Equatorial Guinea",
        "GR" => "Greece",
        "GS" => "South Georgia and the South Sandwich Islands",
        "GT" => "Guatemala",
        "GU" => "Guam",
        "GW" => "Guinea-Bissau",
        "GY" => "Guyana",
        "HK" => "Hong Kong",
        "HM" => "Heard Island and McDonald Islands",
        "HN" => "Honduras",
        "HR" => "Croatia",
        "HT" => "Haiti",
        "HU" => "Hungary",
        "ID" => "Indonesia",
        "IE" => "Ireland",
        "IL" => "Israel",
        "IN" => "India",
        "IO" => "British Indian Ocean Territory",
        "IQ" => "Iraq",
        "IR" => "Islamic Republic of Iran",
        "IS" => "Iceland",
        "IT" => "Italy",
        "JE" => "Jersey",
        "JM" => "Jamaica",
        "JO" => "Jordan",
        "JP" => "Japan",
        "KE" => "Kenya",
        "KG" => "Kyrgyzstan",
        "KH" => "Cambodia",
        "KI" => "Kiribati",
        "KM" => "Comoros",
        "KN" => "Saint Kitts and Nevis",
        "KP" => "Democratic People's Republic of Korea",
        "KR" => "Republic of Korea",
        "KW" => "Kuwait",
        "KY" => "Cayman Islands",
        "KZ" => "Kazakhstan",
        "LA" => "Lao People's Democratic Republic",
        "LB" => "Lebanon",
        "LC" => "Saint Lucia",
        "LI" => "Liechtenstein",
        "LK" => "Sri Lanka",
        "LR" => "Liberia",
        "LS" => "Lesotho",
        "LT" => "Lithuania",
        "LU" => "Luxembourg",
        "LV" => "Latvia",
        "LY" => "Libyan Arab Jamahiriya",
        "MA" => "Morocco",
        "MC" => "Monaco",
        "MD" => "Republic of Moldova",
        "ME" => "Montenegro",
        "MG" => "Madagascar",
        "MH" => "Marshall Islands",
        "MK" => "The Former Yugoslav Republic of Macedonia",
        "ML" => "Mali",
        "MM" => "Myanmar",
        "MN" => "Mongolia",
        "MO" => "Macao",
        "MP" => "Northern Mariana Islands",
        "MQ" => "Martinique",
        "MR" => "Mauritania",
        "MS" => "Montserrat",
        "MT" => "Malta",
        "MU" => "Mauritius",
        "MV" => "Maldives",
        "MW" => "Malawi",
        "MX" => "Mexico",
        "MY" => "Malaysia",
        "MZ" => "Mozambique",
        "NA" => "Namibia",
        "NC" => "New Caledonia",
        "NE" => "Niger",
        "NF" => "Norfolk Island",
        "NG" => "Nigeria",
        "NI" => "Nicaragua",
        "NL" => "Netherlands",
        "NO" => "Norway",
        "NP" => "Nepal",
        "NR" => "Nauru",
        "NU" => "Niue",
        "NZ" => "New Zealand",
        "OM" => "Oman",
        "PA" => "Panama",
        "PE" => "Peru",
        "PF" => "French Polynesia Includes Tahiti",
        "PG" => "Papua New Guinea",
        "PH" => "Philippines",
        "PK" => "Pakistan",
        "PL" => "Poland",
        "PM" => "Saint Pierre and Miquelon",
        "PN" => "Pitcairn",
        "PR" => "Puerto Rico",
        "PS" => "Palestinian territory, Occupied",
        "PT" => "Portugal",
        "PW" => "Palau",
        "PY" => "Paraguay",
        "QA" => "Qatar",
        "RE" => "Reunion",
        "RO" => "Romania",
        "RS" => "Serbia",
        "RU" => "Russian Federation",
        "RW" => "Rwanda",
        "SA" => "Saudi Arabia",
        "SB" => "Solomon Islands",
        "SC" => "Seychelles",
        "SD" => "Sudan",
        "SE" => "Sweden",
        "SG" => "Singapore",
        "SH" => "Saint Helena",
        "SI" => "Slovenia",
        "SJ" => "Svalbard and Jan Mayen",
        "SK" => "Slovakia",
        "SL" => "Sierra Leone",
        "SM" => "San Marino",
        "SN" => "Senegal",
        "SO" => "Somalia",
        "SR" => "Suriname",
        "ST" => "Sao Tome and Principe",
        "SV" => "El Salvador",
        "SY" => "Syrian Arab Republic",
        "SZ" => "Swaziland",
        "TC" => "Turks and Caicos Islands",
        "TD" => "Chad",
        "TF" => "French Southern Territories",
        "TG" => "Togo",
        "TH" => "Thailand",
        "TJ" => "Tajikistan",
        "TK" => "Tokelau",
        "TM" => "Turkmenistan",
        "TN" => "Tunisia",
        "TO" => "Tonga",
        "TP" => "No longer in use",
        "TR" => "Turkey",
        "TT" => "Trinidad and Tobago",
        "TV" => "Tuvalu",
        "TW" => "Taiwan, Province of China",
        "TZ" => "Tanzania, United Republic of",
        "UA" => "Ukraine",
        "UG" => "Uganda",
        "US" => "United States",
        "UY" => "Uruguay",
        "UZ" => "Uzbekistan",
        "VA" => "Holy See (Vatican City state)",
        "VC" => "Saint Vincent and the Grenadines",
        "VE" => "Venezuela",
        "VG" => "Virgin Islands, British",
        "VI" => "Virgin Islands, U.S",
        "VN" => "Vietnam",
        "VU" => "Vanuatu",
        "WF" => "Wallis and Futuna",
        "WS" => "Samoa",
        "YE" => "Yemen",
        "YT" => "Mayotte",
        "ZA" => "South Africa",
        "ZM" => "Zambia",
        "ZW" => "Zimbabwe",
        "ZZ" => "Unknown country",
    ];

    $countryName = '';

    foreach ($country as $key => $value) {

        if ($key === $code) {
            $countryName = $value;

        }
    }

    return $countryName;

}

function getEbayRefreshTokenBaseToken($authorizationHeader, $refreshToken)
{

    $content2 = "grant_type=refresh_token&refresh_token=" . $refreshToken . "&scope=https://api.ebay.com/oauth/api_scope https://api.ebay.com/oauth/api_scope/sell.marketing.readonly https://api.ebay.com/oauth/api_scope/sell.marketing https://api.ebay.com/oauth/api_scope/sell.inventory.readonly https://api.ebay.com/oauth/api_scope/sell.inventory https://api.ebay.com/oauth/api_scope/sell.account.readonly https://api.ebay.com/oauth/api_scope/sell.account https://api.ebay.com/oauth/api_scope/sell.fulfillment.readonly https://api.ebay.com/oauth/api_scope/sell.fulfillment https://api.ebay.com/oauth/api_scope/sell.analytics.readonly https://api.ebay.com/oauth/api_scope/sell.finances https://api.ebay.com/oauth/api_scope/sell.payment.dispute https://api.ebay.com/oauth/api_scope/commerce.identity.readonly https://api.ebay.com/oauth/api_scope/commerce.notification.subscription https://api.ebay.com/oauth/api_scope/commerce.notification.subscription.readonly";


    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.ebay.com/identity/v1/oauth2/token",
        CURLOPT_HTTPHEADER => $authorizationHeader,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $content2
    ));
    $response = curl_exec($curl);


    $data = (array)json_decode($response);


    return $data;


}

function getBackMarketCondition($conditionCode)
{
    switch ($conditionCode) {
        case "10":
            return "Excellent";
            break;
        case "11":
            return "Good";
            break;
        case "12":
            return "Fair";
            break;
        default:
            return false;
    }

}

function getBackMarketConditionAestheticGrade($conditionCode)
{
    switch ($conditionCode) {
        case "1":
            return "Excellent";
            break;
        case "2":
            return "Good";
            break;
        case "3":
            return "Fair";
            break;
        default:
            return false;
    }

}

function getSellerName($productId)
{


    $client = new Client([
            'base_uri' => 'https://www.backmarket.co.uk/',
            'verify' => false,
            'cookie' => true
        ]
    );
    $response = $client->get("https://www.backmarket.co.uk/second-hand-samsung-galaxy-s9-64-gb-carbon-black-unlocked/" . $productId . ".html?offer_type=6#l=10");
    $content = $response->getBody()->getContents();
    $crawler = new Crawler($content);


    $crawler->filter('div>div>.mb-4')->each(function ($node) {

        $sellerName = getBetween($node->text(), 'Refurbished and sold by', 'Reseller since');

        $str = str_replace("\n", "", $sellerName);

        return trim($str);


    });

}


function getBetween($string, $start = "", $end = "")
{
    if (strpos($string, $start)) { // required if $start not exist in $string
        $startCharCount = strpos($string, $start) + strlen($start);
        $firstSubStr = substr($string, $startCharCount, strlen($string));
        $endCharCount = strpos($firstSubStr, $end);
        if ($endCharCount == 0) {
            $endCharCount = strlen($firstSubStr);
        }
        return substr($firstSubStr, 0, $endCharCount);
    } else {
        return '';
    }
}

function getEbayProductDetatils($itemId, $itemUrl, $gra = null)
{


    $apiNetwork = [];
    $condition = '';
    $grade = '';

    $client = new Client();

    //  $data=[];

    $accessToken = \App\AccessToken::where('platform', 'ebay-third')->first();

    $currentTime = \Carbon\Carbon::now();
    $addTime = \Carbon\Carbon::parse($accessToken->updated_at)->addSecond($accessToken->expires_in);

    $BasicHeaders = ebayBasicToken(config('services.ebay3.client_id'), config('services.ebay3.client_secret'));


    if ($currentTime->gt($addTime)) {
        $newAccessToken = getEbayRefreshTokenBaseToken($BasicHeaders, $accessToken->refresh_token);
        $accessToken->access_token = $newAccessToken['access_token'];
        $accessToken->expires_in = $newAccessToken['expires_in'];
        $accessToken->save();
        sleep(5);

    }

    $client->setDefaultOption('headers', array('Authorization' => "Bearer {$accessToken->access_token},'X-EBAY-C-MARKETPLACE-ID'=>EBAY_GB"));

    $productResponse = $client->get($itemUrl);
    $productData = $productResponse->json();

    foreach ($productData['localizedAspects'] as $localized) {
        if ($localized['name'] === "Network") {
            array_push($apiNetwork, str_replace(' ', '', $localized['value']));
        }
    }


    foreach ($productData['localizedAspects'] as $localized) {
        if ($localized['name'] === "Condition") {

            if (strpos($localized['value'], $gra) !== false) {
                //array_push($condition, $localized['value']);
                $condition = $localized['value'];
            }

        }

        if ($localized['name'] === "Grade") {
            if (strpos($localized['value'], $gra) !== false) {
                // array_push($grade, $localized['value']);
                $grade = $localized['value'];
            }
        }
    }


    $network = count($apiNetwork) > 0 ? $apiNetwork[0] : '';
    $networkData = \App\EbayNetwork::firstOrNew([
        'item_id' => $itemId,

    ]);

    if (!is_null($network)) {
        $networkData->item_id = $itemId;
        $networkData->network = $network;
        $networkData->save();
    }


    $data = [
        'network' => $network,
        'condition' => $condition,
        'grade' => $grade
    ];


    return $data;

}


function getAvailableStock($itemUrl)
{


    $client = new Client();

    //  $data=[];

    $accessToken = \App\AccessToken::where('platform', 'ebay-forth')->first();

    $currentTime = \Carbon\Carbon::now();
    $addTime = \Carbon\Carbon::parse($accessToken->updated_at)->addSecond($accessToken->expires_in);

    $BasicHeaders = ebayBasicToken(config('services.ebay4.client_id'), config('services.ebay4.client_secret'));


    if ($currentTime->gt($addTime)) {
        $newAccessToken = getEbayRefreshTokenBaseToken($BasicHeaders, $accessToken->refresh_token);
        $accessToken->access_token = $newAccessToken['access_token'];
        $accessToken->expires_in = $newAccessToken['expires_in'];
        $accessToken->save();
        sleep(5);

    }

    $client->setDefaultOption('headers', array('Authorization' => "Bearer {$accessToken->access_token}", 'X-EBAY-C-MARKETPLACE-ID' => "EBAY_GB"));

    $productResponse = $client->get($itemUrl);
    $productData = $productResponse->json();


    return isset($productData['estimatedAvailabilities'][0]['estimatedAvailableQuantity']) ? $productData['estimatedAvailabilities'][0]['estimatedAvailableQuantity'] : 0;


}

function getProcessor()
{

    return ["AMD A10",
        "AMD A4",
        "AMD A6",
        "AMD A6-3620",
        "AMD A6-7310",
        "AMD A8",
        "AMD A9",
        "AMD A-Serie",
        "AMD Athlon",
        "AMD Athlon 4",
        "AMD Athlon 64",
        "AMD Athlon 64 FX",
        "AMD Athlon 64 X2",
        "AMD Athlon II",
        "AMD Athlon II X2",
        "AMD Athlon II X3",
        "AMD Athlon II X4",
        "AMD Athlon II X4 640",
        "AMD Athlon X2",
        "AMD Athlon XP",
        "AMD E",
        "AMD E2-1800",
        "AMD E-300",
        "AMD E-350",
        "AMD E-450",
        "AMD FM2",
        "AMD FX",
        "AMD FX 4100",
        "AMD FX 8120",
        "AMD FX-8 Core",
        "AMD FX Bulldozer",
        "AMD K6-2",
        "AMD Opteron",
        "AMD Phenom",
        "AMD Phenom II",
        "AMD Phenom II X2",
        "AMD Phenom II X3",
        "AMD Phenom II X4",
        "AMD Phenom II X6",
        "AMD Phenom X3",
        "AMD Phenom X4",
        "AMD Ryzen 3",
        "AMD Ryzen 5",
        "AMD Ryzen 5 PRO",
        "AMD Ryzen 7",
        "AMD Ryzen 9",
        "AMD Ryzen Threadripper",
        "AMD Sempron",
        "AMD Turion 64 X2",
        "Celeron Dual Core",
        "Intel Atom",
        "Intel Atom Dual Core",
        "Intel Celeron",
        "Intel Celeron D",
        "Intel Celeron J",
        "Intel Celeron M",
        "Intel Celeron N",
        "Intel Core 2",
        "Intel Core 2 Duo",
        "Intel Core 2 Quad",
        "Intel Core Duo",
        "Intel Core i3 10th Gen.",
        "Intel Core i3 11th Gen.",
        "Intel Core i3 1st Gen.",
        "Intel Core i3 2nd Gen.",
        "Intel Core i3 3rd Gen.",
        "Intel Core i3 4th Gen.",
        "Intel Core i3 5th Gen.",
        "Intel Core i3 6th Gen.",
        "Intel Core i3 7th Gen.",
        "Intel Core i3 8th Gen.",
        "Intel Core i3 9th Gen.",
        "Intel Core i5 10th Gen.",
        "Intel Core i5 11th Gen.",
        "Intel Core i5 1st Gen.",
        "Intel Core i5 2nd Gen.",
        "Intel Core i5 3rd Gen.",
        "Intel Core i5 4th Gen.",
        "Intel Core i5 5th Gen.",
        "Intel Core i5 6th Gen.",
        "Intel Core i5 7th Gen.",
        "Intel Core i5 8th Gen.",
        "Intel Core i5 9th Gen.",
        "Intel Core i5 X-Series",
        "Intel Core i7 10th Gen.",
        "Intel Core i7 11th Gen.",
        "Intel Core i7 1st Gen.",
        "Intel Core i7-2600",
        "Intel Core i7 2nd Gen.",
        "Intel Core i7 3rd Gen.",
        "Intel Core i7 4th Gen.",
        "Intel Core i7 5th Gen.",
        "Intel Core i7 6th Gen.",
        "Intel Core i7 7th Gen.",
        "Intel Core i7 8th Gen.",
        "Intel Core i7 9th Gen.",
        "Intel Core i7 Extreme 2nd Gen.",
        "Intel Core i7 Extreme 3rd Gen.",
        "Intel Core i7 Extreme 4th Gen.",
        "Intel Core i7 Extreme 6th Gen.",
        "Intel Core i9 10th Gen.",
        "Intel Core i9 9th Gen.",
        "Intel Core i9 X-Series",
        "Intel Dual Core",
        "Intel Pentium",
        "Intel Pentium 4",
        "Intel Pentium 4 HT",
        "Intel Pentium D",
        "Intel Pentium Dual-Core",
        "Intel Pentium G",
        "Intel Pentium Gold",
        "Intel Pentium II",
        "Intel Pentium III",
        "Intel Pentium M",
        "Intel Pentium MMX",
        "Intel Pentium Pro",
        "Intel Pentium Silver",
        "Intel Quad Core",
        "Intel Xeon",
        "Intel Xeon 12-Core",
        "Intel Xeon 6-Core",
        "Intel Xeon 8-Core",
        "Intel Xeon E3",
        "Intel Xeon E5",
        "Intel Xeon Quad Core",
        "Intel Xeon Silver",
        "Intel Xeon Six Core",
        "Intel Xeon W",
        "UltraSPARC IIe",
        "VIA C3",
        "Via Eden",
        "VIA Nano"
    ];
}

function getEbayProductByMobileCategory($searchProductName, $productFullName, $categoryId, $categoryName, $make, $condition, $token, $capacity, $color, $connectivity, $conditionName)
{
    $sellerList = \App\EBaySeller::all();

    $sellerUserNameList = [];
    foreach ($sellerList as $seller) {
        array_push($sellerUserNameList, $seller->user_name);
    }

    /** condition Condition Definitions
     * https://developer.ebay.com/devzone/finding/callref/Enums/conditionIdList.html check on this web site*
     */


    if ($condition !== "") {

        $conditionList = [
            $condition => $conditionName,
        ];

    } else {
        $conditionList = [
            '1000' => 'New',
            '1500' => 'Open box',
            '1750' => 'New with defects',
            '2000' => 'Certified - Refurbished',
            '2010' => 'Excellent - Refurbished',
            '2020' => 'Very Good - Refurbished',
            '2030' => 'Good - Refurbished',
            '2500' => 'Seller refurbished',
            '2750' => 'Like New',
            '3000' => 'Used',
            '4000' => 'Very Good',
            '5000' => 'Good',
            '6000' => 'Acceptable',
            '7000' => 'For parts or not working'

        ];

    }


    $finalData = [];

    foreach ($conditionList as $key => $value) {

        $client = new Client();
        $client->setDefaultOption('headers', array('Authorization' => "Bearer {$token}", "X-EBAY-C-MARKETPLACE-ID" => "EBAY_GB"));

        try {

            $response = $client->get("https://api.ebay.com/buy/browse/v1/item_summary/search?q=" . $productFullName . "&category_ids=" . $categoryId . "&aspect_filter=categoryId:" . $categoryId . ",Storage Capacity:{" . $capacity . "},Colour:{" . $color . "},Connectivity:{" . $connectivity . "}&filter=conditionIds:{" . $key . "},deliveryCountry:GB,itemLocationCountry:GB,buyingOptions:{FIXED_PRICE},price:[20],priceCurrency:GBP&sort=price");
            $data = $response->json();

            //   dd("https://api.ebay.com/buy/browse/v1/item_summary/search?q=" . $productFullName . "&category_ids=" . $categoryId . "&aspect_filter=categoryId:".$categoryId.",Storage Capacity:{".$capacity."},Colour:{".$color."},Connectivity:{".$connectivity."}&filter=conditionIds:{" . $key . "},deliveryCountry:GB,itemLocationCountry:GB,buyingOptions:{FIXED_PRICE},price:[20],priceCurrency:GBP&sort=price");
            $priceFirst = [];
            $bestPrice = '';
            $sellerPrice = [];
            $priceList = [];
            $rakingList = [];
            $finalRaking = [];
            $epid = '';
            $totalSold = 0;
            $modelNumber = '';
            $availableStock = 0;


            $totalQty = $data['total'];

            if ($data['total'] > 0) {


                // $eBayApiResponse = getFindProduct($token, $productFullName, $categoryId, $condition);
                $raking = 0;
                if ($data['total'] > 0) {

                    foreach ($data['itemSummaries'] as $item) {

                        $raking++;
                        $apiNetwork = [];

                        $epid = isset($item['epid']) ? $item['epid'] : '';

                        $productResponse = $client->get($item['itemHref']);
                        $productData = $productResponse->json();


                        $availableStock += getAvailableStock($item['itemHref']);
                        foreach ($productData['localizedAspects'] as $localized) {
                            if ($localized['name'] === "Network") {
                                array_push($apiNetwork, str_replace(' ', '', $localized['value']));
                            }

                            if ($localized['name'] === "Model Number") {

                                $modelNumber = $localized['value'];
                            }

                        }

                        $network = count($apiNetwork) > 0 ? $apiNetwork[0] : '';

                        if (isset($item['price']['convertedFromValue'])) {
                            $comparePrice = $item['price']['convertedFromValue'];
                        } else {
                            $comparePrice = $item['price']['value'];
                        }
                        if (isset($item['price']['convertedFromValue'])) {
                            $price = $item['price']['convertedFromValue'];
                        } else {
                            $price = $item['price']['value'];
                        }

                        if ($comparePrice > 20) {

                            if (!in_array($item['seller']['username'], $sellerUserNameList)) {

                                $priceList[$item['seller']['username'] . '@' . $price . '@' . $network] = $price;
                                $rakingList[$item['seller']['username'] . '@' . $price . '@' . $raking] = $raking;
                            }
                        }

                        if (count($sellerList)) {
                            foreach ($sellerList as $seller) {
                                if ($item['seller']['username'] === $seller->user_name) {
                                    $priceFirst[$item['seller']['username'] . '@' . $price . '@' . $network . '@' . $raking] = $price;
                                }
                            }

                        }
                    }
                }


                if (count($priceFirst)) {
                    $sellerUserNamePrice = array_search(min($priceFirst), $priceFirst);
                    $bestPrice = explode('@', $sellerUserNamePrice);
                }

                arsort($rakingList);
                foreach ($rakingList as $key => $fv) {
                    $username = explode('@', $key);
                    $finalRaking[$username[0] . '@' . $username[1]] = $fv;

                }

                asort($priceList);

                $i = 0;


                foreach ($priceList as $key => $list) {

                    if ($i <= 5) {
                        array_push($sellerPrice, $key);
                    }

                    $i++;
                }

                $result = array_unique($sellerPrice);
                $exportFirst = isset($result[0]) ? explode('@', $result[0]) : [];
                $exportSecond = isset($result[1]) ? explode('@', $result[1]) : [];
                $exportThird = isset($result[2]) ? explode('@', $result[2]) : [];

                $firstPrice = isset($exportFirst[1]) ? $exportFirst[1] : 0;
                $firstSeller = isset($exportFirst[0]) ? $exportFirst[0] : '';
                $firstNetwork = isset($exportFirst[2]) ? $exportFirst[2] : '';

                $secondPrice = isset($exportSecond[1]) ? $exportSecond[1] : 0;
                $secondSeller = isset($exportSecond[0]) ? $exportSecond[0] : '';
                $secondNetwork = isset($exportSecond[2]) ? $exportSecond[2] : '';

                $thirdPrice = isset($exportThird[1]) ? $exportThird[1] : 0;
                $thirdSeller = isset($exportThird[0]) ? $exportThird[0] : '';
                $thirdNetwork = isset($exportThird[2]) ? $exportThird[2] : '';


                $firstRaking = '';
                $secondRaking = '';
                $thirdRaking = '';
                asort($finalRaking);
                if (array_key_exists($firstSeller . '@' . $firstPrice, $finalRaking)) {
                    $firstRaking = $finalRaking[$firstSeller . '@' . $firstPrice];
                }
                if (array_key_exists($secondSeller . '@' . $secondPrice, $finalRaking)) {
                    $secondRaking = $finalRaking[$secondSeller . '@' . $secondPrice];
                }
                if (array_key_exists($thirdSeller . '@' . $thirdPrice, $finalRaking)) {
                    $thirdRaking = $finalRaking[$thirdSeller . '@' . $thirdPrice];
                }
                if ($firstPrice || $secondPrice || $thirdPrice) {


                    $finalData[] = [
                        'product_name' => $searchProductName,
                        'ean' => "",
                        'mpn' => "",
                        'epid' => $epid,
                        'condition' => $value,
                        'best_price_from_named_seller' => isset($bestPrice[1]) ? $bestPrice[1] : '',
                        'best_price_network' => isset($bestPrice[2]) ? $bestPrice[2] : '',
                        'best_seller' => isset($bestPrice[0]) ? $bestPrice[0] : '',
                        'best_seller_listing_rank' => isset($bestPrice[3]) ? $bestPrice[3] : '',
                        'first_best_price' => $firstPrice,
                        'first_network' => $firstNetwork,
                        'first_seller' => $firstSeller,
                        'first_listing_rank' => $firstRaking,
                        'second_best_price' => $secondPrice,
                        'second_network' => $secondNetwork,
                        'second_seller' => $secondSeller,
                        'second_listing_rank' => $secondRaking,
                        'third_best_price' => $thirdPrice,
                        'third_network' => $thirdNetwork,
                        'third_seller' => $thirdSeller,
                        'third_listing_rank' => $thirdRaking,
                        'model_no' => $modelNumber,
                        'category' => $categoryName,
                        'platform' => "eBay",
                        'make' => $make,
                        'available_stock' => $availableStock,
                        'total_qty' => $totalQty


                    ];
                }


            }

        } catch (\Exception $e) {


            return [
                'status' => $e->getCode(),
                'error' => $e->getMessage(),
                'data' => []

            ];


        }


    }
    return [
        'status' => 200,
        'data' => $finalData];


}

function getEbayProductWithOtherCategory($searchProductName, $productFullName, $categoryId, $categoryName, $grade = null, $token, $capacity, $color, $connectivity, $make)
{
    $sellerList = \App\EBaySeller::all();


    $sellerUserNameList = [];
    foreach ($sellerList as $seller) {
        array_push($sellerUserNameList, $seller->user_name);
    }


    $finalData = [];


    $conditionList = [
        '2500' => 'Seller refurbished',

    ];

    if (!is_null($grade)) {


        $gradeList = [
            $grade
        ];


    } else {
        $gradeList = [
            'Grade A-Excellent',
            'Grade B- Very Good',
            'Grade C-Good',

        ];

    }


    $client = new Client();
    $client->setDefaultOption('headers', array('Authorization' => "Bearer {$token}", 'X-EBAY-C-MARKETPLACE-ID' => "EBAY_GB"));


    foreach ($conditionList as $Conditionkey => $value) {


        foreach ($gradeList as $grades) {
            $combinePriceList = [];
            $combineFirstPrice = [];
            $combineRanking = [];
            $sellerPrice = [];
            $priceFirst = [];
            $bestPrice = '';

            $priceList = [];
            $rakingList = [];
            $finalRaking = [];
            $graderWithCondition = explode('-', $grades);
            $totalQty = 0;

            foreach ($graderWithCondition as $gra) {

                try {


                    $epid = '';
                    $apiNetwork = [];
                    $modelNumber = '';

                    $availableStock = 0;
                    $fullProductName = strtolower($productFullName) . ' ' . strtolower($gra) . ' ' . strtolower($value);


                    $response = $client->get("https://api.ebay.com/buy/browse/v1/item_summary/search?q=" . $fullProductName . "&category_id=" . $categoryId . "&aspect_filter=categoryId:" . $categoryId . ",Storage Capacity:{" . $capacity . "},Colour:{" . $color . "},Connectivity:{" . $connectivity . "}&filter=deliveryCountry:GB,itemLocationCountry:GB,buyingOptions:{FIXED_PRICE},price:[20],priceCurrency:GBP&sort=price");
                    $data = $response->json();


                    $totalQty = $data['total'];


                    $raking = 0;
                    if ($data['total'] > 0) {

                        foreach ($data['itemSummaries'] as $item) {

                            $raking++;

                            $condition = [];
                            $grade = [];
                            $title = [];
                            $epid = isset($item['epid']) ? $item['epid'] : '';

                            if (strpos($item['title'], $gra) !== false) {
                                array_push($title, $gra);
                            }

                            $productResponse = $client->get($item['itemHref']);
                            $productData = $productResponse->json();

                            $availableStock += getAvailableStock($item['itemHref']);

                            foreach ($productData['localizedAspects'] as $localized) {
                                if ($localized['name'] === "Condition") {

                                    if (strpos($localized['value'], $gra) !== false) {
                                        array_push($condition, $localized['value']);
                                        //  $condition=$localized['value'];
                                    }

                                }

                                if ($localized['name'] === "Grade") {
                                    if (strpos($localized['value'], $gra) !== false) {
                                        array_push($grade, $localized['value']);
                                        // $grade=$localized['value'];
                                    }
                                }
                            }


                            foreach ($productData['localizedAspects'] as $localized) {
                                if ($localized['name'] === "Network") {
                                    array_push($apiNetwork, str_replace(' ', '', $localized['value']));
                                }

                                if ($localized['name'] === "Model Number") {

                                    $modelNumber = $localized['value'];
                                }

                            }

                            $network = count($apiNetwork) > 0 ? $apiNetwork[0] : '';

                            if (isset($item['price']['convertedFromValue'])) {
                                $comparePrice = $item['price']['convertedFromValue'];
                            } else {
                                $comparePrice = $item['price']['value'];
                            }
                            if (isset($item['price']['convertedFromValue'])) {
                                $price = $item['price']['convertedFromValue'];
                            } else {
                                $price = $item['price']['value'];
                            }

                            if ($comparePrice > 20) {


                                if (!in_array($item['seller']['username'], $sellerUserNameList)) {
                                    $priceList[$item['seller']['username'] . '@' . $price . '@' . $network] = $price;
                                    $rakingList[$item['seller']['username'] . '@' . $price . '@' . $raking] = $raking;
                                }


                            }

                            if (count($sellerList)) {
                                foreach ($sellerList as $seller) {

                                    if ($item['seller']['username'] === $seller->user_name) {
                                        $priceFirst[$item['seller']['username'] . '@' . $price . '@' . $network . '@' . $raking] = $price;
                                    }

                                }

                            }

                        }


                    }


                } catch (\Exception $e) {

                    return [
                        'status' => $e->getCode(),
                        'error' => $e->getMessage(),
                        'data' => []
                    ];

                }


            }


            if (count($priceList)) {
                array_push($combinePriceList, $priceList);
            }

            if (count($rakingList)) {
                array_push($combineRanking, $rakingList);
            }


            if (count($combineFirstPrice)) {

                $sellerUserNamePrice = array_search(min($combineFirstPrice), $combineFirstPrice);
                $bestPrice = explode('@', $sellerUserNamePrice);
            }


            arsort($combineRanking);


            if (count($combineRanking)) {

                foreach ($combineRanking[0] as $key => $fv) {

                    $username = explode('@', $key);
                    $finalRaking[$username[0] . '@' . $username[1]] = $fv;

                }
            }


            asort($combinePriceList);

//
            $i = 0;
//


            foreach ($combinePriceList as $pri) {

                foreach ($pri as $key => $price) {
                    if ($i <= 5) {
                        array_push($sellerPrice, $key);
                    }

                    $i++;
                }


            }


            $result = array_unique($sellerPrice);


            $exportFirst = isset($result[0]) ? explode('@', $result[0]) : [];
            $exportSecond = isset($result[1]) ? explode('@', $result[1]) : [];
            $exportThird = isset($result[2]) ? explode('@', $result[2]) : [];

            $firstPrice = isset($exportFirst[1]) ? $exportFirst[1] : 0;
            $firstSeller = isset($exportFirst[0]) ? $exportFirst[0] : '';
            $firstNetwork = isset($exportFirst[2]) ? $exportFirst[2] : '';

            $secondPrice = isset($exportSecond[1]) ? $exportSecond[1] : 0;
            $secondSeller = isset($exportSecond[0]) ? $exportSecond[0] : '';
            $secondNetwork = isset($exportSecond[2]) ? $exportSecond[2] : '';

            $thirdPrice = isset($exportThird[1]) ? $exportThird[1] : 0;
            $thirdSeller = isset($exportThird[0]) ? $exportThird[0] : '';
            $thirdNetwork = isset($exportThird[2]) ? $exportThird[2] : '';


            $firstRaking = '';
            $secondRaking = '';
            $thirdRaking = '';


            asort($finalRaking);


            if (array_key_exists($firstSeller . '@' . $firstPrice, $finalRaking)) {
                $firstRaking = $finalRaking[$firstSeller . '@' . $firstPrice];
            }
            if (array_key_exists($secondSeller . '@' . $secondPrice, $finalRaking)) {
                $secondRaking = $finalRaking[$secondSeller . '@' . $secondPrice];
            }
            if (array_key_exists($thirdSeller . '@' . $thirdPrice, $finalRaking)) {
                $thirdRaking = $finalRaking[$thirdSeller . '@' . $thirdPrice];
            }


            if ($firstPrice || $secondPrice || $thirdPrice) {


                $finalData[] = [
                    'product_name' => $searchProductName,
                    'ean' => "",
                    'mpn' => "",
                    'epid' => $epid,
                    'condition' => $value . ' ' . $grades,
                    'best_price_from_named_seller' => isset($bestPrice[1]) ? $bestPrice[1] : '',
                    'best_price_network' => isset($bestPrice[2]) ? $bestPrice[2] : '',
                    'best_seller' => isset($bestPrice[0]) ? $bestPrice[0] : '',
                    'best_seller_listing_rank' => isset($bestPrice[3]) ? $bestPrice[3] : '',
                    'first_best_price' => $firstPrice,
                    'first_network' => $firstNetwork,
                    'first_seller' => $firstSeller,
                    'first_listing_rank' => $firstRaking,
                    'second_best_price' => $secondPrice,
                    'second_network' => $secondNetwork,
                    'second_seller' => $secondSeller,
                    'second_listing_rank' => $secondRaking,
                    'third_best_price' => $thirdPrice,
                    'third_network' => $thirdNetwork,
                    'third_seller' => $thirdSeller,
                    'third_listing_rank' => $thirdRaking,
                    'model_no' => $modelNumber,
                    'category' => $categoryName,
                    'platform' => "eBay",
                    'make' => $make,
                    'available_stock' => $availableStock,
                    'total_qty' => $totalQty


                ];


            }


        }


    }

    return [
        'status' => 200,
        'data' => $finalData
    ];


}

function getProductBaseOnProductName($searchProductName, $productName, $categoryName, $token, $categoryId, $operatingSystem, $ramSize, $processor, $storageType, $hardDriveCapacity, $ssdCapacity, $make)
{

    $sellerList = \App\EBaySeller::all();
    $sellerUserNameList = [];
    foreach ($sellerList as $seller) {
        array_push($sellerUserNameList, $seller->user_name);
    }
    $conditionList = [
        '2500' => 'Seller refurbished'
    ];

    $finalData = [];
    foreach ($conditionList as $key => $value) {
        $client = new Client();
        $client->setDefaultOption('headers', array('Authorization' => "Bearer {$token}", 'X-EBAY-C-MARKETPLACE-ID' => "EBAY_GB"));
        $combinePriceList = [];
        $combineFirstPrice = [];
        $combineRanking = [];
        $sellerPrice = [];
        try {
            $priceFirst = [];
            $bestPrice = '';
            $priceList = [];
            $rakingList = [];
            $finalRaking = [];
            $epid = '';
            $mpn = '';
            $modelNumber = '';
            $availableStock = 0;
            $productFullName = $productName;
            $response = $client->get("https://api.ebay.com/buy/browse/v1/item_summary/search?q=" . $productFullName . "&category_ids=" . $categoryId . "&aspect_filter=categoryId:" . $categoryId . ",Operating System:{" . $operatingSystem . "},RAM Size:{" . $ramSize . "},Processor:{" . $processor . "},Storage Type:{" . $storageType . "},Hard Drive Capacity:{" . $hardDriveCapacity . "},SSD Capacity:{" . $ssdCapacity . "}&filter=conditionIds:{" . $key . "},deliveryCountry:GB,itemLocationCountry:GB,buyingOptions:{FIXED_PRICE},price:[20],priceCurrency:GBP&sort=price");

            $data = $response->json();

            // dd("https://api.ebay.com/buy/browse/v1/item_summary/search?q=".$productFullName."&category_ids=".$categoryId."&aspect_filter=categoryId:".$categoryId.",Operating System:{".$operatingSystem."},RAM Size:{".$ramSize."},Processor:{".$processor."},Storage Type:{".$storageType."},Hard Drive Capacity:{".$hardDriveCapacity."},SSD Capacity:{".$ssdCapacity."}&filter=conditionIds:{" . $key . "},deliveryCountry:GB,itemLocationCountry:GB,buyingOptions:{FIXED_PRICE},price:[20],priceCurrency:GBP&sort=price");

            $totalQty = $data['total'];

            if ($data['total'] > 0) {
                $raking = 0;
                foreach ($data['itemSummaries'] as $item) {
                    $raking++;
                    $apiNetwork = [];
                    $epid = isset($item['epid']) ? $item['epid'] : '';
                    $productResponse = $client->get($item['itemHref']);
                    $productData = $productResponse->json();
                    $mpn = isset($productData['mpn']) ? $productData['mpn'] : '';
                    $availableStock += getAvailableStock($item['itemHref']);
                    foreach ($productData['localizedAspects'] as $localized) {
                        if ($localized['name'] === "Network") {
                            array_push($apiNetwork, $localized['value']);
                        }

                        if ($localized['name'] === "Model Number") {
                            $modelNumber = $localized['value'];
                        }
                    }

                    $network = count($apiNetwork) > 0 ? $apiNetwork[0] : '';
                    if (isset($item['price']['convertedFromValue'])) {
                        $comparePrice = $item['price']['convertedFromValue'];
                    } else {
                        $comparePrice = $item['price']['value'];
                    }
                    if (isset($item['price']['convertedFromValue'])) {
                        $price = $item['price']['convertedFromValue'];
                    } else {
                        $price = $item['price']['value'];
                    }
                    if ($comparePrice > 20) {
                        if (!in_array($item['seller']['username'], $sellerUserNameList)) {
                            $priceList[$item['seller']['username'] . '@' . $price . '@' . $network] = $price;
                            $rakingList[$item['seller']['username'] . '@' . $price . '@' . $raking] = $raking;
                        }
                    }

                    if (count($sellerList)) {
                        foreach ($sellerList as $seller) {
                            if ($item['seller']['username'] === $seller->user_name) {
                                $priceFirst[$item['seller']['username'] . '@' . $price . '@' . $network . '@' . $raking] = $price;
                            }
                        }
                    }
                }


                if (count($priceList)) {
                    array_push($combinePriceList, $priceList);
                }
                if (count($priceFirst)) {
                    array_push($combineFirstPrice, $priceFirst);
                }
                if (count($rakingList)) {
                    array_push($combineRanking, $rakingList);
                }


                if (count($combineFirstPrice)) {
                    $sellerUserNamePrice = array_search(min($combineFirstPrice), $combineFirstPrice);
                    $bestPrice = explode('@', $sellerUserNamePrice);
                }
                arsort($combineRanking);
                if (count($combineRanking)) {
                    foreach ($combineRanking[0] as $keyRank => $fv) {
                        $username = explode('@', $keyRank);
                        $finalRaking[$username[0] . '@' . $username[1]] = $fv;
                    }
                }
                asort($combinePriceList);
//
                $i = 0;
//
                foreach ($combinePriceList as $pri) {
                    foreach ($pri as $key => $price) {
                        if ($i <= 5) {
                            array_push($sellerPrice, $key);
                        }
                        $i++;
                    }
                }

                $result = array_unique($sellerPrice);
                //   print_r($result);
                $exportFirst = isset($result[0]) ? explode('@', $result[0]) : [];
                $exportSecond = isset($result[1]) ? explode('@', $result[1]) : [];
                $exportThird = isset($result[2]) ? explode('@', $result[2]) : [];
                $firstPrice = isset($exportFirst[1]) ? $exportFirst[1] : 0;
                $firstSeller = isset($exportFirst[0]) ? $exportFirst[0] : '';
                $firstNetwork = isset($exportFirst[2]) ? $exportFirst[2] : '';
                $secondPrice = isset($exportSecond[1]) ? $exportSecond[1] : 0;
                $secondSeller = isset($exportSecond[0]) ? $exportSecond[0] : '';
                $secondNetwork = isset($exportSecond[2]) ? $exportSecond[2] : '';
                $thirdPrice = isset($exportThird[1]) ? $exportThird[1] : 0;
                $thirdSeller = isset($exportThird[0]) ? $exportThird[0] : '';
                $thirdNetwork = isset($exportThird[2]) ? $exportThird[2] : '';
                $firstRaking = '';
                $secondRaking = '';
                $thirdRaking = '';
                asort($finalRaking);
                if (array_key_exists($firstSeller . '@' . $firstPrice, $finalRaking)) {
                    $firstRaking = $finalRaking[$firstSeller . '@' . $firstPrice];
                }
                if (array_key_exists($secondSeller . '@' . $secondPrice, $finalRaking)) {
                    $secondRaking = $finalRaking[$secondSeller . '@' . $secondPrice];
                }
                if (array_key_exists($thirdSeller . '@' . $thirdPrice, $finalRaking)) {
                    $thirdRaking = $finalRaking[$thirdSeller . '@' . $thirdPrice];
                }

                if ($firstPrice || $secondPrice || $thirdPrice) {
                    $finalData[] = [
                        'product_name' => $searchProductName,
                        'ean' => "",
                        'mpn' => "",
                        'epid' => $epid,
                        'condition' => $value,
                        'best_price_from_named_seller' => isset($bestPrice[1]) ? $bestPrice[1] : '',
                        'best_price_network' => isset($bestPrice[2]) ? $bestPrice[2] : '',
                        'best_seller' => isset($bestPrice[0]) ? $bestPrice[0] : '',
                        'best_seller_listing_rank' => isset($bestPrice[3]) ? $bestPrice[3] : '',
                        'first_best_price' => $firstPrice,
                        'first_network' => $firstNetwork,
                        'first_seller' => $firstSeller,
                        'first_listing_rank' => $firstRaking,
                        'second_best_price' => $secondPrice,
                        'second_network' => $secondNetwork,
                        'second_seller' => $secondSeller,
                        'second_listing_rank' => $secondRaking,
                        'third_best_price' => $thirdPrice,
                        'third_network' => $thirdNetwork,
                        'third_seller' => $thirdSeller,
                        'third_listing_rank' => $thirdRaking,
                        'model_no' => $modelNumber,
                        'category' => $categoryName,
                        'platform' => "eBay",
                        'make' => $make,
                        'available_stock' => $availableStock,
                        'total_qty' => $totalQty
                    ];

                }

            }
        } catch (\Exception $e) {
            return [
                'status' => $e->getCode(),
                'error' => $e->getMessage(),
                'data' => []
            ];

        }

    }
    return [
        'status' => 200,
        'data' => $finalData

    ];
}

function getCategoryValidation($name)
{
    $category = \App\Category::where('name', $name)->first();
    return $category->validation;
}

function getSupplierMappingGrade($supplierId, $supplierCondition)
{
    $supplier = \App\Models\Supplier::find($supplierId);
    $grader = '';
    if (isset($supplier->grade_mapping)) {
        foreach (json_decode($supplier->grade_mapping) as $ty) {

            if ($ty->s === $supplierCondition) {
                $grader = $ty->r;
            }
        }
        return $grader;
    }
    return $grader;
}

function getCondition($name)
{

    if ($name === "Excellent - Refurbished") {

        return "Excellent (A) - Refurbished";
    } elseif ($name === "Very Good - Refurbished") {

        return "Good/Very Good (B) - Refurbished";

    } elseif ($name === "Good - Refurbished") {

        return "Fair/Good (C) Refurbished";
    }

}

function getCountryCode($countryName)
{

    $country = '[
  {
      "name": "Afghanistan",
    "code": "AF"
  },
  {
      "name": "land Islands",
    "code": "AX"
  },
  {
      "name": "Albania",
    "code": "AL"
  },
  {
      "name": "Algeria",
    "code": "DZ"
  },
  {
      "name": "American Samoa",
    "code": "AS"
  },
  {
      "name": "AndorrA",
    "code": "AD"
  },
  {
      "name": "Angola",
    "code": "AO"
  },
  {
      "name": "Anguilla",
    "code": "AI"
  },
  {
      "name": "Antarctica",
    "code": "AQ"
  },
  {
      "name": "Antigua and Barbuda",
    "code": "AG"
  },
  {
      "name": "Argentina",
    "code": "AR"
  },
  {
      "name": "Armenia",
    "code": "AM"
  },
  {
      "name": "Aruba",
    "code": "AW"
  },
  {
      "name": "Australia",
    "code": "AU"
  },
  {
      "name": "Austria",
    "code": "AT"
  },
  {
      "name": "Azerbaijan",
    "code": "AZ"
  },
  {
      "name": "Bahamas",
    "code": "BS"
  },
  {
      "name": "Bahrain",
    "code": "BH"
  },
  {
      "name": "Bangladesh",
    "code": "BD"
  },
  {
      "name": "Barbados",
    "code": "BB"
  },
  {
      "name": "Belarus",
    "code": "BY"
  },
  {
      "name": "Belgium",
    "code": "BE"
  },
  {
      "name": "Belize",
    "code": "BZ"
  },
  {
      "name": "Benin",
    "code": "BJ"
  },
  {
      "name": "Bermuda",
    "code": "BM"
  },
  {
      "name": "Bhutan",
    "code": "BT"
  },
  {
      "name": "Bolivia",
    "code": "BO"
  },
  {
      "name": "Bosnia and Herzegovina",
    "code": "BA"
  },
  {
      "name": "Botswana",
    "code": "BW"
  },
  {
      "name": "Bouvet Island",
    "code": "BV"
  },
  {
      "name": "Brazil",
    "code": "BR"
  },
  {
      "name": "British Indian Ocean Territory",
    "code": "IO"
  },
  {
      "name": "Brunei Darussalam",
    "code": "BN"
  },
  {
      "name": "Bulgaria",
    "code": "BG"
  },
  {
      "name": "Burkina Faso",
    "code": "BF"
  },
  {
      "name": "Burundi",
    "code": "BI"
  },
  {
      "name": "Cambodia",
    "code": "KH"
  },
  {
      "name": "Cameroon",
    "code": "CM"
  },
  {
      "name": "Canada",
    "code": "CA"
  },
  {
      "name": "Cape Verde",
    "code": "CV"
  },
  {
      "name": "Cayman Islands",
    "code": "KY"
  },
  {
      "name": "Central African Republic",
    "code": "CF"
  },
  {
      "name": "Chad",
    "code": "TD"
  },
  {
      "name": "Chile",
    "code": "CL"
  },
  {
      "name": "China",
    "code": "CN"
  },
  {
      "name": "Christmas Island",
    "code": "CX"
  },
  {
      "name": "Cocos (Keeling) Islands",
    "code": "CC"
  },
  {
      "name": "Colombia",
    "code": "CO"
  },
  {
      "name": "Comoros",
    "code": "KM"
  },
  {
      "name": "Congo",
    "code": "CG"
  },
  {
      "name": "Congo, The Democratic Republic of the",
    "code": "CD"
  },
  {
      "name": "Cook Islands",
    "code": "CK"
  },
  {
      "name": "Costa Rica",
    "code": "CR"
  },
  {
      "name": "Cote D\"Ivoire",
    "code": "CI"
  },
  {
      "name": "Croatia",
    "code": "HR"
  },
  {
      "name": "Cuba",
    "code": "CU"
  },
  {
      "name": "Cyprus",
    "code": "CY"
  },
  {
      "name": "Czech Republic",
    "code": "CZ"
  },
  {
      "name": "Denmark",
    "code": "DK"
  },
  {
      "name": "Djibouti",
    "code": "DJ"
  },
  {
      "name": "Dominica",
    "code": "DM"
  },
  {
      "name": "Dominican Republic",
    "code": "DO"
  },
  {
      "name": "Ecuador",
    "code": "EC"
  },
  {
      "name": "Egypt",
    "code": "EG"
  },
  {
      "name": "El Salvador",
    "code": "SV"
  },
  {
      "name": "Equatorial Guinea",
    "code": "GQ"
  },
  {
      "name": "Eritrea",
    "code": "ER"
  },
  {
      "name": "Estonia",
    "code": "EE"
  },
  {
      "name": "Ethiopia",
    "code": "ET"
  },
  {
      "name": "Falkland Islands (Malvinas)",
    "code": "FK"
  },
  {
      "name": "Faroe Islands",
    "code": "FO"
  },
  {
      "name": "Fiji",
    "code": "FJ"
  },
  {
      "name": "Finland",
    "code": "FI"
  },
  {
      "name": "France",
    "code": "FR"
  },
  {
      "name": "French Guiana",
    "code": "GF"
  },
  {
      "name": "French Polynesia",
    "code": "PF"
  },
  {
      "name": "French Southern Territories",
    "code": "TF"
  },
  {
      "name": "Gabon",
    "code": "GA"
  },
  {
      "name": "Gambia",
    "code": "GM"
  },
  {
      "name": "Georgia",
    "code": "GE"
  },
  {
      "name": "Germany",
    "code": "DE"
  },
  {
      "name": "Ghana",
    "code": "GH"
  },
  {
      "name": "Gibraltar",
    "code": "GI"
  },
  {
      "name": "Greece",
    "code": "GR"
  },
  {
      "name": "Greenland",
    "code": "GL"
  },
  {
      "name": "Grenada",
    "code": "GD"
  },
  {
      "name": "Guadeloupe",
    "code": "GP"
  },
  {
      "name": "Guam",
    "code": "GU"
  },
  {
      "name": "Guatemala",
    "code": "GT"
  },
  {
      "name": "Guernsey",
    "code": "GG"
  },
  {
      "name": "Guinea",
    "code": "GN"
  },
  {
      "name": "Guinea-Bissau",
    "code": "GW"
  },
  {
      "name": "Guyana",
    "code": "GY"
  },
  {
      "name": "Haiti",
    "code": "HT"
  },
  {
      "name": "Heard Island and Mcdonald Islands",
    "code": "HM"
  },
  {
      "name": "Holy See (Vatican City State)",
    "code": "VA"
  },
  {
      "name": "Honduras",
    "code": "HN"
  },
  {
      "name": "Hong Kong",
    "code": "HK"
  },
  {
      "name": "Hungary",
    "code": "HU"
  },
  {
      "name": "Iceland",
    "code": "IS"
  },
  {
      "name": "India",
    "code": "IN"
  },
  {
      "name": "Indonesia",
    "code": "ID"
  },
  {
      "name": "Iran, Islamic Republic Of",
    "code": "IR"
  },
  {
      "name": "Iraq",
    "code": "IQ"
  },
  {
      "name": "Ireland",
    "code": "IE"
  },
  {
      "name": "Isle of Man",
    "code": "IM"
  },
  {
      "name": "Israel",
    "code": "IL"
  },
  {
      "name": "Italy",
    "code": "IT"
  },
  {
      "name": "Jamaica",
    "code": "JM"
  },
  {
      "name": "Japan",
    "code": "JP"
  },
  {
      "name": "Jersey",
    "code": "JE"
  },
  {
      "name": "Jordan",
    "code": "JO"
  },
  {
      "name": "Kazakhstan",
    "code": "KZ"
  },
  {
      "name": "Kenya",
    "code": "KE"
  },
  {
      "name": "Kiribati",
    "code": "KI"
  },
  {
      "name": "Korea, Democratic People\"S Republic of",
    "code": "KP"
  },
  {
      "name": "Korea, Republic of",
    "code": "KR"
  },
  {
      "name": "Kuwait",
    "code": "KW"
  },
  {
      "name": "Kyrgyzstan",
    "code": "KG"
  },
  {
      "name": "Lao People\"S Democratic Republic",
    "code": "LA"
  },
  {
      "name": "Latvia",
    "code": "LV"
  },
  {
      "name": "Lebanon",
    "code": "LB"
  },
  {
      "name": "Lesotho",
    "code": "LS"
  },
  {
      "name": "Liberia",
    "code": "LR"
  },
  {
      "name": "Libyan Arab Jamahiriya",
    "code": "LY"
  },
  {
      "name": "Liechtenstein",
    "code": "LI"
  },
  {
      "name": "Lithuania",
    "code": "LT"
  },
  {
      "name": "Luxembourg",
    "code": "LU"
  },
  {
      "name": "Macao",
    "code": "MO"
  },
  {
      "name": "Macedonia, The Former Yugoslav Republic of",
    "code": "MK"
  },
  {
      "name": "Madagascar",
    "code": "MG"
  },
  {
      "name": "Malawi",
    "code": "MW"
  },
  {
      "name": "Malaysia",
    "code": "MY"
  },
  {
      "name": "Maldives",
    "code": "MV"
  },
  {
      "name": "Mali",
    "code": "ML"
  },
  {
      "name": "Malta",
    "code": "MT"
  },
  {
      "name": "Marshall Islands",
    "code": "MH"
  },
  {
      "name": "Martinique",
    "code": "MQ"
  },
  {
      "name": "Mauritania",
    "code": "MR"
  },
  {
      "name": "Mauritius",
    "code": "MU"
  },
  {
      "name": "Mayotte",
    "code": "YT"
  },
  {
      "name": "Mexico",
    "code": "MX"
  },
  {
      "name": "Micronesia, Federated States of",
    "code": "FM"
  },
  {
      "name": "Moldova, Republic of",
    "code": "MD"
  },
  {
      "name": "Monaco",
    "code": "MC"
  },
  {
      "name": "Mongolia",
    "code": "MN"
  },
  {
      "name": "Montenegro",
    "code": "ME"
  },
  {
      "name": "Montserrat",
    "code": "MS"
  },
  {
      "name": "Morocco",
    "code": "MA"
  },
  {
      "name": "Mozambique",
    "code": "MZ"
  },
  {
      "name": "Myanmar",
    "code": "MM"
  },
  {
      "name": "Namibia",
    "code": "NA"
  },
  {
      "name": "Nauru",
    "code": "NR"
  },
  {
      "name": "Nepal",
    "code": "NP"
  },
  {
      "name": "Netherlands",
    "code": "NL"
  },
  {
      "name": "Netherlands Antilles",
    "code": "AN"
  },
  {
      "name": "New Caledonia",
    "code": "NC"
  },
  {
      "name": "New Zealand",
    "code": "NZ"
  },
  {
      "name": "Nicaragua",
    "code": "NI"
  },
  {
      "name": "Niger",
    "code": "NE"
  },
  {
      "name": "Nigeria",
    "code": "NG"
  },
  {
      "name": "Niue",
    "code": "NU"
  },
  {
      "name": "Norfolk Island",
    "code": "NF"
  },
  {
      "name": "Northern Mariana Islands",
    "code": "MP"
  },
  {
      "name": "Norway",
    "code": "NO"
  },
  {
      "name": "Oman",
    "code": "OM"
  },
  {
      "name": "Pakistan",
    "code": "PK"
  },
  {
      "name": "Palau",
    "code": "PW"
  },
  {
      "name": "Palestinian Territory, Occupied",
    "code": "PS"
  },
  {
      "name": "Panama",
    "code": "PA"
  },
  {
      "name": "Papua New Guinea",
    "code": "PG"
  },
  {
      "name": "Paraguay",
    "code": "PY"
  },
  {
      "name": "Peru",
    "code": "PE"
  },
  {
      "name": "Philippines",
    "code": "PH"
  },
  {
      "name": "Pitcairn",
    "code": "PN"
  },
  {
      "name": "Poland",
    "code": "PL"
  },
  {
      "name": "Portugal",
    "code": "PT"
  },
  {
      "name": "Puerto Rico",
    "code": "PR"
  },
  {
      "name": "Qatar",
    "code": "QA"
  },
  {
      "name": "Reunion",
    "code": "RE"
  },
  {
      "name": "Romania",
    "code": "RO"
  },
  {
      "name": "Russian Federation",
    "code": "RU"
  },
  {
      "name": "RWANDA",
    "code": "RW"
  },
  {
      "name": "Saint Helena",
    "code": "SH"
  },
  {
      "name": "Saint Kitts and Nevis",
    "code": "KN"
  },
  {
      "name": "Saint Lucia",
    "code": "LC"
  },
  {
      "name": "Saint Pierre and Miquelon",
    "code": "PM"
  },
  {
      "name": "Saint Vincent and the Grenadines",
    "code": "VC"
  },
  {
      "name": "Samoa",
    "code": "WS"
  },
  {
      "name": "San Marino",
    "code": "SM"
  },
  {
      "name": "Sao Tome and Principe",
    "code": "ST"
  },
  {
      "name": "Saudi Arabia",
    "code": "SA"
  },
  {
      "name": "Senegal",
    "code": "SN"
  },
  {
      "name": "Serbia",
    "code": "RS"
  },
  {
      "name": "Seychelles",
    "code": "SC"
  },
  {
      "name": "Sierra Leone",
    "code": "SL"
  },
  {
      "name": "Singapore",
    "code": "SG"
  },
  {
      "name": "Slovakia",
    "code": "SK"
  },
  {
      "name": "Slovenia",
    "code": "SI"
  },
  {
      "name": "Solomon Islands",
    "code": "SB"
  },
  {
      "name": "Somalia",
    "code": "SO"
  },
  {
      "name": "South Africa",
    "code": "ZA"
  },
  {
      "name": "South Georgia and the South Sandwich Islands",
    "code": "GS"
  },
  {
      "name": "Spain",
    "code": "ES"
  },
  {
      "name": "Sri Lanka",
    "code": "LK"
  },
  {
      "name": "Sudan",
    "code": "SD"
  },
  {
      "name": "Suriname",
    "code": "SR"
  },
  {
      "name": "Svalbard and Jan Mayen",
    "code": "SJ"
  },
  {
      "name": "Swaziland",
    "code": "SZ"
  },
  {
      "name": "Sweden",
    "code": "SE"
  },
  {
      "name": "Switzerland",
    "code": "CH"
  },
  {
      "name": "Syrian Arab Republic",
    "code": "SY"
  },
  {
      "name": "Taiwan, Province of China",
    "code": "TW"
  },
  {
      "name": "Tajikistan",
    "code": "TJ"
  },
  {
      "name": "Tanzania, United Republic of",
    "code": "TZ"
  },
  {
      "name": "Thailand",
    "code": "TH"
  },
  {
      "name": "Timor-Leste",
    "code": "TL"
  },
  {
      "name": "Togo",
    "code": "TG"
  },
  {
      "name": "Tokelau",
    "code": "TK"
  },
  {
      "name": "Tonga",
    "code": "TO"
  },
  {
      "name": "Trinidad and Tobago",
    "code": "TT"
  },
  {
      "name": "Tunisia",
    "code": "TN"
  },
  {
      "name": "Turkey",
    "code": "TR"
  },
  {
      "name": "Turkmenistan",
    "code": "TM"
  },
  {
      "name": "Turks and Caicos Islands",
    "code": "TC"
  },
  {
      "name": "Tuvalu",
    "code": "TV"
  },
  {
      "name": "Uganda",
    "code": "UG"
  },
  {
      "name": "Ukraine",
    "code": "UA"
  },
  {
      "name": "United Arab Emirates",
    "code": "AE"
  },
  {
      "name": "United Kingdom",
     "code": "GB"
  },
  {
    "name":"UnitedKingdom",
    "code":"GB"
  },
  {
      "name": "United States",
    "code": "US"
  },
  {
      "name": "United States Minor Outlying Islands",
    "code": "UM"
  },
  {
      "name": "Uruguay",
    "code": "UY"
  },
  {
      "name": "Uzbekistan",
    "code": "UZ"
  },
  {
      "name": "Vanuatu",
    "code": "VU"
  },
  {
      "name": "Venezuela",
    "code": "VE"
  },
  {
      "name": "Viet Nam",
    "code": "VN"
  },
  {
      "name": "Virgin Islands, British",
    "code": "VG"
  },
  {
      "name": "Virgin Islands, U.S.",
    "code": "VI"
  },
  {
      "name": "Wallis and Futuna",
    "code": "WF"
  },
  {
      "name": "Western Sahara",
    "code": "EH"
  },
  {
      "name": "Yemen",
    "code": "YE"
  },
  {
      "name": "Zambia",
    "code": "ZM"
  },
  {
      "name": "Zimbabwe",
    "code": "ZW"
  },
  {
        "name":"Northern Ireland",
        "code":"GB"

  },
  {
        "name":"Great Britain",
        "code":"GB"
  }

]';
    foreach (json_decode($country) as $key => $value) {
        if ($countryName === $value->name) {
            return $value->code;
        }
    }
}


function money_format($price)
{
   $number= '£'.number_format($price,2);


    return  $number;

}


