<?php

class GridSquare {
    public int $x = 0;
    public int $y = 0;
    public bool $black_square = true;
    public string $letter = '';
    public ?int $clue_number = null;
    public int $flags = 0;

    public const FLAG_CONFLICT = 1;
    public const FLAG_FEWMATCHES = 2;    
    public const FLAG_NOMATCHES = 4;

    public function __construct(int $x, int $y, bool $black_square, string $letter = '', ?int $clue_number = null, int $flags = 0) {
        $this->x = $x;
        $this->y = $y;
        $this->black_square = $black_square;
        $this->letter = $letter;
        $this->clue_number = $clue_number;
        $this->flags = $flags;
    }

    public function toJson() {
        return json_encode($this);
    }

    /**
     * Sets a flag on the GridSquare, whether or not it was already set
     * @return GridSquare returns the GridSquare to allow for method chaining
     */
    public function setFlag(int $val) : GridSquare {
        $this->flags |= $val;
        return $this;
    }
    
    /**
     * Clears a flag on the GridSquare, whether or not it was already set
     * @return GridSquare returns the GridSquare to allow for method chaining
     */
    public function clearFlag(int $val) : GridSquare {
        $this->flags &= ~$val;
        return $this;
    }
}