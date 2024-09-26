<?php
/**
 * Represents a form element (with contents) in Bootstrap 5.x
 * and includes a bunch of utility functions for generating markup
 */
namespace UI {
    use Basic\BaseClass;
    class BootstrapForm extends BaseClass {
        public string $id = '';
        public mixed $fields = [];
        public mixed $headerHtmls = [];
        public mixed $footerHtmls = [];

        /**
         * Creates the BootstrapForm object
         * @constructor
         */
        function __construct(string $id)
        {
            $this->id = $id;
        }

        /**
         * Creates an html insert with the specified markup, to be output above or below the fields
         * @param string $html the markup to insert
         * @param bool $inFooter whether to add the markup in the form footer (after fields) - otherwise it is added above the fields
         */
        function addHtml(string $html, bool $inFooter = false) : BootstrapForm {
            if ($inFooter) {
                $this->footerHtmls[] = $html;
            } else {
                $this->headerHtmls[] = $html;
            }
            return $this;
        }

        /**
         * Creates a field with the specified id, prefixed by the form's id, and adds it to the form's collection
         */
        function addField(string $id) : BootstrapFormField {
            $fld = new BootstrapFormField($this->id.'-'.$id);
            $this->fields[] = $fld;
            return $fld;
        }

        /**
         * Gets the HTML representing this form
         * @return string HTML code representing the form
         */
        function getHtml() : string {
            $html = "<form id='{$this->id}'>";
            $html .= implode(' ',$this->headerHtmls);
            foreach ($this->fields as $field) {
                $html .= $field->getHtml();
            }
            $html .= implode(' ',$this->footerHtmls);
            $html .= "</form>";
            return $html;
        }
    }
}