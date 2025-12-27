<?php
/** Represents a Bootstrap accordion */
namespace UI {
    use Basic\BaseClass;
    class BootstrapAccordionElement extends BaseClass {
        public string $title;
        public string $content;
        public string $name;
        public BootstrapAccordion $parent;

        function __construct($title, $content, $name)
        {
            $this->title = $title;
            $this->content = $content;
            $this->name = $name;
        }

        function getHTML($shown) : string {
            $show = ($shown) ? "show" : "";
            $expanded = ($shown) ? "true" : "false";
            $collapsed = ($shown) ? "" : "collapsed";
            $output = <<<END_HTML
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button {$collapsed}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{$this->name}" aria-expanded="{$expanded}" aria-controls="collapse{$this->name}">
                            {$this->title}
                        </button>
                    </h2>
                    <div id="collapse{$this->name}" class="accordion-collapse collapse {$show}" data-bs-parent="#accordion{$this->parent->name}">
                        <div class="accordion-body">
                            {$this->content}
                        </div>
                    </div>
                </div>
            END_HTML;
            return $output;
        }
    }
    class BootstrapAccordion extends BaseClass {
        public $name;
        public $elements = [];
        function __construct($name) {
            $this->name = $name;
        }

        function AddElement(BootstrapAccordionElement $element) {
            $this->elements[] = $element;
            $element->parent = $this;
            return $this;
        }
        function AddElementByParts(string $title, string $content, string $name) {
            $element = new BootstrapAccordionElement($title, $content, $name);
            $element->parent = $this;
            $this->elements[] = $element;
            return $this;
        }
        function getHTML() : string {
            $startHTML = <<<END_HTML
            <div class="accordion" id="accordion{$this->name}">
            END_HTML;
            $endHTML = <<<END_HTML
            </div>
            END_HTML;
            $middleHTML = '';
            foreach ($this->elements as $k=>$element) {
                $middleHTML .= $element->getHTML( ($k==0) );
            }
            return $startHTML . $middleHTML . $endHTML;
        }
    }
}