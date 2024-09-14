<?php
/**
 * Allows for streamlined creation of Bootstrap 5.x modal dialogs
 */
namespace UI {
    use Basic\BaseClass;
    class BootstrapMenuItem extends BaseClass {
        public string $id = '';
        public string $label = '';
        public BootstrapMenuItem_List $childItems;
        public bool $dividerAfter = false;

        /**
         * Creates the BootstrapMenuItem object
         * @constructor
         * @param string $id the unique id of the BootstrapMenuItem to create - it will be prefaced with the id of the BootstrapContextMenu
         * @param string $label the label to display on the item (may contain html)
         */
        function __construct(string $id, string $label)
        {
            $this->id = $id;
            $this->label = $label;
            $this->childItems = new BootstrapMenuItem_List();
        }

        /**
         * Sets the body HTML for the modal
         * @param string $bodyHtml the HTML code
         * @return BootstrapMenuItem the object itself, to allow for chaining
         */
        function setLabel(string $label) : BootstrapMenuItem {
            $this->label = $label;
            return $this;
        }
        /**
         * Sets whether the menu item is followed by a divider
         * @param bool $hasDivider whether the item should be followed by a divider
         * @return BootstrapMenuItem the object itself, to allow for chaining
         */
        function setDivider(bool $hasDivider) : BootstrapMenuItem {
            $this->dividerAfter = $hasDivider;
            return $this;
        }
        /**
         * Sets the item(s) for the modal
         * @param mixed $buttons - either a single string, representing a single btn-primary button - or an array in the form ['{id}' => ['text' => {text}, 'class' => {class}]]
         * @return BootstrapContextMenu the object itself, to allow for chaining
         */
        function setItems(mixed $items) : BootstrapMenuItem {
            if (is_array($items)) {
                // Handle as an array of buttons
                $this->childItems = $items;
            } else {
                // Handle as a single button
                $this->childItems = [
                    "{$this->id}-default" => [
                        'text' => $items,
                        'class' => 'btn-primary',
                    ],
                ];
            }
            return $this;
        }

        /**
         * Gets the HTML for the child menu item itself
         * @param string $parent_id the id of the menu or menu item in which the current object resides
         * @return string the HTML with correct substitutions made
         */
        public function getHtml(string $parent_id) : string {
            if (count($this->childItems)>0) {
                // Sub-menu
                $html = "<li id='{$parent_id}-{$this->id}'>SUB-MENUS NOT YET SUPPORTED</a>";
            } else {
                // Standard item
                $html = "<li id='{$parent_id}-{$this->id}'><a href='#' class='dropdown-item'>{$this->label}</a>";
            }
            if ($this->dividerAfter) { $html .= "<li class='divider'></li>"; }
            return $html;
        }
    }
}