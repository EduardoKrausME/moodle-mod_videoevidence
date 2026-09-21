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
 * segment_manager.php
 *
 * @package   mod_videoevidence
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videoevidence;

/**
 * Class segment_manager.
 */
class segment_manager {
    /**
     * Method merge.
     *
     * @param array $segments Parameter segments.
     * @param float $start Parameter start.
     * @param float $end Parameter end.
     * @return array Return value.
     */
    public static function merge(array $segments, float $start, float $end): array {
        if ($end <= $start) {
            return self::normalize($segments);
        }
        $segments[] = [$start, $end];
        return self::normalize($segments);
    }

    /**
     * Method normalize.
     *
     * @param array $segments Parameter segments.
     * @return array Return value.
     */
    public static function normalize(array $segments): array {
        $clean = [];
        foreach ($segments as $segment) {
            if (!is_array($segment) || count($segment) < 2) {
                continue;
            }
            $start = max(0, (float)$segment[0]);
            $end = max($start, (float)$segment[1]);
            if ($end > $start) {
                $clean[] = [$start, $end];
            }
        }
        usort($clean, static fn($a, $b) => $a[0] <=> $b[0]);
        $merged = [];
        foreach ($clean as $segment) {
            if (!$merged || $segment[0] > $merged[count($merged) - 1][1] + 0.25) {
                $merged[] = $segment;
            } else {
                $merged[count($merged) - 1][1] = max($merged[count($merged) - 1][1], $segment[1]);
            }
        }
        return $merged;
    }

    /**
     * Method duration.
     *
     * @param array $segments Parameter segments.
     * @return float Return value.
     */
    public static function duration(array $segments): float {
        $total = 0.0;
        foreach (self::normalize($segments) as $segment) {
            $total += $segment[1] - $segment[0];
        }
        return $total;
    }
}
