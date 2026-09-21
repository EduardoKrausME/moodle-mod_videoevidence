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
 * videoevidence.php
 *
 * @package   mod_videoevidence
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['addevidence'] = 'Add evidence';
$string['addquestion'] = 'Add question';
$string['allowseek'] = 'Allow free seeking';
$string['answerlocked'] = 'This answer has already been graded and is locked.';
$string['backtoactivity'] = 'Back to activity';
$string['backtoreport'] = 'Back to report';
$string['completiondetail:evidence'] = 'Submit all mandatory evidence questions';
$string['completionevidence'] = 'Require all mandatory evidence questions to be submitted';
$string['editquestion'] = 'Edit question';
$string['endbeforestart'] = 'End time cannot be before start time.';
$string['endtime'] = 'End';
$string['evidencerequirement'] = 'Select at least {$a->min} and at most {$a->max} evidence item(s).';
$string['evidenceselected'] = 'Evidence selected';
$string['expectedboth'] = 'Define both start and end of the expected range, or leave both empty.';
$string['expectedend'] = 'Expected range end (optional)';
$string['expectedstart'] = 'Expected range start (optional)';
$string['feedback'] = 'Feedback';
$string['grade'] = 'Grade';
$string['gradessaved'] = 'Grades saved.';
$string['invaliddirecturl'] = 'Use a direct .mp4, .webm, .ogv, .m4v, .mov or .m3u8 URL.';
$string['invalidmaximum'] = 'The maximum must be greater than or equal to the minimum.';
$string['invalidminimum'] = 'The minimum must be at least 1.';
$string['invalidtimecode'] = 'Use seconds, MM:SS, or HH:MM:SS.';
$string['invalidtolerance'] = 'Tolerance cannot be negative.';
$string['invalidvideourl'] = 'Enter a valid HTTP/HTTPS video URL.';
$string['invalidvimeourl'] = 'Enter a supported Vimeo URL.';
$string['invalidweight'] = 'The weight must be between 0 and 100.';
$string['invalidyoutubeurl'] = 'Enter a supported YouTube URL.';
$string['justification'] = 'Why is this excerpt evidence?';
$string['justificationscore'] = 'Justification score';
$string['justificationweight'] = 'Justification score weight (%)';
$string['managequestions'] = 'Manage questions';
$string['maxevidence'] = 'Maximum evidence selections';
$string['maxevidencereached'] = 'The maximum number of evidence items for this question has been reached.';
$string['minevidence'] = 'Minimum evidence selections';
$string['modulename'] = 'Video Evidence';
$string['modulename_help'] = 'Students answer questions by selecting evidence in a video and explaining why the selected moment or interval supports the answer.';
$string['modulenameplural'] = 'Video Evidence activities';
$string['noactivities'] = 'There are no Video Evidence activities in this course.';
$string['noevidence'] = 'No evidence selected.';
$string['noquestions'] = 'No evidence questions have been created yet.';
$string['nostudents'] = 'No enrolled students were found.';
$string['notenoughevidence'] = 'Add the minimum required number of evidence items before submitting.';
$string['pluginadministration'] = 'Video Evidence administration';
$string['pluginname'] = 'Video Evidence';
$string['poster'] = 'Poster image';
$string['privacy:metadata:answers'] = 'Stores the state and grading of student answers.';
$string['privacy:metadata:answers:feedback'] = 'Teacher feedback.';
$string['privacy:metadata:answers:finalscore'] = 'Final answer score.';
$string['privacy:metadata:answers:status'] = 'The answer state.';
$string['privacy:metadata:answers:userid'] = 'The user who owns the answer.';
$string['privacy:metadata:evidence'] = 'Stores video evidence selections.';
$string['privacy:metadata:evidence:endtime'] = 'Evidence end time.';
$string['privacy:metadata:evidence:justification'] = 'Student justification for the evidence.';
$string['privacy:metadata:evidence:starttime'] = 'Evidence start time.';
$string['privacy:metadata:progress'] = 'Stores video playback progress.';
$string['privacy:metadata:progress:lastposition'] = 'Last saved playback position.';
$string['privacy:metadata:progress:percent'] = 'Unique watched percentage.';
$string['privacy:metadata:progress:userid'] = 'The user whose progress is stored.';
$string['privacy:metadata:progress:watchedsegments'] = 'Segments actually watched.';
$string['quantityscore'] = 'Quantity score';
$string['quantityweight'] = 'Minimum quantity score weight (%)';
$string['questiondeleted'] = 'Question deleted.';
$string['questionnumber'] = 'Question {$a}';
$string['questionsanswered'] = 'Questions answered';
$string['questionsaved'] = 'Question saved.';
$string['questiontext'] = 'Question';
$string['report'] = 'Report';
$string['required'] = 'Required';
$string['requiredquestion'] = 'Required for activity completion';
$string['resumeask'] = 'Ask before resuming';
$string['resumeautomatic'] = 'Resume automatically';
$string['resumefromstart'] = 'Start from the beginning';
$string['resumeplayback'] = 'Resume playback';
$string['resumequestion'] = 'You stopped at {$a}. Continue from there?';
$string['savegrades'] = 'Save grades';
$string['score'] = 'Score';
$string['selectioninterval'] = 'Time interval';
$string['selectionmoment'] = 'Single moment';
$string['selectionscore'] = 'Selection score';
$string['selectiontype'] = 'Evidence selection type';
$string['selectionweight'] = 'Selection score weight (%)';
$string['sourceupload'] = 'Upload to Moodle';
$string['sourceurl'] = 'Direct URL / HLS';
$string['sourcevimeo'] = 'Vimeo';
$string['sourceyoutube'] = 'YouTube';
$string['starttime'] = 'Start';
$string['statusdraft'] = 'Draft';
$string['statusgraded'] = 'Graded';
$string['statussubmitted'] = 'Submitted';
$string['student'] = 'Student';
$string['submitanswer'] = 'Submit answer';
$string['submitted'] = 'Submitted';
$string['timeline'] = 'Watched timeline and evidence markers';
$string['tolerance'] = 'Tolerance in seconds';
$string['usecurrenttime'] = 'Use current time as start';
$string['usecurrenttimeend'] = 'Use current time as end';
$string['videoevidence:addinstance'] = 'Add a new Video Evidence activity';
$string['videoevidence:grade'] = 'Grade Video Evidence answers';
$string['videoevidence:managequestions'] = 'Manage Video Evidence questions';
$string['videoevidence:view'] = 'View Video Evidence activity';
$string['videoevidence:viewreport'] = 'View Video Evidence reports';
$string['videoevidencename'] = 'Activity name';
$string['videofile'] = 'Video file';
$string['videoheader'] = 'Video';
$string['videosource'] = 'Video source';
$string['videourl'] = 'Video URL';
$string['watchedpercent'] = 'Video watched';
