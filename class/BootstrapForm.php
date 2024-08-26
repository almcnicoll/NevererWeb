<?php
/**
 * Represents a form element (with contents) in Bootstrap 5.x
 * and includes a bunch of utility functions for generating markup
 */
class BootstrapForm {
    public string $id = '';
    public mixed $fields = [];

    /**
     * Creates the BootstrapForm object
     * @constructor
     */
    function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * Creates a field with the specified id, prefixed by the form's id, and adds it to the form's collection
     */
    function addField(string $id) : BootstrapFormField {
        $fld = new BootstrapFormField($this->id.'-',$id);
        $this->fields[] = $fld;
        return $fld;
    }

    /**
     * Gets the HTML representing this form
     * @return string HTML code representing the form
     */
    function getHtml() : string {
        $html = "<form id='{$this->id}'>";
        foreach ($this->fields as $field) {
            $html .= $field->getHtml();
        }
        $html .= "</form>";
        return $html;
    }
}