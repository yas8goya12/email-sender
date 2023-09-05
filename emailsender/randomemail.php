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
require_once('userform.php');
//Checking the capability
if (has_capability('local/emailsender:managefiles', context_system::instance())) {
    global $DB;
    $PAGE->set_context(context_system::instance());
    $PAGE->set_title(get_string('randomemailtitle', 'local_emailsender'));
    $PAGE->set_heading(get_string('randomemailtitle', 'local_emailsender'));
    $PAGE->set_url($CFG->wwwroot . '/local/emailsender/randomemail.php');
    $PAGE->set_pagelayout('admin');
    $returnurl = new moodle_url('/local/emailsender/randomemail.php');

    $mform = new random_userform();
    if ($mform->is_cancelled()) {
        redirect($CFG->wwwroot . '/local/point_system/courserulemanager.php', 'Form has been cancelled');
    } else if ($data = $mform->get_data()) {
        $message = $data->mailbody;
        $sentemail = 0;
        foreach ($data->users as $users) {
            $reciepent = core_user::get_user($users);
            $from = core_user::get_noreply_user(); //Admin user
            $subject = "Test email";
            $emailstatus = email_to_user($reciepent, $from, $subject, $message);
            if ($emailstatus) {
                $sentemail++;
            }
        }
        echo $OUTPUT->header();
        \core\notification::add($sentemail . " emails sent successfully", \core\output\notification::NOTIFY_INFO);
        echo $OUTPUT->continue_button($returnurl);
        echo $OUTPUT->footer();
    } else {
        echo $OUTPUT->header();
        $mform->display();
        echo $OUTPUT->footer();
        die;
    }
} else {
    throw new \moodle_exception('nopermissiontoaccesspage', '', $CFG->wwwroot);
}
