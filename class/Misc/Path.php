<?php

namespace Misc {
    class Path
    {
        public static function combine(): string
        {
            $paths = func_get_args();
            $paths = array_map(fn($path) => str_replace(["\\", "/"], DIRECTORY_SEPARATOR, $path), $paths);
            $paths = array_map(fn($path) => self::trimPath($path), $paths);
            error_log(implode(' || ', $paths));
            return implode(DIRECTORY_SEPARATOR, $paths);
        }

        public static function isAbsoluteUrl($url): bool
        {
            return isset(parse_url($url)['host']);
        }

        private static function trimPath(string $path): string
        {
            $path = trim($path);
            if ($path === '') { return $path; }
            $start = $path[0] === DIRECTORY_SEPARATOR ? 1 : 0;
            $end = $path[strlen($path) - 1] === DIRECTORY_SEPARATOR ? -1 : strlen($path);
            return substr($path, $start, $end);
        }
    }
}