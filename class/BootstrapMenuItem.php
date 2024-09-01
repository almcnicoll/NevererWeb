<?php
/**
 * Allows for streamlined creation of Bootstrap 5.x modal dialogs
 */
class BootstrapMenuItem extends BaseClass {
    public string $id = '';
    public string $bodyHtml = '';
    public BootstrapMenuItem_List $childItems = [];

    /**
     * Creates the BootstrapModal object
     * @constructor
     * @param string $id the unique id of the modal to create
     */
    function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * Sets the title HTML for the modal
     * @param string $titleHtml the HTML code (excluding <h5></h5> tags)
     * @return BootstrapModal the object itself, to allow for chaining
     */
    function setTitle(string $titleHtml) : BootstrapModal {
        $this->titleHtml = $titleHtml;
        return $this;
    }
    /**
     * Sets the body HTML for the modal
     * @param string $bodyHtml the HTML code
     * @return BootstrapModal the object itself, to allow for chaining
     */
    function setBody(string $bodyHtml) : BootstrapModal {
        $this->bodyHtml = $bodyHtml;
        return $this;
    }
    /**
     * Sets the button(s) for the modal
     * @param mixed $buttons - either a single string, representing a single btn-primary button - or an array in the form ['{id}' => ['text' => {text}, 'class' => {class}]]
     * @return BootstrapModal the object itself, to allow for chaining
     */
    function setButtons(mixed $buttons) : BootstrapModal {
        if (is_array($buttons)) {
            // Handle as an array of buttons
        } else {
            // Handle as a single button
            $this->footerButtons = [
                "{$this->id}-default" => [
                    'text' => $buttons,
                    'class' => 'btn-primary',
                ],
            ];
        }
        return $this;
    }
    /**
     * Sets the trigger HTML for the modal
     * @param string $triggerHtml the HTML code (excluding containing tags, which will be set per the $triggerType field)
     * @return BootstrapModal the object itself, to allow for chaining
     */
    function setTrigger(string $triggerHtml) : BootstrapModal {
        $this->triggerHtml = $triggerHtml;
        return $this;
    }

    /**
     * Gets the HTML for the modal itself
     * @return string the HTML with correct substitutions made
     */
    public function getMainHtml() : string {
        $buttons = '';
        if (is_array($this->footerButtons)) {
            foreach ($this->footerButtons as $button_id=>$button) {
                if (!is_array($button)) { continue; } // Skip any invalid buttons
                if (!key_exists('text',$button)) { $button['text'] = $button_id; }
                if (!key_exists('class',$button)) { $button['class'] = ''; }
                $buttons .= "<button type='button' class='btn {$button['class']}' id='{$button_id}'>{$button['text']}</button>\n";
            }
        }
        $html = <<<END_HTML
    <div class="modal" id="{$this->id}" tabindex="-1" role="dialog" aria-labelledby="{$this->id}Label" aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="{$this->id}Label">{$this->titleHtml}</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
                {$this->bodyHtml}
            </div>
            <div class="modal-footer">
                {$buttons}
            </div>
          </div>
        </div>
    </div>
END_HTML;
        return $html;
    }

    public function getTriggerHtml() : string {
        $html = <<<END_HTML
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#{$this->id}">
    {$this->triggerHtml}
</button>
END_HTML;
        return $html;
    }
}