<?php
/**
 * Allows for streamlined creation of Bootstrap 5.x modal dialogs
 * NB - see https://codepen.io/anirugu/pen/xjjxvG for how to implement in HTML/JS/CSS
 */
namespace UI {
    use Basic\BaseClass;
    class BootstrapContextMenu extends BaseClass {
        public string $id = '';
        public string $titleHtml = '';
        public BootstrapMenuItem_List $menuItems;
        public string $action = '';
        private mixed $arguments = [];

        /**
         * Creates the BootstrapContextMenu object
         * @constructor
         * @param string $id the unique id of the menu to create
         * @param mixed $args currently unused
         */
        function __construct(string $id,mixed $args = [])
        {
            $this->id = $id;
            $this->menuItems = new BootstrapMenuItem_List();
            $this->arguments = $args;
        }

        /**
         * Sets the item(s) for the modal
         * @param mixed $buttons - either a single string, representing a single btn-primary button - or an array in the form ['{id}' => ['text' => {text}, 'class' => {class}]]
         * @return BootstrapContextMenu the object itself, to allow for chaining
         */
        function setItems(mixed $items) : BootstrapContextMenu {
            if (is_array($items)) {
                // Handle as an array of buttons
                $this->menuItems = new BootstrapMenuItem_List( $items );
            } elseif ($items instanceof BootstrapMenuItem_List) {
                $this->menuItems = $items;
            } else {
                // Handle as a single button
                $this->menuItems = new BootstrapMenuItem_List( [
                    "{$this->id}-default" => [
                        'text' => $items,
                        'class' => 'btn-primary',
                    ],
                ] );
            }
            return $this;
        }

        /**
         * Gets the HTML for the menu itself
         * @return string the HTML with correct substitutions made
         */
        public function getHtml() : string {
            $items_string = '';
            foreach ($this->menuItems as $k=>$item) {
                $items_string .= $item->getHtml($this->id)."\n";
            }
            // style="display:block;position:static;margin-bottom:5px;"
            $html = <<<END_HTML
                <ul id="context-menu-{$this->id}" class="dropdown-menu context-menu" role="menu">
                    {$items_string}
                </ul>
    END_HTML;
            return $html;
        }
    }
}