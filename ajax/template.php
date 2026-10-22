<?php
use Logging\LoggedError;
use Crosswords\Crossword, Crosswords\Template;

function throw_error($errors, $code=400) {
    if ($code == 400) { $code = "400 Bad Request"; }
    if (!headers_sent()) { header("HTTP/1.1 {$code}"); }
    error_log($errors);
    @LoggedError::log('ajaxError',0,__FILE__,__LINE__,$errors);
    die(json_encode(['errors' => $errors]));
}

if ((!is_array($params))||(count($params) == 0)) {
    throw_error("No valid action passed to ajax/template.php");
}
$action = array_shift($params);

switch ($action) {
    case 'create':
        // Called as /ajax/template/*/create/[crossword_id] - saves the crossword's grid as a private template
        $crossword_id = array_shift($params);
        /** @var Crossword $crossword */
        $crossword = Crossword::findFirst(['id','=',$crossword_id]);
        if ($crossword === null) { throw_error("Cannot find crossword with id {$crossword_id}"); }
        if (!$crossword->isOwnedBy($user->id)) { throw_error("Crossword with id {$crossword_id} does not belong to user #{$user->id}"); }
        $name = mb_substr(trim($_POST['name'] ?? ''), 0, 200);
        if ($name === '') { throw_error("A template needs a name"); }
        try {
            $template = Template::fromCrossword($crossword, $name, $user->id);
        } catch (InvalidArgumentException $ex) {
            throw_error("Cannot save a template from a crossword with no clues");
        }
        $template->save();
        die(json_encode($template->expose()));
    case 'delete':
        // Called as /ajax/template/*/delete/[id] - built-in templates (no owner) can't be deleted
        $id = array_shift($params);
        $template = Template::getById((int)$id);
        if ($template === null) { throw_error("Cannot find template with id {$id}"); }
        if (!$template->isOwnedBy($user->id)) { throw_error("You can only delete templates that you created"); }
        $template->delete();
        die(json_encode(['ok' => true]));
    default:
        throw_error("Invalid action {$action} passed to ajax/template.php");
}
