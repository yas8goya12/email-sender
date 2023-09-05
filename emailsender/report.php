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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>

// defined('MOODLE_INTERNAL') || die;

require_once('../../config.php');
//Checking the capability
if (has_capability('local/emailsender:managefiles', context_system::instance())) {
    global $DB;
    $PAGE->set_context(context_system::instance());
    $PAGE->set_title(get_string('reporttitle', 'local_emailsender'));
    $PAGE->set_heading(get_string('reporttitle', 'local_emailsender'));
    $PAGE->set_url($CFG->wwwroot . '/local/emailsender/report.php');
    $PAGE->set_pagelayout('admin');
    $user_records = $DB->get_records('emaillogs');
    echo $OUTPUT->header();
    $table = new html_table();
    $table->head = array(
        get_string('sno', 'local_emailsender'),
        get_string('fullname'),
        get_string('email'),
        get_string('status'),
        get_string('time')
    );
    $i = 0;
    if ($user_records) {
        foreach ($user_records as $record) {
            $i++;
            $table->data[] = array(
                'Sno.' => $i,
                'name' => $record->firstname . " " . $record->lastname,
                'email' => $record->email,
                'status' => $record->status = 1 ? "Success" : "Pending",
                'time' => date("Y-m-d H:i:s", $record->timecreated),
            );
        }
    }
    echo html_writer::table($table);
    echo $OUTPUT->footer();
} else {
    throw new \moodle_exception('nopermissiontoaccesspage', '', $CFG->wwwroot);
}
