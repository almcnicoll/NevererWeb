<?php
/**
 * Allows for streamlined creation of Bootstrap 5.x modal dialogs
 */
namespace UI {
    class BootstrapMenuItem extends \BaseClass {
        public string $id = '';
        public string $bodyHtml = '';
        public BootstrapMenuItem_List $childItems;
        public bool $dividerAfter = false;

        /**
         * Creates the BootstrapModal object
         * @constructor
         * @param string $id the unique id of the modal to create
         */
        function __construct(string $id)
        {
            $this->id = $id;
            $this->childItems = new BootstrapMenuItem_List();
        }

        /**
         * Sets the body HTML for the modal
         * @param string $bodyHtml the HTML code
         * @return BootstrapMenuItem the object itself, to allow for chaining
         */
        function setBody(string $bodyHtml) : BootstrapMenuItem {
            $this->bodyHtml = $bodyHtml;
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
         * @return string the HTML with correct substitutions made
         */
        public function getHtml() : string {
            $html = "<li><a tabindex='-1' href='#'>{$this->bodyHtml}</a>";
            if ($this->dividerAfter) { $html .= "<li class='divider'></li>"; }
            return $html;
        }
    }
}