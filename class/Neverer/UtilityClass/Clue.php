<?php
namespace Neverer\UtilityClass;

class Clue
{
    public const BlankQuestion = "[blank clue]";
    public static $NonCountingChars = [" ", "-", "'"];

    private $question = "";
    private $answer = "";

    public function getQuestion()
    {
        return $this->question;
    }

    public function setQuestion($value)
    {
        $this->question = $value;
    }

    public function getAnswer()
    {
        return $this->answer;
    }

    public function setAnswer($value)
    {
        $lengthChange = (strlen($value) != strlen($this->answer));
        if ($value === null) {
            $value = "";
        }
        $reStrip = "/[^A-Za-z?" . preg_quote(implode("", self::$NonCountingChars), "/") . "]+/";
        $this->answer = strtoupper(preg_replace($reStrip, "", $value));
        $changeType = ClueChangedEventArgs::NothingSignificant;
        if ($lengthChange) {
            $changeType |= ClueChangedEventArgs::LengthChanged;
        }
        if ($this->changed !== null) {
            // TODO - work out how to do events
            //$this->changed($this, new ClueChangedEventArgs(false, $changeType));
        }
    }

    public function getLetters()
    {
        $reStrip = "/[^A-Za-z?]+/";
        return preg_replace($reStrip, "", $this->answer);
    }

    public static function toRegExp($source, $allowFlexibleSpaces = true)
    {
        if ($allowFlexibleSpaces) {
            $allChars = str_split(str_replace("?", ".", $source));
            $prefix = "(";
            $suffix = "([" . preg_quote(implode("", self::$NonCountingChars), "/") . "]?))";
            $pattern = "^" . $prefix . implode($suffix . $prefix, $allChars) . $suffix . "$";
            return new Regex($pattern, Regex::IgnoreCase);
        } else {
            throw new \Exception("No implementation of this function yet");
        }
    }

    public function getRegExp()
    {
        return Clue::toRegExp($this->answer);
    }

    public function getLength()
    {
        return strlen($this->getLetters());
    }

    public function getPattern()
    {
        $parts = array_filter(explode(implode("", self::$NonCountingChars), $this->answer));
        return array_map('strlen', $parts);
    }

    public function __toString()
    {
        $output = "";
        $i = 0;
        foreach ($this->getPattern() as $l) {
            if (($l + $i) >= strlen($this->answer)) {
                $output .= $l;
                continue;
            }
            switch (substr($this->answer, $l + $i, 1)) {
                case "-":
                    $output .= $l . "-";
                    break;
                default:
                    $output .= $l . ",";
                    break;
            }
            $i += $l + 1;
        }
        return sprintf("%s (%s)", $this->question, $output);
    }

    public $id;

    public function blankClone($makeContiguous = true)
    {
        $c = new Clue();
        $reAllQuestionMarks = "/[^?" . preg_quote(implode("", self::$NonCountingChars), "/") . "]/";
        $c->setAnswer(preg_replace($reAllQuestionMarks, "?", $this->answer));
        if ($makeContiguous) {
            $reNoDividers = "/[^?]/";
            $c->setAnswer(preg_replace($reNoDividers, "", $c->getAnswer()));
        }
        $c->setQuestion(self::BlankQuestion);
        return $c;
    }

    public function copyTo(&$cDest)
    {
        if ($cDest === null) {
            $cDest = new Clue();
        }
        $cDest->setQuestion($this->getQuestion());
        $cDest->setAnswer($this->getAnswer());
    }

    public function __construct($q = "", $a = "")
    {
        $this->id = uniqid();
        $this->question = $q;
        $this->answer = $a;
    }

    public $changed;
}

class ClueChangedEventArgs
{
    const NothingSignificant = 0;
    const LengthChanged = 1;

    public $changeType;

    public function __construct($sender, $changeType)
    {
        $this->changeType = $changeType;
    }
}