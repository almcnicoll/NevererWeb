<?php
/**
 * The clue itself, devoid of any crossword context.
 * It is contained within a PlacedClue class to connect it to a crossword.
 */
namespace Crosswords {
    use Basic\Model;
    class Clue extends Model {
        public int $placedclue_id;
        public ?string $question = null;
        public ?string $answer = null;
        public ?string $pattern = null;
        public ?string $explanation = null;

        static string $tableName = "clues";
        static $fields = ['id','placedclue_id','question','answer','pattern','explanation','created','modified'];

        public static $defaultOrderBy = ['id'];

        public const NON_PATTERN_CHARS = "/([^A-Za-z0-9\\s\\-\\?]+)/i";
        public const PATTERN_SPLIT_CHARS = "/([\\s\\-]+)/i";
        public const PATTERN_ANSWER_CHARS = "/[A-Za-z9-0]+/i";
        public const PATTERN_WHITESPACE = "/\\s+/";

        /**
         * Constructs the class, initialising some default values
         */
        public function __construct()
        {
        }

        /** 
         * Extends the built-in save method:
         * - updates the pattern of the clue
         * @return ?int the id of the saved record or null if the save failed
         */
        public function save() : ?int {
            $this->ensureFieldSet('question')->ensureFieldSet('answer');
            $this->answer = strtolower($this->answer);
            $this->pattern = Clue::getPattern($this->answer);
            $returnVal = parent::save(); // Call parent save logic
            return $returnVal;
        }

        /**
         * Get the length of the clue in grid squares (i.e. ignoring punctuation and spaces)
         * @return int the total length
         */
        public function getLength() : int {
            if ($this->pattern == null) {
                if ($this->answer == null) {
                    return 0;
                } else {
                    return strlen($this->getAnswerLetters());
                }
            } else {
                $this->pattern = Clue::getPattern($this->answer);
                $parts = explode(',',str_replace('(','',str_replace(')','',$this->pattern)));
                $length = 0;
                foreach ($parts as $part) { $length += (int)$part; }
                return $length;
            }
        }

        /**
         * Get the pattern for a clue, based on the specified answer text
         */
        public static function getPattern(string $input) : string {
            // Rationalise the string
            // Question-marks indicate unspecified letters - replace them with an actual letter for pattern purposes
            $input = str_replace('?','z',$input);
            // Now get rid of any other non-answer characters
            $input = preg_replace(Clue::NON_PATTERN_CHARS,'',$input);
            // Split on valid delimiters (at time of writing, whitespace and hyphen)
            // NB Delimiters are returned because they are bracketed in the regexp
            $parts = preg_split(Clue::PATTERN_SPLIT_CHARS,$input,-1,PREG_SPLIT_DELIM_CAPTURE);
            for($i=0;$i<count($parts);$i++) {
                // Strip out any whitespace
                $parts[$i] = preg_replace(Clue::PATTERN_WHITESPACE,'',$parts[$i]);
                if ($parts[$i] === '') {
                    // If part is now blank, it was a whitespace delimiter and should be replaced with a comma
                    $parts[$i] = ',';
                } elseif (preg_match('^'.Clue::PATTERN_SPLIT_CHARS,$parts[$i].'$') === false) {
                    // Otherwise if it doesn't match a splitting character, it must be letters - replace them with their length
                    $parts[$i] = strlen($parts[$i]);
                }
            }
            return '('.implode('',$parts).')';
        }

        /**
         * Get the letters that make up the answer, without any spaces or punctuation
         * @return string the letters as a string
         */
        public function getAnswerLetters() : string {
            return preg_replace(Clue::NON_PATTERN_CHARS,'',preg_replace(Clue::PATTERN_SPLIT_CHARS,'',$this->answer));
        }

        /** Returns a blank clone (same length, no specified letters) of the current clue */
        public function blankClone() : Clue {
            $clone = new Clue();
            $clone->answer = str_repeat('?',strlen($this->answer));
            return $clone;
        }
    }
}