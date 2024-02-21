<?php

namespace Neverer\UtilityClass;

class Crossword
{
    private $width = 15;
    private $height = 15;
    private $rotationalSymmetryOrder = 4;
    public $title;

    public function getRotationalSymmetryOrder()
    {
        return $this->rotationalSymmetryOrder;
    }

    public function setRotationalSymmetryOrder($value)
    {
        switch ($value) {
            case 1:
            case 2:
            case 4:
                $this->rotationalSymmetryOrder = $value;
                break;
            default:
                throw new \Exception(sprintf(
                    "Cannot set order of rotational symmetry to %d. Valid values are 1, 2 & 4.",
                    $value
                ));
        }
    }

    public function getSize()
    {
        return $this->width;
    }

    public function setSize($value)
    {
        $this->width = $value;
        $this->height = $value;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function setWidth($value)
    {
        $this->width = $value;
        if ($this->rotationalSymmetryOrder == 4) {
            $this->height = $value;
        }
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function setHeight($value)
    {
        $this->height = $value;
        if ($this->rotationalSymmetryOrder == 4) {
            $this->width = $value;
        }
    }

    public function getCols()
    {
        return $this->width;
    }

    public function setCols($value)
    {
        $this->width = $value;
    }

    public function getRows()
    {
        return $this->height;
    }

    public function setRows($value)
    {
        $this->height = $value;
    }

    public $placedClues = [];

    public function getSortedClues()
    {
        $sc = [];
        $pcTmp = array_values(array_filter($this->placedClues));
        $lastOrder = -1;
        $clueIncrement = 0;
        foreach ($pcTmp as $pc) {
            if ($pc->order != $lastOrder) {
                $clueIncrement++;
            }
            if ($pc->orientation > AD::Unset) {
                $k = new KeyValuePair($pc->orientation, $clueIncrement);
                while (array_key_exists($k, $sc)) {
                    $clueIncrement++;
                    $k = new KeyValuePair($pc->orientation, $clueIncrement);
                }
                $pc->placeNumber = $clueIncrement;
                $sc[$k] = $pc;
            }
            $lastOrder = $pc->order;
        }
        return $sc;
    }

    public function getSortedClueList()
    {
        $sortedClues = $this->getSortedClues();
        ksort($sortedClues);
        $result = [];
        foreach ($sortedClues as $entry) {
            $result[] = $entry;
        }
        return $result;
    }

    private function clueHeads()
    {
        /* TODO - reinstate this when PlacedClue imported
        $r = 0;
        return array_map(function ($pc) use (&$r) {
            $r++;
            return new KeyValuePair(PlacedClue::GetOrder($pc->y, $pc->x), $r);
        }, array_values(array_filter($this->placedClues)));
        */
        return [];
    }

    public function refreshNumbers()
    {
        /* TODO - reinstate this when PlacedClue imported
        $lookup = [];
        $clues = $this->clueHeads();
        foreach ($clues as $kvp) {
            $lookup[$kvp->Key] = $kvp->Value;
        }

        foreach ($this->placedClues as $pc) {
            $o = PlacedClue::GetOrder($pc->y, $pc->x);
            if (array_key_exists($o, $lookup)) {
                $pc->placeNumber = $lookup[$o];
            }
        }
        */
    }

    public function clone()
    {
        $c = new Crossword();
        $this->copyTo($c);
        return $c;
    }

    public function copyTo(&$target)
    {
        foreach ($this as $key => $value) {
            if ($key !== 'placedClues' && $key !== 'title') {
                $target->{$key} = $value;
            }
        }

        $target->placedClues = [];
        foreach ($this->placedClues as $pc) {
            $target->placedClues[] = $pc->clone();
        }
    }
}