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

defined('MOODLE_INTERNAL') || die;

require('../../config.php');
require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->dirroot . '/local/emailsender/userform.php');
require_once($CFG->dirroot . '/local/emailsender/classes/preview.php');
//Checking the capability
if (has_capability('local/emailsender:managefiles', context_system::instance())) {
    $iid         = optional_param('iid', '', PARAM_INT);
    $previewrows = optional_param('previewrows', 10, PARAM_INT);
    $PAGE->set_context(context_system::instance());
    $PAGE->set_title(get_string('mainpagetitle', 'local_emailsender'));
    $PAGE->set_heading(get_string('mainpagetitle', 'local_emailsender'));
    $PAGE->set_url($CFG->wwwroot . '/local/emailsender/index.php');
    $PAGE->set_pagelayout('admin');
    $mform = new uploaduser_form();
    if (empty($iid)) {
        if ($mform->is_cancelled()) {
            redirect($CFG->wwwroot . '/local/point_system/courserulemanager.php', 'Form has been cancelled');
        } else if ($data = $mform->get_data()) {
            $iid = csv_import_reader::get_new_iid('uploaduser');
            $cir = new csv_import_reader($iid, 'uploaduser');
            $content = $mform->get_file_content('userfile');

            $readcount = $cir->load_csv_content($content, $formdata->encoding, $formdata->delimiter_name);
            $csvloaderror = $cir->get_error();
            unset($content);

            if (!is_null($csvloaderror)) {
                throw new \moodle_exception('csvloaderror', '', $returnurl, $csvloaderror);
            }
        } else {
            echo $OUTPUT->header();
            $mform->display();
            echo $OUTPUT->footer();
            die;
        }
    } else {
        $cir = new csv_import_reader($iid, 'uploaduser');
    }
    $process = new \tool_uploaduser\process($cir);
    $filecolumns = $process->get_file_columns();
    if (!in_array('email', $filecolumns)) {
        throw new \moodle_exception('fieldrequired', '', $returnurl, get_string('email'));
    }
    if (!in_array('firstname', $filecolumns)) {
        throw new \moodle_exception('fieldrequired', '', $returnurl, get_string('firstname'));
    }
    if (!in_array('lastname', $filecolumns)) {
        throw new \moodle_exception('fieldrequired', '', $returnurl, get_string('lastname'));
    }
    //Second Form
    $mform2 = new uploaduser_form2(
        null,
        ['columns' => $filecolumns, 'data' => ['iid' => $iid, 'previewrows' => $previewrows]]
    );
    // If a file has been uploaded, then process it.
    if ($formdata = $mform2->is_cancelled()) {
        $cir->cleanup(true);
        redirect($returnurl);
    } else if ($formdata = $mform2->get_data()) {
        global $DB;
        $cir->init();
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('uploadusersresult', 'tool_uploaduser'));
        $linenum = 1; // Column header is first line.
        while ($linenum <= $previewrows and $fields = $cir->next()) {
            $linenum++;
            $csvfields = array();
            $csvfields['line'] = $linenum;
            foreach ($fields as $key => $field) {
                $csvfields[$filecolumns[$key]] = s(trim($field));
            }
            if ($csvfields['firstname'] && $csvfields['lastname'] && $csvfields['email']) {
                $firstname = $csvfields['firstname'];
                $lastname = $csvfields['lastname'];
                $email = $csvfields['email'];
                $emailsendertask = new \local_emailsender\task\emailsender();
                $emailsendertask->set_custom_data(array(
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'email' => $email,
                ));
                //Queue the task for the next run.
                \core\task\manager::queue_adhoc_task($emailsendertask);
            } else {
                continue; //Skip that user
            }
        }
        $message = get_string('scheduledmail', 'local_emailsender');
        \core\notification::add($message, \core\output\notification::NOTIFY_INFO);
        echo $OUTPUT->continue_button($returnurl);
        echo $OUTPUT->footer();
        die;
    }
    // Print the header.
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('uploaduserspreview', 'tool_uploaduser'));
    // Preview table data.
    $table = new \local_emailsender\table_preview($cir, $filecolumns, $previewrows);
    echo html_writer::table($table);
    // Print the form if valid values are available.
    if ($table->get_no_error()) {
        $mform2->display();
    }
    echo $OUTPUT->footer();
    die;
} else {
    throw new \moodle_exception('nopermissiontoaccesspage', '', $CFG->wwwroot);
}
