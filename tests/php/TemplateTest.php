<?php
/**
 * Grid templates: the pattern checksum, creating a crossword from a template, and counting a user's
 * crosswords that share a template's pattern (including ones not made from the template).
 *
 * DB-backed parts run inside one transaction that is always rolled back, with throwaway users.
 */
use Basic\db;
use Security\User;
use Crosswords\Crossword, Crosswords\PlacedClue, Crosswords\Clue, Crosswords\Template;

/** A 5x5 grid with blacks at (1,1),(1,3),(3,1),(3,3): 3 across + 3 down, all length 5 */
const MINI_CLUES = [[0,0,'across',5],[0,2,'across',5],[0,4,'across',5],[0,0,'down',5],[2,0,'down',5],[4,0,'down',5]];

function makeTemplateTestUser() : User {
    $user = new User();
    $user->authmethod_id = 1;
    $user->identifier = 'test-harness-' . uniqid();
    $user->email = null;
    $user->display_name = 'Test Harness User';
    $user->save();
    return $user;
}

/** Hand-builds a crossword the way the editor would: fully-filled answers, no template involved */
function makeHandBuiltCrossword(User $user, array $clues, int $symmetry = 1) : Crossword {
    $cw = new Crossword();
    $cw->title = 'Hand built';
    $cw->rows = 5;
    $cw->cols = 5;
    $cw->rotational_symmetry_order = $symmetry;
    $cw->user_id = $user->id;
    $cw->save();
    foreach ($clues as [$x, $y, $orientation, $length]) {
        $pc = new PlacedClue();
        $pc->crossword_id = $cw->id;
        $pc->x = $x;
        $pc->y = $y;
        $pc->orientation = $orientation;
        $clue = new Clue();
        $clue->answer = str_repeat('a', $length);
        $pc->setClue($clue, false);
        $pc->save();
    }
    return $cw;
}

TestRunner::suite('Template checksum', function () {
    TestRunner::test('is independent of clue order and orientation case', function () {
        $a = Template::checksum(5, 5, [[0,0,'across',5],[0,0,'down',5]]);
        $b = Template::checksum(5, 5, [[0,0,'DOWN',5],[0,0,'Across',5]]);
        assertEqual($a, $b);
    });

    TestRunner::test('changes with dimensions, clue position and clue length', function () {
        $base = Template::checksum(5, 5, MINI_CLUES);
        assertTrue($base !== Template::checksum(5, 6, MINI_CLUES), 'cols differ');
        assertTrue($base !== Template::checksum(6, 5, MINI_CLUES), 'rows differ');
        $moved = MINI_CLUES; $moved[0][1] = 1;
        assertTrue($base !== Template::checksum(5, 5, $moved), 'position differs');
        $shorter = MINI_CLUES; $shorter[0][3] = 4;
        assertTrue($base !== Template::checksum(5, 5, $shorter), 'length differs');
    });

    TestRunner::test('ignores symmetry', function () {
        $a = new Template(); $a->rows = 5; $a->cols = 5; $a->rotational_symmetry_order = 1; $a->clues = json_encode(MINI_CLUES);
        $b = new Template(); $b->rows = 5; $b->cols = 5; $b->rotational_symmetry_order = 4; $b->clues = json_encode(MINI_CLUES);
        assertEqual($a->getChecksum(), $b->getChecksum());
    });
});

TestRunner::suite('Template - database', function () {
    $pdo = db::getPDO();
    $pdo->beginTransaction();

    try {
        $user = makeTemplateTestUser();
        $other = makeTemplateTestUser();

        $mini = new Template();
        $mini->user_id = $user->id;
        $mini->name = 'Test mini';
        $mini->rows = 5;
        $mini->cols = 5;
        $mini->rotational_symmetry_order = 4;
        $mini->clues = json_encode(MINI_CLUES);
        $mini->save();

        TestRunner::test('createCrossword copies size and symmetry and makes blank clues', function () use ($mini, $user) {
            $cw = $mini->createCrossword('From template', $user->id);
            assertEqual(5, (int)$cw->rows);
            assertEqual(4, (int)$cw->rotational_symmetry_order);
            $pcs = $cw->getPlacedClues();
            assertEqual(6, count($pcs));
            foreach ($pcs as $pc) {
                assertEqual('?????', $pc->getClue()->answer);
            }
        });

        TestRunner::test('createCrossword runs without PHP warnings or deprecations', function () use ($mini, $user) {
            $problems = [];
            set_error_handler(function ($no, $msg) use (&$problems) { $problems[] = $msg; return true; });
            try {
                $mini->createCrossword('Quiet', $user->id);
            } finally {
                restore_error_handler();
            }
            assertEqual([], $problems);
        });

        TestRunner::test('fromCrossword round-trips the grid', function () use ($mini, $user) {
            $cw = makeHandBuiltCrossword($user, MINI_CLUES);
            $t = Template::fromCrossword($cw, 'Round trip', $user->id);
            assertEqual($mini->getChecksum(), $t->getChecksum());
            assertEqual(1, $t->rotational_symmetry_order, 'symmetry is taken from the crossword');
        });

        TestRunner::test('fromCrossword refuses a crossword with no clues', function () use ($user) {
            $cw = makeHandBuiltCrossword($user, []);
            assertThrows(fn() => Template::fromCrossword($cw, 'Empty', $user->id));
        });

        TestRunner::test('counts crosswords with the same pattern however they were made, and nothing else', function () use ($mini, $user, $other) {
            // Two template-made crosswords and one hand-built one exist from the tests above; add a hand-built one with different symmetry
            makeHandBuiltCrossword($user, MINI_CLUES, 2);
            makeHandBuiltCrossword($user, array_slice(MINI_CLUES, 1)); // one clue short: different pattern
            makeHandBuiltCrossword($other, MINI_CLUES);                // someone else's
            $counts = Template::countUserCrosswordsByChecksum($user->id);
            assertEqual(4, $counts[$mini->getChecksum()] ?? 0);
            assertEqual(1, $counts[Template::checksum(5, 5, array_slice(MINI_CLUES, 1))] ?? 0);
        });

        TestRunner::test('users see built-in and their own templates, not other people\'s', function () use ($mini, $user, $other) {
            $theirs = new Template();
            $theirs->user_id = $other->id;
            $theirs->name = 'Theirs';
            $theirs->rows = 5; $theirs->cols = 5;
            $theirs->clues = json_encode(MINI_CLUES);
            $theirs->save();

            $ids = array_map(fn($t) => $t->id, Template::getVisibleTo($user->id));
            assertTrue(in_array($mini->id, $ids), 'own template is listed');
            assertFalse(in_array($theirs->id, $ids), "another user's template is not listed");
            assertNull(Template::getVisibleById($theirs->id, $user->id));
            assertNotNull(Template::getVisibleById($mini->id, $user->id));
        });

        TestRunner::test('every built-in template is a valid grid whose preview renders', function () {
            $builtIn = Template::find(['user_id', 'IS', null]);
            assertTrue(count($builtIn) > 0, 'migration 40 should have seeded some');
            foreach ($builtIn as $t) {
                assertTrue(count($t->getClueList()) > 0, "{$t->name} has clues");
                assertStringContains('<svg', $t->getPreviewSvg());
            }
        });
    } finally {
        $pdo->rollBack();
    }
});
