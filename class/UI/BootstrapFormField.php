<?php
/** Represents a field on a Bootstrap form */
namespace UI {
    use Basic\BaseClass;
    class BootstrapFormField extends BaseClass {
        private ?BootstrapForm $parent = null;
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
        public string $style_preset = '';
        public int $column = 1;

        /**
         * Creates the field
         * @constructor
         * @param ?BootstrapForm $parent the parent form to which to attach the field (can be null for standalone)
         * @param string $id the id of the form, to be prepended to form ids etc.
         */
        function __construct(?BootstrapForm $parent, string $id)
        {
            $this->parent = $parent;
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
         * Styles the field using a Bootstrap preset (e.g. 'floating')
         * @return BootstrapFormField the object itself, for method chaining
         */
        function setStylePreset(string $style_preset) : BootstrapFormField {
            $this->style_preset = strtolower($style_preset);
            return $this;
        }

        /**
         * Sets the column in which this field should appear
         * @param int $column the column of the form in which the field should appear
         */
        function setColumn(int $column) : BootstrapFormField {
            $this->column = $column;
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
            $form_div_class_extra = '';
            if ($this->style_preset == 'floating') { $form_div_class_extra.=" form-floating"; }
            if ($this->parent->columns > 1) { $form_div_class_extra .= " col"; }
            $div_start = "<div class=\"form-group {$this->div_class}{$form_div_class_extra}\">";
            $label_tag = '';
            if ($this->label != '') {
                $label_tag = "<label for=\"{$this->id}\">{$this->label}</label>";
            }
            switch (strtolower($this->type)) {
                case 'select':
                    $input_tag = <<<END_TAG
                    <select class="form-select {$this->class}" id="{$this->id}" name="{$this->id}" aria-describedby="{$this->id}-help" style="{$this->style}" {$attributes}>\n
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
                case 'checkbox':
                    // NB - add form-check class manually for next-line behaviour
                    $input_tag = <<<END_TAG
                    <input type="{$this->type}" class="{$this->class}" id="{$this->id}" name="{$this->id}" aria-describedby="{$this->id}-help" placeholder="{$this->placeholder}" style="{$this->style}" {$attributes} value="{$this->value}">
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

            $labelled_input_tag = (
                ($this->style_preset=='floating')
                ? $input_tag . "\n            " . $label_tag
                : $label_tag . "\n            " . $input_tag
            );

            $html = <<<END_HTML
            $div_start
            $labelled_input_tag
            $help_text
            $div_end
            END_HTML;
            return $html;
        }
    }
}