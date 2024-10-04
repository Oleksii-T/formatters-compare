<?php

use Illuminate\Support\Str;

// generale unique slug based on provided array
if (!function_exists('makeSlug')) {
    function makeSlug($str, $check = [])
    {
        $slug = Str::slug($str);

        if (in_array($slug, $check)) {
            $rand = 2;
            while (true) {
                if (!in_array($slug . '-' . $rand, $check)) {
                    $slug = $slug . '-' . $rand;
                    break;
                }
                $rand++;
            }
        }

        return $slug;
    }
}

// get alphabet representation of integer
if (!function_exists('intToAlphabet')) {
    function intToAlphabet($number) {
        $result = '';
        while ($number > 0) {
            $remainder = ($number - 1) % 26;
            $result = chr(65 + $remainder) . $result; // 65 is the ASCII code for 'A'
            $number = ($number - $remainder - 1) / 26;
        }
        return $result;
    }
}

if (!function_exists('isdev')) {
    function isdev() {
        if (\Illuminate\Support\Facades\Facade::getFacadeApplication() !== null) {
            // late checks will not set APP_DEBUG

            $user = auth()->user();

            if (isset($_GET['rmforcedebug63']) && $user?->isAdmin()) {
                return true;
            }
    
            if (isset($_COOKIE['debug']) && $user?->isAdmin()) {
                return true;
            }

            if ($user) {
                cache()->forget('debug_emabled_for_users');
                $debuggers = cache()->remember('debug_emabled_for_users', 60*1, function () {
                    return \App\Models\Setting::get('debug_emabled_for_users');
                });
                $debuggers = json_decode($debuggers);
                if (in_array($user->id, $debuggers)) {
                    return true;
                } 
            }
        }

        $ips = [
            '194.44.179.84', // AT
            '127.0.0.1',
        ];

        try {
            return in_array(request()->ip(), $ips);
        } catch (\Throwable $th) {}

        return false;
    }
}


if (!function_exists('devdd')) {
    function devdd($text, $array=[]) {
        if (!isdev()) {
            return;
        }

        dd($text, $array);
    }
}

if (!function_exists('devdump')) {
    function devdump($text, $array=[]) {
        if (!isdev()) {
            return;
        }

        dump($text, $array);
    }
}

// transform snake\kebab\camel case to user friendly string
if (!function_exists('readable')) {
    function readable(string|null $s, $upperCaseEach=false) {
        if (!$s) {
            return $s;
        }
        if (str_contains($s, '-')) {
            $s = str_replace('-', ' ', $s);// kebab case
        } else if (str_contains($s, '_')) {
            $s = str_replace('_', ' ', $s);// snake case
        } else {
            $s = strtolower(preg_replace('/(?<!^)[A-Z]/', ' $0', $s));// camel case
        }

        return $upperCaseEach ? ucwords($s) : ucfirst($s);
    }
}

// route with appended current query params
if (!function_exists('qroute')) {
    function qroute($route, $data=null) {
        $route = $data ? route($route, $data) : route($route);
        $params = $_GET;
        $q = http_build_query($_GET);
        $route .= '?';
        $route .= $q;

        return $route;
    }
}

// print some message to separate log file
if (!function_exists('dlog')) {
    function dlog(string $text, array $array=[]) {
        return \Log::channel('dev')->info($text, $array);
    }
}

// add flash notif for user
if (!function_exists('flash')) {
    function flash(string $message, $type=true) {
        session()->flash($type ? 'message-success' : 'message-error', $message);
    }
}

// get current flash to display
if (!function_exists('getActiveFlash')) {
    function getActiveFlash() {
        $m = session()->has('message-success');
        if ($m) {
            return [
                'level' => 'success',
                'message' => session()->get('message-success')
            ];
        }

        $m = session()->has('message-error');
        if ($m) {
            return [
                'level' => 'error',
                'message' => session()->get('message-error')
            ];
        }

        $m = session('status');
        if ($m) {
            return [
                'level' => 'success',
                'message' => $m
            ];
        }

        return null;
    }
}

// get localized countries array
if (!function_exists('countries')) {
    function countries() {
        foreach (config('countries') as $c) {
            $countries[$c] = trans("countries.$c");
        }

        asort($countries);

        return $countries;
    }
}

// get currencies array
if (!function_exists('currencies')) {
    function currencies($code=null) {
        $all = config('currencies');

        return $code ? $all[$code] : $all;
    }
}

// get general info for activity log creation
if (!function_exists('infoForActivityLog')) {
    function infoForActivityLog() {
        $location = null;
        $ip = request()->ip();
        $agentString = request()->header('User-Agent');
        $agentInfo = [];
        $requestParams = request()->all();
        unset($requestParams['_token']);

        if ($ip != '127.0.0.1') {
            try {
                // Get the current log level
                // $originalLogLevel = \Illuminate\Support\Facades\Log::getLogger()->getHandlers()[0]->getLevel();

                // Set the log level to emergency to suppress all other log levels
                // \Illuminate\Support\Facades\Log::getLogger()->getHandlers()[0]->setLevel(\Monolog\Logger::EMERGENCY);

                $location = config('location.testing.enabled')
                    ? null
                    : \Stevebauman\Location\Facades\Location::get($ip)->countryCode;

                // Restore the original log level
                // \Illuminate\Support\Facades\Log::getLogger()->getHandlers()[0]->setLevel($originalLogLevel);
            } catch (\Throwable $th) {
                \Log::error("Can not detect location for activity log ($ip): " . $th->getMessage());
            }
        }

        try {
            $agent = new \Jenssegers\Agent\Agent();
            $agent->setUserAgent($agentString);
            $agentInfo = [
                'languages' => $agent->languages(),
                'device' => $agent->device(),
                'platform' => $agent->platform(),
                'platform_version' => $agent->version($agent->platform()),
                'browser' => $agent->browser(),
                'browser_version' => $agent->version($agent->browser()),
                'is_robot' => $agent->isRobot(),
                'robot' => $agent->robot(),
            ];
        } catch (\Throwable $th) {
            \Log::error('Can not detect agent info for activity log: ' . $th->getMessage());
        }

        $info = [
            'ip' => $ip,
            'url' => request()->fullUrl(),
            'from' => request()->headers->get('referer'),
            'location' => $location,
            'agent' => $agentString,
            'agent_info' => $agentInfo,
            'request_params' => $requestParams,
        ];

        return ['general_info' => $info];
    }
}

// case insensitive array_unique
if (!function_exists('array_iunique')) {
    function array_iunique($array) {
        return array_intersect_key(
            $array,
            array_unique( array_map( "strtolower", $array ) )
        );
    }
}

// get string from exeption
if (!function_exists('exceptionAsString')) {
    function exceptionAsString($th) {
        return $th->getMessage() . '. Trace: ' . $th->getTraceAsString();
    }
}

// get readable last json error
if (!function_exists('_json_last_error')) {
    function _json_last_error()
    {
        switch (json_last_error()) {
            case JSON_ERROR_NONE:
                return 'No errors';
            case JSON_ERROR_DEPTH:
                return 'Maximum stack depth exceeded';
            case JSON_ERROR_STATE_MISMATCH:
                return 'Underflow or the modes mismatch';
            case JSON_ERROR_CTRL_CHAR:
                return 'Unexpected control character found';
            case JSON_ERROR_SYNTAX:
                return 'Syntax error, malformed JSON';
            case JSON_ERROR_UTF8:
                return 'Malformed UTF-8 characters, possibly incorrectly encoded';
            default:
                return 'Unknown error';
        }
    }
}

if (!function_exists('strposX')) {
    function strposX($haystack, $needle, $number = 0)
    {
        return strpos($haystack, $needle,
            $number > 1 ?
            strposX($haystack, $needle, $number - 1) + strlen($needle) : 0
        );
    }
}

if (!function_exists('sanitizeHtml')) {
    function sanitizeHtml($html)
    {
        // dlog("sanitizeHtml $html"); //! LOG
        // return $html;
        $html = str_replace('<br>', '', $html);
        $html = str_replace('<p></p>', '', $html);
        $html = str_replace(' style="font-family: &quot;Source Sans Pro&quot;, -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, Roboto, &quot;Helvetica Neue&quot;, Arial, sans-serif, &quot;Apple Color Emoji&quot;, &quot;Segoe UI Emoji&quot;, &quot;Segoe UI Symbol&quot;; font-size: 1rem;"', '', $html);
        $html = str_replace(' style="font-family: &quot;Source Sans Pro&quot;, -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, Roboto, &quot;Helvetica Neue&quot;, Arial, sans-serif, &quot;Apple Color Emoji&quot;, &quot;Segoe UI Emoji&quot;, &quot;Segoe UI Symbol&quot;; font-size: 1rem; font-weight: bolder;"', '', $html);
        $html = preg_replace('#<span[^>]*>(.*?)</span>#i', '$1', $html);
        $html = preg_replace('/<table[^>]+>/', '<table>', $html);

        if (str_contains($html, '</table>')) {
            $dom = new \DOMDocument;
            $html = str_replace('&nbsp;', '', $html);
            $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

            $xpath = new \DOMXPath($dom);

            $tds = $xpath->query('//td');

            foreach ($tds as $td) {
                // Remove 'br' elements
                $brs = $td->getElementsByTagName('br');
                for ($i = $brs->length - 1; $i >= 0; $i--) {
                    $brs->item($i)->parentNode->removeChild($brs->item($i));
                }
                // Unwrap 'p' elements
                foreach ($td->getElementsByTagName('p') as $p) {
                    while ($p->childNodes->length > 0) {
                        $p->parentNode->insertBefore($p->childNodes->item(0), $p);
                    }
                    $p->parentNode->removeChild($p);
                }
                // Wrap text nodes in 'span' elements
                // foreach ($td->childNodes as $child) {
                //     $nodeValue = trim($child->nodeValue);
                //     if ($child instanceof \DOMText && $nodeValue) {
                //         $span = $dom->createElement('span', $nodeValue);
                //         $td->replaceChild($span, $child);
                //     }
                // }
            }

            // Wrap 'b' elements inside 'td' elements with 'span' elements
            // $bs = $xpath->query('//td/b[not(parent::span)]');
            // foreach ($bs as $b) {
            //     $span = $dom->createElement('span');
            //     $b->parentNode->replaceChild($span, $b);
            //     $span->appendChild($b);
            // }

            $html = $dom->saveHTML();
        }

        $html = str_replace('<?xml encoding="utf-8" ?>', '', $html);
        $html = str_replace('<!--?xml encoding="utf-8" ?-->', '', $html);
        $html = str_replace(' style="font-family: &quot;Source Sans Pro&quot;, -apple-system, BlinkMacSystemFont, &quot;Segoe UI&quot;, Roboto, &quot;Helvetica Neue&quot;, Arial, sans-serif, &quot;Apple Color Emoji&quot;, &quot;Segoe UI Emoji&quot;, &quot;Segoe UI Symbol&quot;; font-size: 1rem;"', '', $html);

        // dlog(" result: $html"); //! LOG

        return $html;
    }
}

if (!function_exists('countReplies')) {
    function countReplies($replies, $count = 0)
    {
        $count += $replies->count();

        foreach ($replies as $r) {
            $count = countReplies($r->replies, $count);
        }

        return $count;
    }
}

if (!function_exists('removePageQuery')) {
    function removePageQuery($uri)
    {
        return $uri;
    }
}
