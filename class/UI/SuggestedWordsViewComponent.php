<?php
/** Outputs an anagram-finder component */
namespace UI {
    use Basic\BaseClass;
    class SuggestedWordsViewComponent extends BaseClass {
        public $prefix;
        function __construct($prefix)
        {
            $this->prefix = $prefix;
        }

        function getHTML() : string {
            return <<<END_HTML
                <div class="container">
                    <div class="row word-list-row">
                        <table class="table table-hover word-list">
                            <thead>
                                <tr class='table-dark'>
                                    <th scope='col' id='{$this->prefix}-clue-suggested-words-pattern'></th>
                                </tr>
                            </thead>
                            <tbody id='{$this->prefix}-clue-suggested-words-tbody'></tbody>
                        </table>
                    </div>
                </div>
            END_HTML;
        }

        static function HTML($prefix) : string {
            return (new static($prefix))->getHTML();
        }
    }
}