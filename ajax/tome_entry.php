<?php
use Logging\LoggedError;
use Dictionaries\Tome;
use Dictionaries\TomeEntry;
use Crosswords\Clue;
use PDO,Exception,DateTime;
use Basic\db;

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
        // Called as /ajax/tome_entry/*/list?tome_ids=[m,n]&limit=p&offset=q
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
            if (!is_numeric($tome_id)) { $errors[] = "Cannot parse tome id with value of {$tome_id}"; }
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
        $extras = ['forceIndex' => 'filter2'];
        $tome_entries = TomeEntry::find($criteria, ['modified','tome_id','bare_letters','word'], $limit+1, $offset, $extras);
        $more_entries = false;
        if (count($tome_entries) == $limit+1) {
            $more_entries = true;
            array_pop($tome_entries); // Because we don't actually want the last one (we just want to know it exists)
        }
        $return_value = [
            'entries' => $tome_entries,
            'nextOffset' => (
                $more_entries ?
                $offset+$limit: // Next offset to try
                null // No more rows
            )
        ];
        die(json_encode($return_value));
    case 'lookup':
        // TODO - HIGH - test this
        $errors = [];
        // Called as /ajax/tome_entry/*/lookup?pattern=...
        $pattern = ''; $limit = null; $offset = null;
        populate_from_request(['pattern','limit','offset']);
        // Retrieve for all user-accessible dictionaries
        $tomes = Tome::getAllForUser($user->id);
        $tome_ids = array_column($tomes, 'id');
        // Generate query
        $criteria_values = $tome_ids;
        $qmarks = implode(',',array_fill(0, count($tome_ids), '?'));
        $rlike = str_replace('?','.',$pattern);
        $criteria_values[] = $rlike;
        $sql = <<<END_SQL
            SELECT `tome_entries`.*
            FROM `tome_entries`
            WHERE `tome_id` IN ({$qmarks})
            AND `bare_letters` RLIKE ?
END_SQL;
        $pdo = db::getPDO();        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($criteria_values);
        $stmt->setFetchMode(PDO::FETCH_CLASS, TomeEntry::class);
        $results = $stmt->fetchAll();
        return $results;
        break;
    case 'create':
        /** @var int $tome_id */
        populate_from_request(['tome_id','word']);
        $tome = Tome::getById($tome_id);
        if (!$tome->isWriteableBy($user->id)) { throw_error("Cannot create entry: you do not have write permissions to {$tome->name}"); }
        $word = trim($word);
        if (empty($word)) { throw_error("Cannot supply a word that is blank or only whitespace ({$word})"); }
        $bare_letters = Clue::stripToAnswerLetters($word);
        if (empty($bare_letters)) { throw_error("Cannot supply a word that is blank or only whitespace when non-crossword characters are removed ({$word})"); }
        $tome_entry = new TomeEntry();
        $tome_entry->tome_id = $tome_id;
        $tome_entry->word = $word;
        $tome_entry->bare_letters = $bare_letters;
        $tome_entry->length = strlen($bare_letters);
        $tome_entry->created = date('Y-m-d H:i:s');
        $tome_entry->modified = date('Y-m-d H:i:s');
        $tome_entry->flagForLetterCountUpdate(); // Shouldn't be needed, as this is default true, but let's play it safe, given the minimal time cost
        $tome_entry->save();
        return json_encode($tome_entry->expose());
    case 'update':
        populate_from_request(['id','word']);
        $word = trim($word);
        if (empty($word)) { throw_error("Cannot supply a word that is blank or only whitespace ({$word})"); }
        $bare_letters = Clue::stripToAnswerLetters($word);
        if (empty($bare_letters)) { throw_error("Cannot supply a word that is blank or only whitespace when non-crossword characters are removed ({$word})"); }
        /** @var TomeEntry $tome_entry */
        $tome_entry = TomeEntry::getById($id);
        /** @var Tome $tome */
        $tome = $tome_entry->getParent();
        if (!$tome->isWriteableBy($user->id)) { throw_error("Cannot update entry: you do not have write permissions to {$tome->name}"); }
        // Update fields
        $tome_entry->word = $word;
        $tome_entry->bare_letters = $bare_letters;
        $tome_entry->length = strlen($bare_letters);
        $tome_entry->flagForLetterCountUpdate();
        $tome_entry->modified = date('Y-m-d H:i:s');
        $tome_entry->save();
        $tome_entry = TomeEntry::getById($id); // To retrieve the now-updated values
        return json_encode($tome_entry->expose());
    case 'delete':
        /** @var int $id */
        populate_from_request(['id']);
        $tome_entry = TomeEntry::getById($id);
        /** @var Tome $tome */
        $tome = $tome_entry->getParent();
        if (!$tome->isWriteableBy($user->id)) { throw_error("Cannot delete entry: you do not have write permissions to {$tome->name}"); }
        $tome_entry->delete();
        die("OK");
    default:
        $file = str_replace(__DIR__,'',__FILE__);
        throw_error("Invalid action {$action} passed to {$file}");
}