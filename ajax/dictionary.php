<?php
use Logging\LoggedError;
use Dictionaries\Tome, Dictionaries\Subscription;

function throw_error($errors, $code=400) {
    if ($code == 400) { $code = "400 Bad Request"; }
    if (!headers_sent()) { header("HTTP/1.1 {$code}"); }
    $retval = ['errors' => $errors];
    if (is_array($errors)) {
        $output = print_r($errors,true);
        error_log($output);
        @LoggedError::log('ajaxError',0,__FILE__,__LINE__,$output);
    } else {
        error_log($errors);
        @LoggedError::log('ajaxError',0,__FILE__,__LINE__,$errors);
    }
    die(json_encode($retval));
}
function populate_from_request($varnames) {
    foreach ($varnames as $varname) {
        if (isset($_REQUEST[$varname])) { 
            $safe_varname = str_replace('-','_',$varname);
            global $$safe_varname;
            $$safe_varname = $_REQUEST[$varname]; 
        }
    }
}

//error_log(print_r($params,true));

if ((!is_array($params))||(count($params) == 0)) {
    $file = str_replace(__DIR__,'',__FILE__);
    throw_error("No valid action passed to {$file}");
}
$action = array_shift($params);

//error_log($action);

switch ($action) {
    case 'subscribe':
        $sub = new Subscription();
        $sub->tome_id = $params[0];
        $sub->user_id = $user->id;
        $sub->subscribed = true;
        $sub->save();
        break;
    case 'unsubscribe':
        $sub = new Subscription();
        $sub->tome_id = $params[0];
        $sub->user_id = $user->id;
        $sub->subscribed = false;
        $sub->save();
        break;
    case 'delete':
        $tome = Tome::getById($params[0]);
        $tome->delete();
        break;
    default:
        $file = str_replace(__DIR__,'',__FILE__);
        throw_error(["Invalid action {$action} passed to {$file}"]);
}