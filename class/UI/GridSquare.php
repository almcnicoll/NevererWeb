<?php

namespace UI {
    use Basic\BaseClass;
    use InvalidArgumentException;

    class GridSquare extends BaseClass {

        public const FLAG_CONFLICT = 1;
        public const FLAG_FEWMATCHES = 2;    
        public const FLAG_NOMATCHES = 4;

        public const INTERSECTS_NONE = 0;
        public const INTERSECTS_ACROSS = 1;
        public const INTERSECTS_DOWN = 2;
        public const INTERSECTS_BOTH = 3;

        public int $x = 0;
        public int $y = 0;
        public bool $black_square = true;
        public string $letter = '';
        public ?int $clue_number = null;
        public int $flags = 0;
        public mixed $placed_clue_ids = [];
        public int $intersects = self::INTERSECTS_NONE;

        public function __construct(int $x, int $y, bool $black_square, string $letter = '', ?int $clue_number = null, int $flags = 0, mixed $placed_clue_ids = [], int $intersects = self::INTERSECTS_NONE) {
            $this->x = $x;
            $this->y = $y;
            $this->black_square = $black_square;
            $this->letter = $letter;
            $this->clue_number = $clue_number;
            $this->flags = $flags;
            $this->placed_clue_ids = $placed_clue_ids;
            $this->intersects = $intersects;
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
        
        /**
         * Checks a flag on the GridSquare
         * @return bool returns whether or not the flag is set
         */
        public function hasFlag(int $val) : bool {
            return (($this->flags & $val)>0);
        }

        /**
         * Sets an intersect on the GridSquare, whether or not it was already set
         * @return GridSquare returns the GridSquare to allow for method chaining
         */
        public function setIntersect(int $intersectType) : GridSquare {
            if (($intersectType < self::INTERSECTS_NONE) || ($intersectType >= self::INTERSECTS_BOTH)) { throw new InvalidArgumentException("Invalid intersect type passed."); }
            $this->intersects |= $intersectType;
            return $this;
        }
        
        /**
         * Clears an intersect on the GridSquare, whether or not it was already set
         * @return GridSquare returns the GridSquare to allow for method chaining
         */
        public function clearIntersect(int $intersectType) : GridSquare {
            if (($intersectType < self::INTERSECTS_NONE) || ($intersectType >= self::INTERSECTS_BOTH)) { throw new InvalidArgumentException("Invalid intersect type passed."); }
            $this->intersects &= ~$intersectType;
            return $this;
        }
        
        /**
         * Checks an intersect on the GridSquare
         * @return bool returns whether or not the intersect is set
         */
        public function hasIntersect(int $intersectType) : bool {
            if (($intersectType < self::INTERSECTS_NONE) || ($intersectType >= self::INTERSECTS_BOTH)) { throw new InvalidArgumentException("Invalid intersect type passed."); }
            return (($this->intersects & $intersectType)>0);
        }
    }
}