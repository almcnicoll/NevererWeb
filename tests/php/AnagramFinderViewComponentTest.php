<?php
use UI\AnagramFinderViewComponent;

TestRunner::suite('UI\AnagramFinderViewComponent (#31 exclude-words filter)', function () {
    TestRunner::test('renders both the search field and a prefixed exclude-words field', function () {
        $html = AnagramFinderViewComponent::HTML('new');
        assertStringContains('anagram-search', $html);
        assertStringContains('class="form-control border-secondary no-mobile-auto anagram-exclude"', $html);
        assertStringContains('id="anagramexclude_new"', $html);
        assertStringContains('Exclude words:', $html);
    });

    TestRunner::test('the prefix keeps two instances (new/edit) from colliding on ids', function () {
        $new = AnagramFinderViewComponent::HTML('new');
        $edit = AnagramFinderViewComponent::HTML('edit');
        assertStringContains('id="anagramexclude_new"', $new);
        assertStringContains('id="anagramexclude_edit"', $edit);
        assertStringNotContains('id="anagramexclude_edit"', $new);
    });
});
