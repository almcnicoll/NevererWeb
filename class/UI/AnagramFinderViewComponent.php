<?php
/** Outputs an anagram-finder component */
namespace UI {
    use Basic\BaseClass;
    class AnagramFinderViewComponent extends BaseClass {
        public $prefix;
        function __construct($prefix)
        {
            $this->prefix = $prefix;
        }

        function getHTML() : string {
            return <<<END_HTML
            <div class='container' id='anagramcontainer_{$this->prefix}'>
                <div class="row">
                    <div class="form-group mb-3">
                        <div class="col-6">
                            <label for="anagramsearchword_{$this->prefix}">Anagrams for:</label>
                        </div>
                        <div class="col-6">
                            <input class="form-control border-secondary no-mobile-auto anagram-search" id="anagramsearchword_{$this->prefix}" name="anagramsearchword_{$this->prefix}">
                        </div>
                    </div>
                </div>
                <div class="row anagram-results-container" style="max-height: 12em; overflow: scroll-y;" id="anagramresults_{$this->prefix}">
                    <table class="table table-hover anagram-list">
                        <thead>
                            <tr class='table-dark'>
                                <th scope='col' id='{$this->prefix}-anagram-results'></th>
                            </tr>
                        </thead>
                        <tbody id='{$this->prefix}-anagram-results-tbody' class='anagram-results-tbody'></tbody>
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