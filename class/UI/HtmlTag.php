<?php
namespace UI {
    class HtmlTag
    {
        public $tagName;
        public $innerTag;
        public $innerText;
        public $attributes;
        public $styleAttributes;

        public function __construct($tag, $inner = null, array $attributes = null, array $styles = null)
        {
            $this->init();
            $this->tagName = $tag;

            if (is_string($inner)) {
                $this->innerText = $inner;
            } elseif ($inner instanceof HtmlTag) {
                $this->innerTag = $inner;
            }

            if ($attributes !== null) {
                $this->attributes = $attributes;
            }

            if ($styles !== null) {
                $this->styleAttributes = $styles;
            }
        }

        private function init(): void
        {
            $this->attributes = [];
            $this->styleAttributes = [];
        }
    }
}