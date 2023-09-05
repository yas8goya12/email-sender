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
namespace local_emailsender\task;

use core_user;
use stdClass;

defined('MOODLE_INTERNAL') || die();
class emailsender extends  \core\task\adhoc_task
{

    /**
     * Run the deletion task.
     *
     * @throws \coding_exception if the module could not be removed.
     */
    public function execute()
    {
        $firstname = $this->get_custom_data()->firstname;
        $lastname = $this->get_custom_data()->lastname;
        $email = $this->get_custom_data()->email;
        $user = core_user::get_user_by_email($email);
        if(empty($user))
        {
            $user = new stdClass();
            $user->username = $email;
            $user->firstname = $firstname;
            $user->lastname = $lastname;
            $user->email = $email;
            $user->password = "Dummypassword@123";
            $user->mnethostid = 1;
            user_create_user($user, true);
            $reciepent = core_user::get_user_by_email($email);
        }else{
            $reciepent = $user;
        }
        $from = core_user::get_noreply_user(); //Noreply user
        $subject = "Test email";
        $message = get_string('sampletext', "local_emailsender");
        $emailstatus = email_to_user($reciepent, $from, $subject, $message);
        $dataobject = new stdClass();
        $dataobject->firstname = $reciepent->firstname;
        $dataobject->lastname = $reciepent->lastname;
        $dataobject->email = $reciepent->email;
        $dataobject->timecreated = time();
        global $DB;

        if ($emailstatus) {
            $dataobject->status = 1;
            $DB->insert_record('emaillogs', $dataobject);
        } else {
            $dataobject->status = 0;
            $DB->insert_record('emaillogs', $dataobject);
        }
    }
}
