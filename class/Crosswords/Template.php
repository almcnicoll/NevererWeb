<?php
/**
 * A reusable grid layout: dimensions, symmetry and the position/length of each (blank) clue.
 * Built-in templates have a null user_id and are visible to everyone; user-saved ones are private.
 */
namespace Crosswords {
    use Basic\Model, Basic\db;
    use PDO, InvalidArgumentException;

    class Template extends Model {
        public ?int $user_id = null;
        public string $name = '';
        public int $rows = 15;
        public int $cols = 15;
        public int $rotational_symmetry_order = 2;
        /** JSON list of [x, y, orientation, length] */
        public string $clues = '[]';

        static string $tableName = "templates";
        static $fields = ['id','user_id','name','rows','cols','rotational_symmetry_order','clues','created','modified'];

        public static $defaultOrderBy = [['name','ASC'],['id','ASC']];

        public function isOwnedBy(int $userId) : bool {
            return $this->user_id === $userId;
        }

        /** @return array[] list of [x, y, orientation, length] */
        public function getClueList() : array {
            return json_decode($this->clues, true) ?: [];
        }

        /**
         * Fingerprint of a grid: its dimensions and the position, orientation and length of every clue.
         * Symmetry is deliberately excluded - it's a setting, not part of the pattern.
         * @param array[] $clues list of [x, y, orientation, length], in any order
         */
        public static function checksum(int $rows, int $cols, array $clues) : string {
            $parts = array_map(fn($c) => "{$c[0]},{$c[1]},".strtolower($c[2]).",{$c[3]}", $clues);
            sort($parts, SORT_STRING);
            return md5("{$rows}x{$cols}|".implode(';', $parts));
        }

        public function getChecksum() : string {
            return self::checksum($this->rows, $this->cols, $this->getClueList());
        }

        /** Builds (but does not save) a template from an existing crossword's grid */
        public static function fromCrossword(Crossword $crossword, string $name, ?int $userId) : Template {
            $clues = [];
            foreach ($crossword->getPlacedClues() as $pc) {
                $clues[] = [(int)$pc->x, (int)$pc->y, strtolower($pc->orientation), $pc->getLength()];
            }
            if (count($clues) == 0) { throw new InvalidArgumentException("A template needs at least one clue"); }
            $t = new Template();
            $t->user_id = $userId;
            $t->name = $name;
            $t->rows = (int)$crossword->rows;
            $t->cols = (int)$crossword->cols;
            $t->rotational_symmetry_order = (int)$crossword->rotational_symmetry_order;
            $t->clues = json_encode($clues);
            return $t;
        }

        /** Creates and saves a crossword with this template's grid, symmetry and all-blank clues */
        public function createCrossword(string $title, int $userId) : Crossword {
            $pdo = db::getPDO();
            $ownTransaction = !$pdo->inTransaction(); // so callers (and the test harness) can wrap this in their own
            if ($ownTransaction) { $pdo->beginTransaction(); }
            try {
                $crossword = new Crossword();
                $crossword->title = $title;
                $crossword->rows = $this->rows;
                $crossword->cols = $this->cols;
                $crossword->rotational_symmetry_order = $this->rotational_symmetry_order;
                $crossword->user_id = $userId;
                $crossword->save();
                foreach ($this->getClueList() as [$x, $y, $orientation, $length]) {
                    $pc = new PlacedClue();
                    $pc->crossword_id = $crossword->id;
                    $pc->x = $x;
                    $pc->y = $y;
                    $pc->orientation = $orientation;
                    $clue = new Clue();
                    $clue->answer = str_repeat('?', $length);
                    $pc->setClue($clue, false);
                    $pc->save();
                }
                if ($ownTransaction) { $pdo->commit(); }
            } catch (\Throwable $t) {
                if ($ownTransaction) { $pdo->rollBack(); }
                throw $t;
            }
            return $crossword;
        }

        /** Built-in templates plus the user's own, built-ins first */
        public static function getVisibleTo(int $userId) : array {
            $stmt = db::getPDO()->prepare(
                "SELECT * FROM `templates` WHERE `user_id` IS NULL OR `user_id` = ? ORDER BY `user_id` IS NOT NULL, `rows` * `cols`, `name`, `id`"
            );
            $stmt->setFetchMode(PDO::FETCH_CLASS|PDO::FETCH_PROPS_LATE, static::class);
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        }

        /** A template if it exists and the user may see it, otherwise null */
        public static function getVisibleById(int $id, int $userId) : ?Template {
            $t = static::getById($id);
            return ($t !== null && ($t->user_id === null || $t->user_id === $userId)) ? $t : null;
        }

        /**
         * How many of the user's crosswords have each grid pattern, whether or not they were made from a template
         * @return int[] checksum => count
         */
        public static function countUserCrosswordsByChecksum(int $userId) : array {
            $stmt = db::getPDO()->prepare(
                "SELECT c.`id`, c.`rows`, c.`cols`, p.`x`, p.`y`, p.`orientation`, k.`answer`
                 FROM `crosswords` c
                 JOIN `placedclues` p ON p.`crossword_id` = c.`id`
                 LEFT JOIN `clues` k ON k.`placedclue_id` = p.`id`
                 WHERE c.`user_id` = ?"
            );
            $stmt->execute([$userId]);
            $grids = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $grids[$r['id']]['dims'] = [(int)$r['rows'], (int)$r['cols']];
                $grids[$r['id']]['clues'][] = [(int)$r['x'], (int)$r['y'], $r['orientation'], strlen(Clue::stripToAnswerLetters($r['answer'] ?? ''))];
            }
            $counts = [];
            foreach ($grids as $g) {
                $sum = self::checksum($g['dims'][0], $g['dims'][1], $g['clues']);
                $counts[$sum] = ($counts[$sum] ?? 0) + 1;
            }
            return $counts;
        }

        /** Small inline SVG of the grid: white squares are covered by a clue, black are not */
        public function getPreviewSvg(int $maxPx = 96) : string {
            $cell = max(1, intdiv($maxPx, max($this->rows, $this->cols)));
            $white = [];
            foreach ($this->getClueList() as [$x, $y, $orientation, $length]) {
                for ($i = 0; $i < $length; $i++) {
                    $cx = $orientation == PlacedClue::ACROSS ? $x + $i : $x;
                    $cy = $orientation == PlacedClue::ACROSS ? $y : $y + $i;
                    $white["{$cx},{$cy}"] = "M".($cx + 0.05)." ".($cy + 0.05)."h.9v.9h-.9z";
                }
            }
            return "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 {$this->cols} {$this->rows}' width='".($cell * $this->cols)."' height='".($cell * $this->rows)."' role='img' aria-label='Grid preview' shape-rendering='crispEdges'>"
                 . "<rect width='{$this->cols}' height='{$this->rows}' fill='#000' stroke='#adb5bd' stroke-width='.1'/>"
                 . "<path fill='#fff' d='".implode('', $white)."'/></svg>";
        }

        public function getSymmetryLabel() : string {
            return match ($this->rotational_symmetry_order) {
                4 => '4-fold (90°)',
                2 => '2-fold (180°)',
                default => 'None',
            };
        }
    }
}
