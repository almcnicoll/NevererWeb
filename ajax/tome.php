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
        $tomes = Tome::getSubscribedForUser($user->id);
        die(json_encode($tomes));
    case 'create':
        /** @var string $name */
        /** @var string $source */
        /** @var string $source_type */
        /** @var string $source_format */
        /** @var int $readable */
        /** @var int $writeable */
        /** @var string $created */
        /** @var string $modified */
        populate_from_request(['name','source','source_type','source_format','readable','writeable']);
        $tome = new Tome();
        $tome->name = $name;
        $tome->source = $source;
        $tome->source_type = $source_type;
        $tome->source_format = $source_format;
        $tome->readable = $readable;
        $tome->writeable = $writeable;
        $tome->user_id = $user->id;
        $tome->created = date('Y-m-d H:i:s');
        $tome->modified = date('Y-m-d H:i:s');
        $tome->save();
        die(json_encode($tome->expose()));
    case 'update':
        /** @var int $id */
        /** @var string $name */
        /** @var string $source */
        /** @var string $source_type */
        /** @var string $source_format */
        /** @var int $readable */
        /** @var int $writeable */
        populate_from_request(['id','name','source','source_type','source_format','readable','writeable']);
        $tome = Tome::getById($id);
        if ($tome->user_id != $user->id) { throw_error("Cannot update tome: you do not own it"); }
        if (!empty($name)) { $tome->name = $name; }
        if (!empty($source)) { $tome->source = $source; }
        if (!empty($source_type)) { $tome->source_type = $source_type; }
        if (!empty($source_format)) { $tome->source_format = $source_format; }
        if (!empty($readable)) { $tome->readable = $readable; }
        if (!empty($writeable)) { $tome->writeable = $writeable; }
        $tome->modified = date('Y-m-d H:i:s');
        $tome->save();
        die(json_encode($tome->expose()));
    case 'delete':
        /** @var int $id */
        populate_from_request(['id']);
        $tome = Tome::getById($id);
        if ($tome->user_id != $user->id) { throw_error("Cannot delete tome: you do not own it"); }
        $tome->delete();
        die("OK");
    default:
        $file = str_replace(__DIR__,'',__FILE__);
        throw_error("Invalid action {$action} passed to {$file}");
}