<?php
/** Represents a field on a Bootstrap form */
namespace UI {
    use Basic\BaseClass;
    class BootstrapFormField extends BaseClass {
        public string $id = '';
        public string $label = '';
        public string $help = '';
        public string $type = 'text';
        public mixed $options = [];
        public string $value = '';
        public string $placeholder = '';
        public string $div_class = '';
        public string $class = '';
        public string $style = '';
        public mixed $tag_attributes = [];

        /**
         * Creates the field
         * @constructor
         */
        function __construct(string $id)
        {
            $this->id = $id;
        }

        /**
         * Sets the field label (text or HTML)
         * @return BootstrapFormField the object itself, for method chaining
         */
        function setLabel(string $label) : BootstrapFormField {
            $this->label = $label;
            return $this;
        }
        /**
         * Sets the field help text (text or HTML)
         * @return BootstrapFormField the object itself, for method chaining
         */
        function setHelp(string $help) : BootstrapFormField {
            $this->help = $help;
            return $this;
        }
        /**
         * Sets the field type attribute (text, number, password, select, textarea, ...)
         * @param string $type the type of field, as specified in the type="" attribute OR select OR textarea
         * @return BootstrapFormField the object itself, for method chaining
         */
        function setType(string $type) : BootstrapFormField {
            $this->type = $type;
            return $this;
        }
        /**
         * Sets the options for a 'select' form field
         * @param mixed $options the options in the form of a k=>v array
         * @return BootstrapFormField the object itself, for method chaining
         */
        function setOptions(mixed $options) : BootstrapFormField {
            $this->options = $options;
            return $this;
        }
        /**
         * Sets the field's starting value
         * @param string $value the starting value of the field - including for <select> fields
         * @return BootstrapFormField the object itself, for method chaining
         */
        function setValue(string $value) : BootstrapFormField {
            $this->value = $value;
            return $this;
        }
        /**
         * Sets the field placeholder
         * @return BootstrapFormField the object itself, for method chaining
         */
        function setPlaceholder(string $placeholder) : BootstrapFormField {
            $this->placeholder = $placeholder;
            return $this;
        }
        /**
         * Sets the content of the <div>'s class tag (in addition to form-group)
         * @param string $div_class the class name(s) to add
         * @return BootstrapFormField the object itself, for method chaining
         */
        function setDivClass(string $div_class) : BootstrapFormField {
            $this->div_class = $div_class;
            return $this;
        }
        /**
         * Sets the content of the class tag (in addition to form-control)
         * @param string $class the class name(s) to add
         * @return BootstrapFormField the object itself, for method chaining
         */
        function setClass(string $class) : BootstrapFormField {
            $this->class = $class;
            return $this;
        }
        /**
         * Sets the content of the style tag
         * @return BootstrapFormField the object itself, for method chaining
         */
        function setStyle(string $style) : BootstrapFormField {
            $this->style = $style;
            return $this;
        }

        /**
         * Sets additional attributes for the <input> tag
         * @return BootstrapFormField the object itself, for method chaining
         */
        function setAdditionalAttributes(mixed $attributes) : BootstrapFormField {
            if (is_array($attributes)) {
                foreach ($attributes as $k=>$v) {
                    $this->tag_attributes[$k] = $v;
                }
            } else {
                $this->tag_attributes[$attributes] = '';
            }
            return $this;
        }

        /**
         * Retrieves the HTML of this form field
         * @return string the HTML representing this field, including its label
         */
        function getHtml() : string {
            $attributes = '';
            foreach ($this->tag_attributes as $k=>$v) {
                $attributes .= " {$k}=\"{$v}\" ";
            }
            $div_start = "<div class=\"form-group {$this->div_class}\">";
            $label_tag = '';
            if ($this->label != '') {
                $label_tag = "<label for=\"{$this->id}\">{$this->label}</label>";
            }
            switch (strtolower($this->type)) {
                case 'select':
                    $input_tag = <<<END_TAG
    <select class="form-control {$this->class}" id="{$this->id}" name="{$this->id}" aria-describedby="{$this->id}-help" style="{$this->style}" {$attributes}>\n
    END_TAG;
                    foreach ($this->options as $k=>$v) {
                        if ($v == $this->value) { $selected='selected'; } else { $selected = ''; }
                        $input_tag .= "<option value=\"{$k}\" {$selected}>{$v}</option>\n";
                    }
                    $input_tag .= "</select>";
                    break;
                case 'textarea':
                    $input_tag = <<<END_TAG
    <textarea class="form-control {$this->class}" id="{$this->id}" name="{$this->id}" aria-describedby="{$this->id}-help" placeholder="{$this->placeholder}" style="{$this->style}" {$attributes}>
    {$this->value}
    </textarea>
    END_TAG;
                    break;
                default:
                    $input_tag = <<<END_TAG
    <input type="{$this->type}" class="form-control {$this->class}" id="{$this->id}" name="{$this->id}" aria-describedby="{$this->id}-help" placeholder="{$this->placeholder}" style="{$this->style}" {$attributes} value="{$this->value}">
    END_TAG;
                    break;
            }
            $help_text = "<small id=\"{$this->id}-help\" class=\"form-text text-muted\">{$this->help}</small>";
            $div_end = "</div>";

            $html = <<<END_HTML
            $div_start
            $label_tag
            $input_tag
            $help_text
            $div_end
    END_HTML;
            return $html;
        }
    }
}