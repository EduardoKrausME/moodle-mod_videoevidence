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
 * source_manager.php
 *
 * @package   mod_videoevidence
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_videoevidence;

use context_module;
use moodle_url;
use stdClass;

/**
 * Class source_manager.
 */
class source_manager {
    /**
     * Method player_data.
     *
     * @param stdClass $activity Parameter activity.
     * @param context_module $context Parameter context.
     * @return array Return value.
     */
    public static function player_data(stdClass $activity, context_module $context): array {
        $source = $activity->videosource;
        $data = ['source' => $source, 'url' => '', 'youtubeid' => '', 'vimeoid' => '', 'vimeohash' => ''];
        if ($source === 'upload') {
            $files = get_file_storage()->get_area_files($context->id, 'mod_videoevidence', 'video', 0, 'id', false);
            if ($files) {
                $file = reset($files);
                $data['url'] = moodle_url::make_pluginfile_url($context->id, 'mod_videoevidence', 'video', 0,
                    $file->get_filepath(), $file->get_filename())->out(false);
            }
        } else if ($source === 'url') {
            $data['url'] = (string)$activity->videourl;
        } else if ($source === 'youtube') {
            $data['youtubeid'] = self::youtube_id((string)$activity->videourl) ?: '';
        } else if ($source === 'vimeo') {
            $config = self::vimeo_config((string)$activity->videourl) ?: [];
            $data['vimeoid'] = $config['id'] ?? '';
            $data['vimeohash'] = $config['hash'] ?? '';
        }
        $posters = get_file_storage()->get_area_files($context->id, 'mod_videoevidence', 'poster', 0, 'id', false);
        $data['poster'] = '';
        if ($posters) {
            $poster = reset($posters);
            $data['poster'] = moodle_url::make_pluginfile_url($context->id, 'mod_videoevidence', 'poster', 0,
                $poster->get_filepath(), $poster->get_filename())->out(false);
        }
        return $data;
    }

    /**
     * Method youtube_id.
     *
     * @param string $url Parameter url.
     * @return string|false Return value.
     */
    public static function youtube_id(string $url): string|false {
        $parts = parse_url(trim($url));
        if (!$parts || empty($parts['host'])) {
            return false;
        }
        $host = strtolower(preg_replace('/^www\./', '', $parts['host']));
        $id = '';
        if ($host === 'youtu.be') {
            $id = trim($parts['path'] ?? '', '/');
        } else if (in_array($host, ['youtube.com', 'youtube-nocookie.com', 'm.youtube.com'], true)) {
            parse_str($parts['query'] ?? '', $query);
            if (!empty($query['v'])) {
                $id = (string)$query['v'];
            } else if (preg_match('~^/(?:embed|shorts)/([A-Za-z0-9_-]{6,})~', $parts['path'] ?? '', $m)) {
                $id = $m[1];
            }
        }
        return preg_match('/^[A-Za-z0-9_-]{6,}$/', $id) ? $id : false;
    }

    /**
     * Method vimeo_config.
     *
     * @param string $url Parameter url.
     * @return array|false Return value.
     */
    public static function vimeo_config(string $url): array|false {
        $parts = parse_url(trim($url));
        if (!$parts || empty($parts['host'])) {
            return false;
        }
        $host = strtolower(preg_replace('/^www\./', '', $parts['host']));
        if (!in_array($host, ['vimeo.com', 'player.vimeo.com'], true)) {
            return false;
        }
        $path = trim($parts['path'] ?? '', '/');
        if (!preg_match('~^(?:video/)?(\d+)(?:/([A-Za-z0-9]+))?$~', $path, $m)) {
            return false;
        }
        parse_str($parts['query'] ?? '', $query);
        $hash = $m[2] ?? '';
        if ($hash === '' && !empty($query['h']) && preg_match('/^[A-Za-z0-9]+$/', $query['h'])) {
            $hash = $query['h'];
        }
        return ['id' => $m[1], 'hash' => $hash];
    }
}
