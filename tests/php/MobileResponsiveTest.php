<?php
/**
 * #14 (mobile interface): pages/crossword_index.php and pages/dictionary_index.php both
 * had "+ New"/"+ Import" action buttons marked with Bootstrap's `col-1` grid-column class
 * while living inside an <h2>, with no surrounding `.row` - col-1 forces ~8.3% width
 * unconditionally (it's a plain CSS class, not something that only applies inside a grid
 * row), which is far too narrow for the button text on a phone-width screen. The result
 * (see the screenshot on issue #14) was each letter wrapping onto its own line, making
 * the buttons unreadable.
 *
 * These are static-source checks (like the crossword_edit.php ones in
 * BootstrapFormFieldTest.php) since both pages need a live DB + logged-in session to
 * render fully.
 */

function assertNoNarrowColOnButton(string $src, string $file) {
    // Word-boundary match: "col-1" is also a substring of the perfectly legitimate
    // "col-12", so a plain strpos() check would false-positive on that.
    if (preg_match('/\bcol-1\b/', $src)) {
        throw new TestFailure("{$file} should not use the col-1 grid class on its action buttons (see #14)");
    }
}

TestRunner::suite('Mobile-responsive action buttons (#14)', function () {
    TestRunner::test('crossword_index.php: New/Import buttons use a responsive stack-on-mobile group, not col-1', function () {
        $src = file_get_contents(dirname(__DIR__, 2) . '/pages/crossword_index.php');
        assertNoNarrowColOnButton($src, 'crossword_index.php');
        assertStringContains('d-grid gap-2 d-md-flex', $src);
        assertStringContains('+ New', $src);
        assertStringContains('+ Import', $src);
    });

    TestRunner::test('dictionary_index.php: New button uses a responsive stack-on-mobile group, not col-1', function () {
        $src = file_get_contents(dirname(__DIR__, 2) . '/pages/dictionary_index.php');
        assertNoNarrowColOnButton($src, 'dictionary_index.php');
        assertStringContains('d-grid gap-2 d-md-flex', $src);
        assertStringContains('+ New', $src);
    });

    TestRunner::test('no other page reintroduces a Bootstrap col-N class directly on a .btn outside a grid row', function () {
        // Broad guard against the same mistake recurring elsewhere: col-1/col-2/col-3 are
        // narrow enough to cause the same letter-wrapping problem if ever put on a button.
        $pagesDir = dirname(__DIR__, 2) . '/pages';
        $offenders = [];
        foreach (glob($pagesDir . '/*.php') as $file) {
            $src = file_get_contents($file);
            if (preg_match('/class=["\'][^"\']*\bbtn\b[^"\']*\bcol-[123]\b[^"\']*["\']/', $src)
                || preg_match('/class=["\'][^"\']*\bcol-[123]\b[^"\']*\bbtn\b[^"\']*["\']/', $src)) {
                $offenders[] = basename($file);
            }
        }
        assertEqual([], $offenders, 'found col-1/2/3 combined with btn on: ' . implode(', ', $offenders));
    });
});
