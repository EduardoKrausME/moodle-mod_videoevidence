<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * timecode.php
 *
 * @package   mod_videoevidence
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videoevidence;

/**
 * Class timecode.
 */
class timecode {
    /**
     * Method parse.
     *
     * @param string $value Parameter value.
     * @return ?float Return value.
     */
    public static function parse(string $value): ?float {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return max(0, (float)$value);
        }
        $parts = explode(':', $value);
        if (count($parts) < 2 || count($parts) > 3) {
            return null;
        }
        foreach ($parts as $part) {
            if (!is_numeric($part)) {
                return null;
            }
        }
        if (count($parts) === 2) {
            [$m, $s] = array_map('floatval', $parts);
            if ($s >= 60) {
                return null;
            }
            return max(0, $m * 60 + $s);
        }
        [$h, $m, $s] = array_map('floatval', $parts);
        if ($m >= 60 || $s >= 60) {
            return null;
        }
        return max(0, $h * 3600 + $m * 60 + $s);
    }

    /**
     * Method format.
     *
     * @param float $seconds Parameter seconds.
     * @return string Return value.
     */
    public static function format(float $seconds): string {
        $seconds = max(0, (int)round($seconds));
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;
        return $h > 0 ? sprintf('%02d:%02d:%02d', $h, $m, $s) : sprintf('%02d:%02d', $m, $s);
    }
}
