<?php
use Logging\LoggedError;
use Dictionaries\Tome;

function throw_error($errors) {
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
    case 'list':
        // Called as /ajax/tome/*/list
        $tomes = Tome::getAllForUser($user->id);
        die(json_encode($tomes));
    case 'create':
        // TODO - not implemented yet
        die("Not implemented");
    case 'update':
        // TODO - not implemented yet
        die("Not implemented");
    case 'delete':
        // TODO - not implemented yet
        die("Not implemented");
    default:
        $file = str_replace(__DIR__,'',__FILE__);
        throw_error("Invalid action {$action} passed to {$file}");
}