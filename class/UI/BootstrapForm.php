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
        public int $columns = 1;

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
            $fld = new BootstrapFormField($this, $this->id.'-'.$id);
            $this->fields[] = $fld;
            return $fld;
        }

        /**
         * Sets the number of columns in which form fields should be laid out
         * @param int $columnCount the number of columns
         * @return BootstrapForm the original object, for method chaining
         */
        function setColumns(int $columnCount) : BootstrapForm {
            $this->columns = $columnCount;
            return $this;
        }

        /**
         * Gets the HTML representing this form
         * @return string HTML code representing the form
         */
        function getHtml() : string {
            $html = "<form id='{$this->id}'>";
            $html .= implode(' ',$this->headerHtmls);
            $lastColumn = 99;
            $colOpen = false;
            foreach ($this->fields as $field) {
                if ($this->columns > 1) {
                    if ($field->column <= $lastColumn) {
                        if ($colOpen) { $html .= "</div>\n"; }
                        $html .= "<div class='row'>\n";
                        $colOpen = true;
                    }
                    $lastColumn = $field->column;
                }
                $html .= $field->getHtml();
            }
            if ($this->columns > 1) {
                $html .= "</div>\n";
            }
            $html .= implode(' ',$this->footerHtmls);
            $html .= "</form>";
            return $html;
        }
    }
}