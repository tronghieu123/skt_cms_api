<?php

use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

if (!function_exists('print_arr')) {
    function print_arr($array = array()){
        echo "<pre>";
        @print_r($array);
        echo "</pre>";
    }
}

if (!function_exists('mongodb_id')) {
    function mongodb_id($objectId){
        $objectId = new MongoDB\BSON\ObjectID($objectId);
        return $objectId->jsonSerialize()['$oid'];
    }
}

if (!function_exists('mongo_time')) {
    function mongo_time($time = ''){
        if ($time == '') {
            $time = time();
        }
        return new MongoDB\BSON\UTCDateTime($time * 1000);
    }
}

if (!function_exists('convert_date')) {
    function convert_date($date){
        $timezone = request()->header('timezone') ?? 'Asia/Ho_Chi_Minh';
        $temp = Carbon\Carbon::parse($date, $timezone)->setTimezone('UTC')->getTimestamp();
        return mongo_time($temp);
    }
}

if (!function_exists('convert_date_time')) {
    function convert_date_time($date_time){
        $timezone = request()->header('timezone') ?? 'Asia/Ho_Chi_Minh';
        $temp = Carbon\Carbon::parse($date_time, $timezone)->setTimezone('UTC')->getTimestamp();
        return mongo_time($temp);
    }
}

if (!function_exists('convert_date_search')) {
    function convert_date_search($date){
        $date = explode('-', $date);
        return $date[2].'-'.$date[1].'-'.$date[0];
    }
}

if (!function_exists('parseTimestamp')) {
    function parseTimestamp($cursor = array()){
        foreach ($cursor as $doc) {
            return (int) round($doc / 1000);
        }
    }
}

if (!function_exists('error_login')) {
    function error_login(){
        return response()->json([
            'code' => Response::HTTP_UNAUTHORIZED,
            'message' => __('UNAUTHORIZED')
        ]);
    }
}

if (!function_exists('error_method')) {
    function error_method()
    {
        return response()->json([
            'code' => Response::HTTP_METHOD_NOT_ALLOWED,
            'message' => __('method_not_allowed')
        ]);
    }
}

if (!function_exists('error_bad_request')) {
    function error_bad_request($mess='') {
        return response()->json([
            'code' => Response::HTTP_BAD_REQUEST,
            'message' => $mess=='' ? __('an_error_occurred') : $mess
        ]);
    }
}


if (!function_exists('error_data')) {
    function error_data()
    {
        return response()->json([
            'code' => Response::HTTP_NOT_FOUND,
            'message' => __('an_error_occurred')
        ]);
    }
}

if (!function_exists('error_forbidden')) {
    function error_forbidden($mess='')
    {
        return response()->json([
            'code' => Response::HTTP_FORBIDDEN,
            'message' => $mess=='' ? __('error_token_user') : $mess
        ]);
    }
}

if (!function_exists('response_custom')) {
    function response_custom($mess = '', $error = 0, $data = array(), $code = 200) {
        if($error == 1 && $code == 200) {
            $code = 400;
        }
        $response = [
            'error' => $error,
            'code' => ($code == 200) ? Response::HTTP_OK : $code,
            'message' => !empty($mess) ? $mess : (($code == 200) ? 'Thành công!' : 'Thất bại!'),
        ];
        if(!empty($data)){
            $response['data'] = $data;
        }
        if(!empty($data['other'])) {
            foreach ($data['other'] as $k => $v){
                $response[$k] = $v;
            }
            unset($response['data']['other']);
        }
        return response()->json($response);
    }
}

if (!function_exists('response_pagination')) {
    function response_pagination($data = array(), $mess = 'Thành công', $error = 0, $code = 200){
        if($error == 1 && $code == 200){
            $code = 400;
        }
        $response = [
            'error' => $error,
            'code' => ($code == 200) ? Response::HTTP_OK : $code,
            'message' => !empty($mess) ? $mess : (($code == 200) ? 'Thành công!' : 'Thất bại!'),
            'data' => $data['data'] ?? [],
            'from' => $data['from'] ?? "",
            'to' => $data['to'] ?? "",
            'total' => $data['total'] ?? "",
            'total_page' => $data['last_page'] ?? "",
            'current_page' => $data['current_page'] ?? "",
            'per_page' => $data['per_page'] ?? "",
            'links' => $data['links']
        ];
        if(!empty($data['other'])){
            foreach ($data['other'] as $k => $v){
                $response[$k] = $v;
            }
        }
        return response()->json($response);
    }
}

if (!function_exists('input_editor')) {
    function input_editor($str){
        $str = htmlspecialchars($str, ENT_QUOTES);
        return $str;
    }
}

if (!function_exists('input_editor_decode')) {
    function input_editor_decode($str){
        $str = htmlspecialchars_decode($str, ENT_QUOTES);
        $str = html_entity_decode($str);
        return $str;
    }
}

if (!function_exists('string_cut')) {
    function string_cut($str, $max_length){
        if (strlen($str) > $max_length) {
            $str = substr($str, 0, $max_length);
            $pos = strrpos($str, " ");
            if ($pos === false) {
                return substr($str, 0, $max_length) . "...";
            }
            return substr($str, 0, $pos) . "...";
        } else {
            return $str;
        }
    }
}

if (!function_exists('short')) {
    function short($str, $max_length = 200){
        $str = input_editor_decode($str);
        $str = strip_tags($str);
        $str = string_cut($str, $max_length);
        return $str;
    }
}

if (!function_exists('random_str')) {
    function random_str($len = 5, $type = ''){
        $u = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $l = 'abcdefghijklmnopqrstuvwxyz';
        $n = '0123456789';
        $s = $u . $l . $n;
        switch ($type) {
            case 'n':
                $s = $n;
                break;
            case 'l':
                $s = $l;
                break;
            case 'u':
                $s = $u;
                break;
            case 'un':
                $s = $u . $n;
                break;
            case 'ul':
                $s = $u . $l;
                break;
            case 'ln':
                $s = $l . $n;
                break;
        }
        ;
        $randomString = '';
        for ($i = 0; $i < $len; $i++) {
            $randomString .= substr($s, (rand() % (strlen($s))), 1);
        }
        return $randomString;
    }
}

if (!function_exists('random_str_db')) {
    function random_str_db($db_info, $len, $type, $code = ''){
        $database = $db_info['database'] ?? 'sky_cms';
        $db = DB::connection($database);
        if(empty($db_info['table']) || empty($db_info['field'])){
            return '';
        }
        if($code){
            $check_exist = $db->collection($db_info['table'])->where($db_info['field'], $code)->value($db_info['field']);
            if($check_exist){
                $code = random_str($len, $type);
                random_str_db($db_info, $len, $type, $code);
            }
        }else{
            $code = random_str($len, $type);
            $check_exist = $db->collection($db_info['table'])->where($db_info['field'], $code)->value($db_info['field']);
            if($check_exist){
                random_str_db($db_info, $len, $type, $code);
            }
        }
        return $code;
    }
}

if (!function_exists('rmkdir')) {
    function rmkdir($dir = "", $chmod = 0777, $path_folder = "uploads"){
        global $ims;

        $chmod     = ($chmod == 'auto') ? 0777 : $chmod;
        $arr_allow = array("uploads", "thumbs", "thumbs");

        $path_folder = (in_array($path_folder, $arr_allow)) ? $path_folder : 'uploads';
        $path        = $ims->conf["rootpath_web"] . $path_folder;
        $path        = rtrim(preg_replace(array("/\\\\/", "/\/{2,}/"), "/", $path), "/");

        if (is_dir($path . '/' . $dir) && file_exists($path . '/' . $dir)) {
            return true;
        }

        $path_thumbs = $path . '/' . $dir;
        $path_thumbs = rtrim(preg_replace(array("/\\\\/", "/\/{2,}/"), "/", $path_thumbs), "/");

        $oldumask = umask(0);
        if ($path && !file_exists($path)) {
            mkdir($path, $chmod, true); // or even 01777 so you get the sticky bit set
        }
        if ($path_thumbs && !file_exists($path_thumbs)) {
            mkdir($path_thumbs, $chmod, true);
            //mkdir($path_thumbs, $chmod, true) or die("$path_thumbs cannot be found"); // or even 01777 so you get the sticky bit set
        }
        umask($oldumask);

        return true;
    }
}

if (!function_exists('hex2rgb')) {
    function hex2rgb($hexStr, $returnAsString = false, $seperator = ','){
        $hexStr   = preg_replace("/[^0-9A-Fa-f]/", '', $hexStr); // Gets a proper hex string
        $rgbArray = array();
        if (strlen($hexStr) == 6) { //If a proper hex code, convert using bitwise operation. No overhead... faster
            $colorVal          = hexdec($hexStr);
            $rgbArray['red']   = 0xFF & ($colorVal >> 0x10);
            $rgbArray['green'] = 0xFF & ($colorVal >> 0x8);
            $rgbArray['blue']  = 0xFF & $colorVal;
        } elseif (strlen($hexStr) == 3) { //if shorthand notation, need some string manipulations
            $rgbArray['red']   = hexdec(str_repeat(substr($hexStr, 0, 1), 2));
            $rgbArray['green'] = hexdec(str_repeat(substr($hexStr, 1, 1), 2));
            $rgbArray['blue']  = hexdec(str_repeat(substr($hexStr, 2, 1), 2));
        } else {
            return false; //Invalid hex color code
        }
        return $returnAsString ? implode($seperator, $rgbArray) : $rgbArray; // returns the rgb string or the associative array
    }
}

/*
 * RGB-Colorcodes(i.e: 255 0 255) to HEX-Colorcodes (i.e: FF00FF)
 */

if (!function_exists('rgb2hex')) {
    function rgb2hex($rgb){
        if (!is_array($rgb) || count($rgb) != 3) {
            echo "Argument must be an array with 3 integer elements";
            return false;
        }
        for ($i = 0; $i < count($rgb); $i++) {
            if (strlen($hex[$i] = dechex($rgb[$i])) == 1) {
                $hex[$i] = "0" . $hex[$i];
            }
        }
        return implode('', $hex);
    }
}

if (!function_exists('ims_json_encode')) {
    function ims_json_encode(&$value, $default = ''){
        return ((!empty($value)) ? json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $default);
    }
}

if (!function_exists('ims_json_decode')) {
    function ims_json_decode(&$value, $default = ''){
        $output = $default;
        if (!empty($value)) {
            $output = json_decode($value, true);
            if (empty($output)) {
                $value  = str_replace(["\r\n", "\t"], ['\r\n', '\t'], $value);
                $value  = preg_replace('/([^{,:\[])"(?![},:\]])/', "$1" . '\"' . "$2", $value);
                $value  = str_replace('\\\\', '\\', $value);
                $output = json_decode($value, true);
            }
        }
        return $output;
    }
}

if (!function_exists('link2hex')) {
    function link2hex($str = '', $len = 4){
        global $ims;
        $output = '';
        if (!empty($str)) {
            $str    = base64_encode($str);
            $str    = bin2hex($str);
            $str    = str_split($str, $len);
            $str    = implode('-', $str);
            $output = $str;
        }
        return $output;
    }
}

if (!function_exists('hex2param')) {
    function hex2param($url = ''){
        global $ims;
        $output = array();
        if (!empty($url)) {
            $code = str_replace('-', '', $url);
            if (ctype_xdigit($code) && strlen($code) % 2 == 0) {
                $query = hex2bin($code);
                $query = base64_decode($query);
                parse_str(str_replace('?', '', $query), $param);
                foreach ($param as $key => $value) {
                    $output[$key] = $value;
                }
            }
        }
        return $output;
    }
}

if (!function_exists('encrypt_decrypt')) {
    function encrypt_decrypt($action, $string, $secret_key, $secret_iv){
        $output         = false;
        $encrypt_method = "AES-256-CBC";
        // $secret_key = 'This is my secret key';
        // $secret_iv = 'This is my secret iv';
        // hash
        $key = hash('sha256', $secret_key);
        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $iv = substr(hash('sha256', $secret_iv), 0, 16);
        if ($action == 'encrypt') {
            $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
            // $output = bin2hex($output);
        } else if ($action == 'decrypt') {
            // $output = openssl_decrypt($this->base64_decode($string), $encrypt_method, $key, 0, $iv);
            // $string = hex2bin($string);
            $output = openssl_decrypt($string, $encrypt_method, $key, 0, $iv);
        }
        return $output;
    }
}

if (!function_exists('get_youtube_code')) {
    function get_youtube_code($url){
        preg_match('/(?<=(?:v|i)=)[a-zA-Z0-9-]+(?=&)|(?<=(?:v|i)\/)[^&\n]+|(?<=embed\/)[^"&\n]+|(?<=(?:v|i)=)[^&\n]+|(?<=youtu.be\/)[^&\n]+/', $url, $match);
        $pic_code = print_r($match[0], TRUE);
        return $pic_code;
    }
}

if (!function_exists('img2Webp')) {
    function img2Webp($path = ''){
        $file = storage_path('app/public/uploads/' . $path);
        if (!$file) {
            // $file_webp = str_replace($extension, 'webp', storage_path('app/public/uploads/').$path);
            // if(file_exists($file_webp)){
            //     return str_replace($extension, 'webp', $path);
            // }else{
            user_error("Unable to open image file");
            return false;
            // }
        }
        $code      = 200;
        $extension = pathinfo(parse_url($file, PHP_URL_PATH), PATHINFO_EXTENSION);
        if (in_array($extension, ["jpeg", "jpg", "png"])) {
            $file_webp = str_replace($extension, 'webp', storage_path('app/public/uploads/') . $path);
            if (file_exists($file_webp)) {
                return str_replace($extension, 'webp', $path);
            } else {
                $img_old = storage_path('app/public/uploads/') . '/' . $path;
                $im      = imagecreatefromstring(file_get_contents($img_old));
                imagepalettetotruecolor($im);
                $new_webp = preg_replace('"\.(jpg|jpeg|png)$"', '.webp', $img_old);
                unlink($img_old);
                return imagewebp($im, $new_webp, 100);
            }
        } else {
            return $path;
        }
    }
}

if (!function_exists('get_lang')) {
    function get_lang($key = '', $module = 'ims', $file = 'global', $arr_replace = array()){
        $output = trans($module . '::' . $file . '.' . $key);
        if (count($arr_replace)) {
            $arr_key   = array_keys($arr_replace);
            $arr_value = array_values($arr_replace);
            $output    = str_replace($arr_key, $arr_value, $output);
        }
        return $output;
    }
}

if (!function_exists('secondsToMinutes')) {
    function secondsToMinutes($seconds_time){
        if ($seconds_time < 60 * 60) {
            return gmdate('i:s', $seconds_time);
        } else {
            return sprintf("%02.2d:%02.2d", floor($seconds_time / 60), $seconds_time % 60);
        }
    }
}

if (!function_exists('get_time_format')) {
    function get_time_format($number){
        $out    = "";
        $day    = 24 * 60 * 60;
        $hour   = 60 * 60;
        $minute = 60;
        if ($number >= $day) {
            $tmp    = floor($number / $day);
            $number -= $tmp * $day;
            $out .= '<span>' . $tmp . '</span> ' . trans('Ims::global.day');
        }
        if ($number >= $hour) {
            if ($out)
                $out .= ', ';
            $tmp    = floor($number / $hour);
            $number -= $tmp * $hour;
            $out .= '<span>' . $tmp . '</span> ' . trans('Ims::global.hour');
        }
        if ($number >= $minute) {
            if ($out)
                $out .= ', ';
            $tmp    = floor($number / $minute);
            $number -= $tmp * $minute;
            $out .= '<span>' . $tmp . '</span> ' . trans('Ims::global.minute');
        }
        if ($out)
            $out .= ', ';
        $out .= '<span>' . $number . '</span> ' . trans('Ims::global.second');
        return $out;
    }
}

if (!function_exists('number_format_short')) {
    function number_format_short($n, $precision = 1){
        if ($n < 900) {
            $n_format = number_format($n, $precision);
            $suffix   = '';
        } else if ($n < 900000) {
            $n_format = number_format($n / 1000, $precision);
            $suffix   = 'K';
        } else if ($n < 900000000) {
            $n_format = number_format($n / 1000000, $precision);
            $suffix   = 'M';
        } else if ($n < 900000000000) {
            $n_format = number_format($n / 1000000000, $precision);
            $suffix   = 'B';
        } else {
            $n_format = number_format($n / 1000000000000, $precision);
            $suffix   = 'T';
        }
        if ($precision > 0) {
            $dotzero  = '.' . str_repeat('0', $precision);
            $n_format = str_replace($dotzero, '', $n_format);
        }
        return $n_format . $suffix;
    }
}

if (!function_exists('get_domain')) {
    function get_domain($domain){
        if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,10})$/i', $domain, $matches)) {
            return $matches['domain'];
        } else {
            return $domain;
        }
    }
}

if (!function_exists('get_subdomains')) {
    function get_subdomains($domain){
        $subdomains = $domain;
        $domain     = get_domain($subdomains);
        $subdomains = rtrim(strstr($subdomains, $domain, true), '.');
        return $subdomains;
    }
}

if (!function_exists('convertSecondsToMinutes')) {
    function convertSecondsToMinutes($seconds){
        return number_format($seconds / 60, 1);
    }
}

if (!function_exists('explode_custom')) {
    function explode_custom($text, $delimiter = ','){
        return array_filter(array_map('trim', explode($delimiter, $text)));
    }
}

if (!function_exists('get_arr_in')) {
    function get_arr_in($arr_element, $arr_in, $type = 'add_edit_item'){
        $edit = 0;
        if(request('sub') == 'edit' && !empty(request('item'))){
            $edit = 1;
        }
        if ($type == 'add_edit_item') {
            foreach ($arr_element as $k => $v) {
                if($edit == 1 && !empty($v['addonly'])){
                    continue;
                }
                if (!isset($arr_in[$k])) {
                    if (isset($v['form_type'])) {
                        if($v['form_type'] == 'checkbox') {
                            $arr_in[$k] = 0;
                        }elseif($v['form_type'] == 'select_js' && !empty($v['list_key'])) {
                            foreach ($v['list_key'] as $k1 => $v1) {
                                $type        = !empty($v1['type']) ? $v1['type'] : '';
                                $arr_in[$k1] = convert_column_type($arr_in[$k1], $type);
                            }
                        }elseif($v['form_type'] == 'select' && isset($v['multiple']) && $v['multiple'] == true){
                            $arr_in[$k] = [];
                        }else{
                            $type        = !empty($v['type']) ? $v['type'] : '';
                            if($v['form_type'] == 'editor'){
                                $arr_in[$k] = htmlspecialchars($arr_in[$k]);
                            }
                            $arr_in[$k]  = convert_column_type('', $type);
                        }
                    } elseif (isset($v['only']) && $v['only'] == 'add') {
                        if ($v['auto'] == 'time') {
                            $arr_in[$k] = mongo_time();
                        } else {
                            if (!empty($v['type'])) {
                                $val        = !empty($v['auto']) ? $v['auto'] : '';
                                $arr_in[$k] = convert_column_type($val, $v['type']);
                            } else {
                                $arr_in[$k] = $v['auto'];
                            }
                        }
                    }
                } else {
                    if (isset($v['form_type'])) {
                        switch ($v['form_type']) {
                            case 'arr_custom':
                                foreach ($arr_in[$k] as $k_item => $value) {
                                    foreach ($v['list_key'] as $k_custom) {
                                        if ($k_custom['form_type'] == 'editor') {
                                            $value[$k_custom['key']] = htmlspecialchars($value[$k_custom['key']]);
                                            $arr_in[$k][$k_item]     = $value;
                                        } elseif ($k_custom['form_type'] == 'arr_picture') {
                                            $value[$k_custom['key']] = explode(',', $value[$k_custom['key']][0]);
                                            $arr_in[$k][$k_item]     = $value;
                                        } else {
                                            $arr_in[$k][$k_item] = $value;
                                        }
                                    }
                                }
                                $arr_in[$k] = json_encode($arr_in[$k]); // arr_custom lưu thành json
                                break;
                            case 'datepicker':
                                $arr_in[$k] = convert_date($arr_in[$k]);
                                break;
                            case 'datetimepicker':
                                $arr_in[$k] = convert_date_time($arr_in[$k]);
                                break;
                            case 'datemonthpicker':
                                $arr_in[$k] = str_replace('-','/', $arr_in[$k]);
                                break;
                            case 'select':
                                if (isset($v['multiple']) && $v['multiple'] == true) {
                                    $arr_in[$k] = (!empty($arr_in[$k]) && is_array($arr_in[$k])) ? array_values(array_unique(array_filter($arr_in[$k]))) : [];
                                }else{
                                    if(!empty($v['type'])){
                                        $arr_in[$k] = convert_column_type($arr_in[$k], $v['type']);
                                    }
                                }
                                break;
                            case 'arr_picture':
                                $arr_in[$k] = json_encode(explode(',', $arr_in[$k][0])); // danh sách hình ảnh lưu thành json
                                break;
                            case 'password':
                                if(!empty($arr_in[$k])){
                                    $arr_in[$k] = Hash::make($arr_in[$k]);
                                }else{
                                    unset($arr_in[$k]);
                                }
                                break;
                            case 'friendly_link':
                                $db_info = DB::table('gateway')
                                    ->where('type', request('root'))
                                    ->where('module', request('mod'))
                                    ->where('action', request('act'))
                                    ->first(['database', 'table', 'module', 'action']);
                                $link = !empty($arr_in[$k]) ? $arr_in[$k] : (!empty($arr_in['title']) ? $arr_in['title'] : time());
                                $arr_in[$k] = generateSlug($link, $db_info);
                                break;
                            default:
                                $type = !empty($v['type']) ? $v['type'] : '';
                                if($v['form_type'] == 'editor'){
                                    $arr_in[$k] = htmlspecialchars($arr_in[$k]);
                                }
                                $arr_in[$k] = convert_column_type($arr_in[$k], $type);
                                break;
                        }
                    } else {
                        $type       = !empty($v['type']) ? $v['type'] : '';
                        $arr_in[$k] = convert_column_type($arr_in[$k], $type);
                    }
                }
            }
            $arr_in['admin_id'] = Config('admin_id');
            unset($arr_in['re_password']);
        } elseif ($type == 'edit_limit') {
            //        foreach ($arr_in as $k => $v){
//            if(!empty($arr_element[$k]['type'])){
//                $arr_in[$k] = convert_column_type($v, $arr_element[$k]['type']);
//            }else{
//                $arr_in[$k] = convert_column_type($v);
//            }
//        }
//        $arr_in['updated_at'] = mongo_time();
//        unset($arr_in['is_show']);
        } elseif ($type == 'edit_list') {
            // Chỉnh sửa nội dung các checkbox, show_order ngoài trang danh sách
            foreach ($arr_element as $k => $v) {
                if($edit == 1 && !empty($v['addonly'])){
                    continue;
                }
                if (isset($v['form_type']) && in_array($v['form_type'], array('input', 'checkbox', 'textarea'))) {
                    if (!isset($arr_in[$k])) {
                        $arr_in[$k] = 0;
                    } else {
                        $type       = !empty($v['type']) ? $v['type'] : '';
                        $arr_in[$k] = convert_column_type($arr_in[$k], $type);
                    }
                }
            }
            $arr_in['updated_at'] = mongo_time();
            unset($arr_in['is_show'], $arr_in['created_at']);
        }
        return $arr_in;
    }
}

if (!function_exists('convert_column_type')) {
    function convert_column_type($value, $type = ''){
        switch ($type) {
            case 'int':
                return (int) $value;
                break;
            case 'float':
                return (float) $value;
                break;
            case 'double':
                return (double) $value;
                break;
            case 'array':
                return $value;
                break;
            default:
                return ($value) ? (string) $value : '';
                break;
        }
    }
}

if (!function_exists('generateSlug')) {
    function generateSlug($link, $db_info){
        $db   = DB::connection($db_info['database']);
        $slug = Str::slug($link);
        //    $check_exist = $db->where('module', $db_info['module'])
//                    ->where('action', $db_info['action'])
//                    ->where('dbtable', $db_info['table'])
//                    ->where('friendly_link', $slug)
//                    ->first(['_id']);
        if (request('sub') == 'add') {
            $check_exist = $db->collection('friendly_link')
                ->where('friendly_link', $slug)
                ->first(['friendly_link']);
        } elseif (request('sub') == 'update') {
            $check_exist = $db->collection('friendly_link')
                ->where('friendly_link', $slug)
                ->where('table_id', '!=', request('item'))
                ->first(['friendly_link']);
        }
        if ($check_exist) {
            $slug = explode('-', $slug);
            if (is_numeric(end($slug))) {
                unset($slug[count($slug) - 1]);
            }
            $slug     = implode('-', $slug); // Slug ban đầu
            $link_cur = explode('-', $check_exist['friendly_link']);
            if (is_numeric(end($link_cur))) {
                $slug = $slug . '-' . (end($link_cur) + 1);
            } else {
                $slug = $slug . '-1';
            }
            $slug = generateSlug($slug, $db_info);
        } else {
            if (request('sub') == 'add') {
                $arr_in = array(
                    'friendly_link' => $slug,
                    'module' => $db_info['module'],
                    'action' => $db_info['action'],
                    'table' => $db_info['table'],
                    'table_id' => '',
                    'created_at' => mongo_time(),
                    'updated_at' => mongo_time()
                );
                $db->collection('friendly_link')->insert($arr_in);
            } elseif (request('sub') == 'update') {
                $update = array(
                    'friendly_link' => $slug,
                    'updated_at' => mongo_time()
                );
                $db->collection('friendly_link')
                    ->where('table_id', request('item'))
                    ->where('module', $db_info['module'])
                    ->where('action', $db_info['action'])
                    ->where('table', $db_info['table'])
                    ->update($update);
            }
        }
        return $slug;
    }
}

if (!function_exists('formatData')) {
    function formatData($data, $arr_convert = [], $replace_data = []){
        $text = [];
        foreach ($data as $k => $item) {
            foreach ($item as $k1 => $v) {
                switch ($k1) {
                    case '_id':
                        $data[$k][$k1] = mongodb_id($v);
                        break;
                    case 'created_at':
                    case 'updated_at':
                        $data[$k][$k1] = !empty($v) ? parseTimestamp($v) : '';
                        break;
                    default:
                        if (!empty($arr_convert['date']) && in_array($k1, $arr_convert['date']) && !is_string($v)) {
                            try {
                                $data[$k][$k1] = !empty($v) ? parseTimestamp($v) : ''; // Chuyển kiểu dữ liệu về timestamp
                            } catch (\Throwable $th) {
                                $text[$k1] = $k1.' Nhập dữ liệu sai định dạng mongotime';
                            }
                        }
                        if (!empty($arr_convert['comma']) && in_array($k1, $arr_convert['comma']) && is_string($v)) {
                            $data[$k][$k1] = explode(',', $v); // Chuyển kiểu về array, với loại lưu select multi
                        }
                        if (!empty($arr_convert['json']) && in_array($k1, $arr_convert['json']) && is_string($v)) {
                            $data[$k][$k1] = json_decode($v, true);
                        }
                        if ($k1 == 'password') {
                            unset($data[$k][$k1]);
                        }
                        break;
                }
                if (!empty($replace_data) && !empty($replace_data[$k1])) {
                    if(isset($replace_data[$k1]['multiple']) && $replace_data[$k1]['multiple'] == 1){
                        if(!empty($data[$k][$k1]) && is_array($data[$k][$k1])){
                            $data[$k][$k1] = implode(', ', (array_intersect_key($replace_data[$k1], array_flip($data[$k][$k1]))));
                        }
                    }else{
                        if(!empty($replace_data[$k1]['multiple_select'])){
                            if(is_array($data[$k][$k1])){
                                $data[$k][$k1] = implode(',', $data[$k][$k1]);
                            }
                        }else{
                            $data[$k][$k1] = !empty($replace_data[$k1][$data[$k][$k1]]) ? $replace_data[$k1][$data[$k][$k1]] : '';
                        }
                    }
                }
            }
            if (!empty($item['sub']) && is_array($item['sub'])) {
                $data[$k]['sub'] = formatData($item['sub'], $arr_convert);
            }
        }
        $data['mess'] = !empty($text) ? implode(', ', $text) : '';
        return $data;
    }
}

if (!function_exists('scopeFilter')) {
    function scopeFilter($arr_filter, $arr_element = [], $keyword_for = 'title'){
        $filter = [];
        foreach ($arr_filter as $k => $v){
            switch ($k){
                case 'keyword':
                    if(!empty($v)){
                        $arr_keys = explode_custom($v,' ');
                        if(!empty($arr_keys)){
                            foreach ($arr_keys as $item){
                                $filter[] = '$query->where("'.$keyword_for.'", "like", "%'.$item.'%");';
                            }
                        }
                    }
                    break;
                case 'date_start': // Lọc theo thời gian bắt đầu
                    if(!empty($arr_filter['date_start'])){
                        $date_start = convert_date_search($arr_filter['date_start']);
                        $filter[] = '$query->whereDate("created_at", ">=", "'.$date_start.'");';
                    }
                    break;
                case 'date_end': // Lọc theo thời gian kết thúc
                    if(!empty($arr_filter['date_end'])){
                        $date_end = convert_date_search($arr_filter['date_end']);
                        $filter[] = '$query->whereDate("created_at", "<=", "'.$date_end.'");';
                    }
                    break;
                default: // Lọc theo các trường khác
                    if(!empty($arr_element[$k])){
                        if(isset($arr_element[$k]['type']) && in_array($arr_element[$k]['type'], ['int','float','double'])){
                            $filter[] = '$query->where("'.$k.'", '.$v.');';
                        }else{
                            $filter[] = '$query->where("'.$k.'", "'.$v.'");';
                        }
                    }
                    break;
            }
        }
        return $filter;
    }
}

if (!function_exists('color_status')) {
    function color_status($code=0) {
        $output = [];
        switch ($code) {
            case -1:
                $output['title'] = 'Chưa duyệt hết';
                $output['color'] = '#009CE1';
                $output['background'] = '#E0F3FF';
                break;
            case 0:
                $output['title'] = 'Chờ duyệt';
                $output['color'] = '#009CE1';
                $output['background'] = '#E0F3FF';
                break;
            case 1:
                $output['title'] = 'Đã duyệt';
                $output['color'] = '#18AB23';
                $output['background'] = '#EEFFEF';
                break;
            case 2:
                $output['title'] = 'Từ chối';
                $output['color'] = '#E50000';
                $output['background'] = '#FFD8D8';
                break;
            case 3:
                $output['title'] = 'Duyệt lại';
                $output['color'] = '#E50000';
                $output['background'] = '#FFD8D8';
                break;
            default:
                $output['title'] = 'Chưa có';
                $output['color'] = '#000';
                $output['background'] = '#e3e3e3';
                break;
        }
        return $output;
    }
}

if (!function_exists('color_status_expired')) {
    function color_status_expired($code=0) {
        $output = [];
        switch ($code) {
            case 1:
                $output['title'] = 'Sắp hết hạn';
                $output['color'] = '#009CE1';
                $output['background'] = '#E0F3FF';
                break;
            case 2:
                $output['title'] = 'Đã hết hạn';
                $output['color'] = '#E50000';
                $output['background'] = '#FFD8D8';
                break;
            default:
                $output['title'] = 'Chưa hết hạn';
                $output['color'] = '#000';
                $output['background'] = '#e3e3e3';
                break;
        }
        return $output;
    }
}

if (!function_exists('sky_hash_hmac')) {
    function sky_hash_hmac($inputData=array(), $type='') {
        unset($inputData['securehash']);
        $serectKey = $type . '-5ead2ac1c87bbf33555c2cdd055f319c897a62e53fb9d6ed51a008cb1fd4f721963568a6ee1d651cbff18aa319052700ce0c61c1d7b1dc91a354b6fc73592c74';
        ksort($inputData);
        $i = 0;
        $hashData = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData = $hashData . urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }
        $output = hash_hmac('sha512', $hashData, md5($hashData) . $serectKey);
        return $output;
    }
}

if (!function_exists('formatNumber')) {
    function formatNumber($num){
        $num = ltrim($num, '0');
        return number_format($num,0,'.',',');
    }

}

if (!function_exists('_call_api_get')) {
    function _call_api_get($url = '', $params = array(), $uid = '', $_debug = 0)
    {
        $token = (new App\Http\Token)->getToken($url, $uid);
        $header = [
            'Authorization' =>  'Bearer ' . $token,
            'lang' => request()->header('lang')
        ];
        $path = parse_url((request()->api_gateway ?? config('app.gateway_url')) . $url);
        if(!empty($path['query'])){
            parse_str($path['query'], $query);
            $params = array_merge($params, $query);
        }
        $res = Illuminate\Support\Facades\Http::withHeaders($header)
            ->get(Config('Api_app') . '/' . $url, $params)
            ->body();
        if ($_debug == 1) {
            print_arr($res);
            die;
        }
        $res = json_decode($res);
        if (isset($res->code) && $res->code == 200) {
            return (array)$res;
        }
        if (isset($res->code) && $res->code == 403) {
            return (array)$res;
        } else {
            return array();
        }
    }
}

if (!function_exists('_call_api_post')) {
    function _call_api_post($url = '', $params = array(), $uid = '', $_debug = 0)
    {
        $token = (new App\Http\Token)->getToken($url, $uid);
        $header = [
            'Authorization' =>  'Bearer ' . $token,
            'lang' => request()->header('lang')
        ];

        $res = Illuminate\Support\Facades\Http::withHeaders($header)
            ->post(Config('Api_app') . '/' .$url, $params)
            ->body();
        if ($_debug == 1) {
            return $res;
        }
        $res = json_decode($res);
        if (isset($res->code) && $res->code == 200) {
            return (array)$res->data;
        } else {
            return array();
        }
    }
}
