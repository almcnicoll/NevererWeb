<?php
/**
 * pages/crossword_edit.php can't easily be rendered in a unit test (it needs a live
 * Crossword, a logged-in User, and session state), so these checks work at two levels:
 *  - direct behavioural tests of UI\BootstrapForm/BootstrapFormField in isolation
 *  - "golden pattern" checks against the actual page source, since that's where the #32
 *    fix (commit d63c620) introduced the regression.
 */
use UI\BootstrapForm;

/** Returns the source substring from each occurrence of $needle up to (excluding) the next ';'. */
function findFieldChains(string $src, string $needle): array {
    $chains = [];
    $offset = 0;
    while (($pos = strpos($src, $needle, $offset)) !== false) {
        $semi = strpos($src, ';', $pos);
        $chains[] = substr($src, $pos, ($semi === false ? strlen($src) : $semi) - $pos);
        $offset = $pos + strlen($needle);
    }
    return $chains;
}

TestRunner::suite('BootstrapFormField', function () {
    TestRunner::test('a checkbox field (setType checkbox) renders type=checkbox with its label and value', function () {
        $form = new BootstrapForm('edit-clue');
        $form->addField('cryptic-clue')->setLabel('Cryptic clue?')->setClass('border-secondary')->setType('checkbox')->setValue('on')
            ->setAdditionalAttributes(['checked' => 'checked']);

        $html = $form->getHtml();
        assertStringContains('type="checkbox"', $html);
        assertStringContains('Cryptic clue?', $html);
        assertStringContains('value="on"', $html);
    });

    TestRunner::test('a field with no setType/setLabel (the regressed shape) silently renders as an unlabeled text input', function () {
        // This documents *why* the missing chain calls are a real bug, not just cosmetic:
        // dropping setType('checkbox') doesn't fail loudly, it falls back to the 'text' default.
        $form = new BootstrapForm('edit-clue');
        $form->addField('cryptic-clue')->setAdditionalAttributes(['checked' => 'checked']);

        $html = $form->getHtml();
        assertStringNotContains('type="checkbox"', $html);
        assertStringContains('type="text"', $html);
        assertStringNotContains('Cryptic clue?', $html, 'no setLabel() call means no <label> at all');
    });

    TestRunner::test('setAfterHtml renders its markup immediately after the field it was called on, not at the top of the form', function () {
        $form = new BootstrapForm('test-form');
        $form->addField('first')->setLabel('First');
        $form->addField('save-tome-clue')->setLabel('Save?')->setType('checkbox')->setAfterHtml('<p class="note">note text</p>');
        $form->addField('last')->setLabel('Last');

        $html = $form->getHtml();
        $firstPos = strpos($html, 'id="test-form-first"');
        $notePos = strpos($html, 'note text');
        $lastPos = strpos($html, 'id="test-form-last"');
        assertNotNull($firstPos);
        assertNotNull($notePos);
        assertNotNull($lastPos);
        assertTrue($notePos > $firstPos, 'note must render after the first field');
        assertTrue($notePos < $lastPos, 'note must render before the last field (i.e. right after save-tome-clue)');
    });
});

TestRunner::suite('pages/crossword_edit.php source (golden-pattern regression checks)', function () {
    $src = file_get_contents(dirname(__DIR__, 2) . '/pages/crossword_edit.php');

    TestRunner::test("both clue forms build 'cryptic-clue' as a labeled checkbox defaulting to on", function () use ($src) {
        $chains = findFieldChains($src, "addField('cryptic-clue')");
        assertEqual(2, count($chains), 'expected exactly 2 cryptic-clue fields (new-clue form + edit-clue form)');
        foreach ($chains as $i => $chain) {
            assertStringContains("setLabel('Cryptic clue?')", $chain, "cryptic-clue field #{$i} is missing setLabel");
            assertStringContains("setType('checkbox')", $chain, "cryptic-clue field #{$i} is missing setType('checkbox') - without it, the field silently renders as a text input and always POSTs an empty string, so the clue is permanently saved as non-cryptic");
            assertStringContains('setValue("on")', $chain, "cryptic-clue field #{$i} is missing its default checked value");
        }
    });

    TestRunner::test("the 'no default dictionary' prompt is attached to the save-tome-clue field, not floated to the top of the form", function () use ($src) {
        // BootstrapForm::addHtml() always renders in the form's header, before every field,
        // regardless of where in the PHP the call appears - so calling it partway through
        // building the form does NOT put the message "below the disabled checkbox".
        assertStringNotContains('addHtml("<p class=\'text-muted small\'>You don\'t have a default dictionary', $src);
    });
});
