<?php
namespace Neverer\UtilityClass;

class Regex
{
    // Constants equivalent to RegexOptions enum in C#
    const None = 0;
    const IgnoreCase = 1;
    const Multiline = 2;
    const ExplicitCapture = 4;
    const Compiled = 8;
    const Singleline = 16;
    const IgnorePatternWhitespace = 32;
    const RightToLeft = 64;
    const ECMAScript = 256;
    const CultureInvariant = 512;

    
    private $pattern;
    private $options;

    public function __construct($pattern, $options = null)
    {
        $this->pattern = $pattern;
        $this->options = $options;
    }

    public function isMatch($input)
    {
        return preg_match($this->pattern, $input) === 1;
    }

    public function match($input)
    {
        preg_match($this->pattern, $input, $matches);
        return $matches;
    }

    public function matches($input)
    {
        preg_match_all($this->pattern, $input, $matches);
        return $matches;
    }

    public function replace($input, $replacement)
    {
        return preg_replace($this->pattern, $replacement, $input);
    }

    public function replaceCallback($input, $callback)
    {
        return preg_replace_callback($this->pattern, $callback, $input);
    }

    public function split($input, $limit = -1)
    {
        return preg_split($this->pattern, $input, $limit, PREG_SPLIT_DELIM_CAPTURE);
    }

    public static function escape($str)
    {
        return preg_quote($str);
    }
}

/* Example usage:
$regex = new Regex('/\d+/'); // Create a Regex object with pattern '\d+'
$input = "The code is 12345";
if ($regex->isMatch($input)) {
    echo "Found a match!\n";
} else {
    echo "No match found.\n";
}

$matches = $regex->match($input);
print_r($matches);
*/