<?php
class BootstrapFormField {
    public string $id = '';
    public string $label = '';
    public string $help = '';
    public string $type = 'text';
    public string $placeholder = '';
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
     * Sets the field type attribute (text, number, password, ...)
     * @return BootstrapFormField the object itself, for method chaining
     */
    function setType(string $type) : BootstrapFormField {
        $this->type = $type;
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
     * Sets the content of the class tag (in addition to form-control)
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

    function getHtml() : string {
        $attributes = '';
        foreach ($this->tag_attributes as $k=>$v) {
            $attributes .= " {$k}=\"{$v}\" ";
        }
        $html = <<<END_HTML
<div class="form-group">
    <label for="{$this->id}">{$this->label}</label>
    <input type="text" class="form-control {$this->class}" id="{$this->id}" aria-describedby="{$this->id}-help" placeholder="{$this->placeholder}" style="{$this->style}" {$attributes}>
    <small id="{$this->id}-help" class="form-text text-muted">{$this->help}</small>
  </div>
END_HTML;
        return $html;
    }
}