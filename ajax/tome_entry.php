<?php
use Logging\LoggedError;
use Dictionaries\Tome;
use Dictionaries\TomeEntry;

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
    if (!is_array($varnames)) {
        LoggedError::log(LoggedError::TYPE_DEBUG, 0, __FILE__, __LINE__, 
        "populate_from_request normally receives an array of variable names, but a scalar was provided "
        ."- this may indicate erroneous usage");
    }
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
        $errors = [];
        $permissions_checked = false;
        // Called as /ajax/tome/*/list?tome_ids=[m,n]&limit=p&offset=q
        // If tome_id is not supplied, retrieve for all user-accessible dictionaries
        populate_from_request(['tome_ids','since','limit','offset']);
        if (!isset($since)) { $since = new DateTime('1970-01-01 00:00:00'); }
        if (!isset($tome_ids)) {
            // Not supplied - retrieve all accessible dictionaries
            $tomes = Tome::getAllForUser($user->id);
            $tome_ids = array_column($tomes, 'id');
            $permissions_checked = true;
        } elseif (!is_array($tome_ids)) {
            if (is_integer($tome_ids)) {
                // Single int value supplied instead of array
                $tome_ids = [$tome_ids];
            } else {
                // We're really stuck - give up
                throw_error("Cannot parse tome ids: value supplied was {$tome_ids}");
            }
        }
        // Check they're all integers and all user-readable
        foreach ($tome_ids as $tome_id) {
            if (!is_integer($tome_id)) { $errors[] = "Cannot parse tome id with value of {$tome_id}"; }
            if (!$permissions_checked) { // We don't need to check if we've just pulled the list of user-readable tomes
                if (!Tome::readableBy($tome_id, $user->id)) { $errors[] = "User does not have permission to read tome with id of {$tome_id}"; }
            }
        }
        if (count($errors) > 0) { throw_error($errors); }
        
        $criteria = [];
        $criteria[] = ['tome_id','IN',$tome_ids];
        $criteria[] = ['modified','>=',$since];
        if (!isset($limit)) { $limit = null; }
        if (!isset($offset)) { $offset = null; }
        // Return entries
        $tome_entries = TomeEntry::find($criteria, ['modified','tome_id','bare_letters','word'], $limit, $offset);
        // Also return whether there are more entries to process - more reliable than checking if row count smaller than limit
        $more_entries = TomeEntry::find($criteria, ['modified','tome_id','bare_letters','word'], 1, $offset+$limit);
        $return_value = [
            'entries' => $tome_entries,
            'nextOffset' => (
                (count($more_entries)>0) ?
                $offset+$limit: // Next offset to try
                null // No more rows
            )
        ];
        die(json_encode($return_value));
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