<?php
/**
 * Passes PHP values into client-side JS without an inline <script> block that
 * interpolates them directly into executable code (which a strict Content-Security-Policy
 * without 'unsafe-inline' would block). Emits a <script type="application/json"> "data
 * island" instead - browsers never execute non-JS-mimetype script tags, so its content is
 * just inert markup that js/app.js's transferData() reads via .text() and JSON.parse() on
 * document-ready, assigning each key into the given JS scope (window by default).
 */
namespace UI {
    class DataTransfer {
        /**
         * Builds the <script type="application/json"> markup for the given data.
         * @param array $data associative array of variable name => value to expose in JS
         * @param string $scope the JS scope to assign into: "window" for globals, or the
         *                      name of an object (created if it doesn't already exist)
         * @return string the <script> tag markup, ready to echo
         */
        public static function render(array $data, string $scope = 'window') : string {
            $safeScope = htmlspecialchars($scope, ENT_QUOTES);
            return '<script type="application/json" class="data-transfer" data-scope="' . $safeScope . '">'
                 . json_encode($data)
                 . '</script>';
        }

        /**
         * Echoes the <script type="application/json"> markup for the given data.
         * @param array $data associative array of variable name => value to expose in JS
         * @param string $scope the JS scope to assign into: "window" for globals, or the
         *                      name of an object (created if it doesn't already exist)
         */
        public static function emit(array $data, string $scope = 'window') : void {
            echo self::render($data, $scope);
        }
    }
}
