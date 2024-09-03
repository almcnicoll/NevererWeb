<?php
/**
 * Allows for streamlined creation of Bootstrap 5.x modal dialogs
 * NB - see https://codepen.io/anirugu/pen/xjjxvG for how to implement in HTML/JS/CSS
 */
class BootstrapContextMenu extends BaseClass {
    public string $id = '';
    public string $titleHtml = '';
    public BootstrapMenuItem_List $menuItems;
    public string $action = '';
    private mixed $arguments = [];

    /**
     * Creates the BootstrapModal object
     * @constructor
     * @param string $id the unique id of the modal to create
     */
    function __construct(string $id,mixed $args = [])
    {
        $this->id = $id;
        $this->menuItems = new BootstrapMenuItem_List();
        $this->arguments = $args;
    }

    /**
     * Sets the title HTML for the modal
     * @param string $titleHtml the HTML code (excluding <h5></h5> tags)
     * @return BootstrapContextMenu the object itself, to allow for chaining
     */
    function setTitle(string $titleHtml) : BootstrapContextMenu {
        $this->titleHtml = $titleHtml;
        return $this;
    }
    /**
     * Sets the item(s) for the modal
     * @param mixed $buttons - either a single string, representing a single btn-primary button - or an array in the form ['{id}' => ['text' => {text}, 'class' => {class}]]
     * @return BootstrapContextMenu the object itself, to allow for chaining
     */
    function setItems(mixed $items) : BootstrapContextMenu {
        if (is_array($items)) {
            // Handle as an array of buttons
            $this->menuItems = $items;
        } else {
            // Handle as a single button
            $this->menuItems = [
                "{$this->id}-default" => [
                    'text' => $items,
                    'class' => 'btn-primary',
                ],
            ];
        }
        return $this;
    }
    /**
     * Sets the action for the modal
     * @param string $action the javascript action to perform
     * @return BootstrapContextMenu the object itself, to allow for chaining
     */
    function setAction(string $action) : BootstrapContextMenu {
        $this->action = $action;
        return $this;
    }

    /**
     * Gets the HTML for the menu itself
     * @return string the HTML with correct substitutions made
     */
    public function getHtml() : string {
        $items_string = '';
        foreach ($this->menuItems as $k=>$item) {
            $items_string .= $item->getHtml()."\n";
        }
        $html = <<<END_HTML
        <div id="context-menu-{$this->id}" class="dropdown clearfix">
            <ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu" style="display:block;position:static;margin-bottom:5px;">
                {$items_string}
            </ul>
        </div>
END_HTML;
        return $html;
    }
}