<?php
/**
 * UI\DataTransfer is the #8 replacement for inline <script> blocks that interpolate PHP
 * values directly into executable JS (a CSP violation without 'unsafe-inline'). It emits
 * a <script type="application/json"> data island that js/app.js's transferData() reads
 * on document-ready - see tests/js/data_transfer.test.js for the JS side of the contract.
 */
use UI\DataTransfer;

TestRunner::suite('UI\DataTransfer', function () {
    TestRunner::test('render() produces a script type=application/json tag with the data-transfer class', function () {
        $html = DataTransfer::render(['root_path' => '/nw']);
        assertStringContains('<script type="application/json" class="data-transfer"', $html);
        assertStringContains('</script>', $html);
    });

    TestRunner::test('render() JSON-encodes the data as the script body, decodable back to the original array', function () {
        $data = ['root_path' => '/nw', 'crossword_id' => 42, 'currentUser' => 7];
        $html = DataTransfer::render($data);
        // Extract just the JSON body between the tags
        $body = preg_replace('/^<script[^>]*>|<\/script>$/', '', $html);
        $decoded = json_decode($body, true);
        assertEqual($data, $decoded);
    });

    TestRunner::test('defaults to data-scope="window"', function () {
        $html = DataTransfer::render(['x' => 1]);
        assertStringContains('data-scope="window"', $html);
    });

    TestRunner::test('a custom scope is used verbatim (and HTML-escaped)', function () {
        $html = DataTransfer::render(['x' => 1], 'myApp');
        assertStringContains('data-scope="myApp"', $html);

        $escaped = DataTransfer::render(['x' => 1], '"><script>alert(1)</script>');
        assertStringNotContains('<script>alert(1)</script>', $escaped);
    });

    TestRunner::test('values containing special characters survive the round trip (no premature </script>)', function () {
        $data = ['clue' => 'A "tricky" clue with </script> and <b>tags</b> & ampersands'];
        $html = DataTransfer::render($data);
        // The literal "</script>" must appear exactly once - the tag's real closing tag -
        // not also mid-body from the unescaped data, which would break out of the element.
        // PHP's json_encode escapes forward slashes by default (</script> -> <\/script>),
        // which is what keeps this safe.
        assertEqual(1, substr_count($html, '</script>'), 'embedded </script> in data must not close the tag early');
        $body = preg_replace('/^<script[^>]*>|<\/script>$/', '', $html);
        assertEqual($data, json_decode($body, true));
    });

    TestRunner::test('emit() echoes exactly what render() returns', function () {
        $data = ['a' => 1];
        ob_start();
        DataTransfer::emit($data);
        $output = ob_get_clean();
        assertEqual(DataTransfer::render($data), $output);
    });
});
