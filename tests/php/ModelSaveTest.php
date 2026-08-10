<?php
/**
 * Exercises Basic\Model::save($onDuplicateKeyUpdate) against the real local dev database,
 * via Dictionaries\TomeClue (the only current caller of save(true), added by the #27 fix).
 *
 * Everything here runs inside one transaction that is always rolled back at the end
 * (see the try/finally below), so it never leaves data behind - a throwaway User + Tome
 * are created as fixtures rather than touching any pre-existing rows.
 */
use Basic\db;
use Security\User;
use Dictionaries\Tome;
use Dictionaries\TomeClue;

TestRunner::suite('Model::save(true) ON DUPLICATE KEY UPDATE - TomeClue', function () {
    $pdo = db::getPDO();
    $pdo->beginTransaction();

    try {
        $user = new User();
        $user->authmethod_id = 1;
        $user->identifier = 'test-harness-' . uniqid();
        $user->email = null;
        $user->display_name = 'Test Harness User';
        $user->save();

        $tome = new Tome();
        $tome->name = 'Test Harness Tome';
        $tome->source = null;
        $tome->source_type = Tome::TYPE_LOCAL;
        $tome->source_format = Tome::FORMAT_SQL;
        $tome->readable = Tome::PERMISSION_OWNER;
        $tome->writeable = Tome::PERMISSION_OWNER;
        $tome->user_id = $user->id;
        $tome->subscribed_by_default = 0;
        $tome->last_updated = date('Y-m-d H:i:s');
        $tome->save();

        TestRunner::test('save(true) inserts a fresh row and returns its id', function () use ($tome, $user) {
            $tc = new TomeClue();
            $tc->tome_id = $tome->id;
            $tc->user_id = $user->id;
            $tc->word = 'CAT';
            $tc->question = 'Feline pet';
            $tc->explanation = '';
            $tc->cryptic = true;
            $id = $tc->save(true);

            assertNotNull($id);
            assertTrue($id > 0);
            $reloaded = TomeClue::getById($id);
            assertNotNull($reloaded);
            assertEqual('CAT', $reloaded->word);
        });

        TestRunner::test('re-saving the same (tome_id, cryptic, question) updates the existing row instead of erroring', function () use ($tome, $user) {
            // ajax/placed_clue.php always does `$tc = new TomeClue(); ... $tc->save(true);`
            // for both the create AND update actions - it never loads the existing row
            // first. So the very first time a user re-saves the same clue text to the same
            // tome (e.g. re-editing a placed clue without changing the clue wording), this
            // is the code path that runs: an INSERT that collides with the `by_entry`
            // UNIQUE KEY (tome_id, cryptic, question) and falls through to
            // ON DUPLICATE KEY UPDATE.
            $first = new TomeClue();
            $first->tome_id = $tome->id;
            $first->user_id = $user->id;
            $first->word = 'DOG';
            $first->question = 'Canine pet';
            $first->explanation = 'first explanation';
            $first->cryptic = true;
            $firstId = $first->save(true);

            $second = new TomeClue();
            $second->tome_id = $tome->id;
            $second->user_id = $user->id;
            $second->word = 'DOG';
            $second->question = 'Canine pet'; // same tome_id+cryptic+question -> hits the unique key
            $second->explanation = 'updated explanation';
            $second->cryptic = true;
            $secondId = $second->save(true);

            assertEqual($firstId, $secondId, "the conflicting row's primary key must not change");

            $reloaded = TomeClue::getById($firstId);
            assertNotNull($reloaded, 'row must still exist after the "duplicate" save');
            assertEqual('updated explanation', $reloaded->explanation, 'ON DUPLICATE KEY UPDATE should apply the new values');
        });
    } finally {
        $pdo->rollBack();
    }
});
