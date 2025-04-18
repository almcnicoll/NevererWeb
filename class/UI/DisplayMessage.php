<?php
namespace UI {
    /**
     * Contains an error message
     */
    class DisplayMessage extends \Basic\Model {
        public const LVL_UNKNOWN = 0;
        public const LVL_FATAL = 1;
        public const LVL_ERROR = 2;
        public const LVL_WARNING = 3;
        public const LVL_INFORMATION = 4;
        public const LVL_VERBOSE = 5;
        public const LVL_SUCCESS = 6;

        /** Indicates which field to order by */
        public $defaultOrderBy = 'created';

        /** Indicates the severity of the message */
        public $level = self::LVL_UNKNOWN;

        /** Contains the message to be displayed */
        public $message = '';

        /** Indicates the number of times the message should be shown */
        public $displayCount = 1;

        /**
         * Constructor
         * @param string $message the message to be displayed
         * @param int $level the type of message
         * @param int $displayCount the number of times to show the message
         */
        public function __construct(string $message, $level = self::LVL_INFORMATION, $displayCount = 1) {
            $this->message = $message;
            $this->level = $level;
            $this->displayCount = $displayCount;
        }

        /**
         * @param string $prefix the prefix to add to the color string
         */
        public function getColor($prefix='#') : string {
            // Choose the colour based on the level
            switch ($this->level) {
                case self::LVL_FATAL;
                    return $prefix.'660000';
                case self::LVL_ERROR;
                    return $prefix.'dc3545';
                case self::LVL_WARNING;
                    return $prefix.'ffc107';
                case self::LVL_INFORMATION;
                    return $prefix.'0dcaf0';
                case self::LVL_VERBOSE;
                    return $prefix.'6c757d';
                case self::LVL_SUCCESS;
                    return $prefix.'198754';
                default:
                    return $prefix.'000000';
            }
        }
        /**
         * @param string $prefix the prefix to add to the color string
         */
        public function getColorClass() : string {
            // Choose the Bootstrap colour class based on the level
            switch ($this->level) {
                case self::LVL_FATAL;
                    return 'text-danger';
                case self::LVL_ERROR;
                    return 'text-danger';
                case self::LVL_WARNING;
                    return 'text-warning';
                case self::LVL_INFORMATION;
                    return 'text-info';
                case self::LVL_VERBOSE;
                    return 'text-muted';
                case self::LVL_SUCCESS;
                    return 'text-success';
                default:
                    return 'text-secondary';
            }
        }

        /** 
         * Adds a new message into the database
         * @param string $message the message to be displayed
         * @param int $level the type of message
         * @param int $displayCount the number of times to show the message
         * @return int the id of the message, to be inserted into the session for later retrieval
        */
        public static function add(string $message, $level = self::LVL_INFORMATION, $displayCount = 1) : int {
            $msg = new DisplayMessage($message, $level, $displayCount);
            return $msg->id;
        }

        /**
         * Retrieves the list of messages, so long as they have not been displayed the right number of times
         * @var mixed $params a DisplayMessage id, or an array of DisplayMessage ids
         */
        public static function getList(...$params) : mixed {
            // Retrieve messages
            $messages = self::find([['id','IN',$params],['displayCount','>',0]]);
            /** @var DisplayMessage $message */
            foreach ($messages as $message) {
                if ($message->displayCount > 0) { $message->displayCount -= 1; } // Mark it shown another time
                $message->save();
            }
            return $messages;
        }

        /**
         * Retrieves the list of current messages as a formatted HTML string
         * @var mixed $params a DisplayMessage id, or an array of DisplayMessage ids
         */
        public static function getFormattedList(...$params) : string {
            $html = '';
            // NB - don't abstract code to getList(), or we'll loop through the messages twice (decrement, display)
            // Retrieve messages
            $messages = self::find([['id','IN',$params],['displayCount','>',0]]);
            /** @var DisplayMessage $message */
            foreach ($messages as $message) {
                if ($message->displayCount > 0) { $message->displayCount -= 1; } // Mark it shown another time
                $message->save();
                $colorClass = $message->getColorClass();
                $html .= "<div class='{$colorClass}'>{$message->message}</div>\n";
            }
            $html = "<div class='messages-container'>{$html}</div>";
            return $html;
        }
    }
}