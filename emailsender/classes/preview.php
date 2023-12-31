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

namespace local_emailsender;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/csvlib.class.php');

class table_preview extends \html_table {

    /** @var \csv_import_reader  */
    protected $cir;
    /** @var array */
    protected $filecolumns;
    /** @var int */
    protected $previewrows;
    /** @var bool */
    protected $noerror = true; // Keep status of any error.

    /**
     * preview constructor.
     *
     * @param \csv_import_reader $cir
     * @param array $filecolumns
     * @param int $previewrows
     * @throws \coding_exception
     */
    public function __construct(\csv_import_reader $cir, array $filecolumns, int $previewrows) {
        parent::__construct();
        $this->cir = $cir;
        $this->filecolumns = $filecolumns;
        $this->previewrows = $previewrows;

        $this->id = "uupreview";
        $this->attributes['class'] = 'generaltable';
        $this->head = array();
        $this->data = $this->read_data();
        $this->head[] = get_string('csvline', 'local_emailsender');
        foreach ($filecolumns as $column) {
            $this->head[] = $column;
        }
        $this->head[] = get_string('status');

    }

    /**
     * Read data
     *
     * @return array
     */
    protected function read_data() {
        $data = array();
        $this->cir->init();
        $linenum = 1; // Column header is first line.
        while ($linenum <= $this->previewrows and $fields = $this->cir->next()) {
            $linenum++;
            $rowcols = array();
            $rowcols['line'] = $linenum;
            foreach ($fields as $key => $field) {
                $rowcols[$this->filecolumns[$key]] = s(trim($field));
            }
            $rowcols['status'] = array();

            if (isset($rowcols['email'])) {
                if (!validate_email($rowcols['email'])) {
                    $rowcols['status'][] = get_string('invalidemail');
                }
            }else{
                $rowcols['status'][] = get_string('missingfield', 'local_emailsender','Email');
            }
            if(empty($rowcols['firstname']))
            {
                $rowcols['status'][] = get_string('missingfield', 'local_emailsender', 'Firstname');
            }
            if(empty($rowcols['lastname']))
            {
                $rowcols['status'][] = get_string('missingfield', 'local_emailsender', 'Lastname');
            }
            $rowcols['status'] = implode('<br />', $rowcols['status']);
            $data[] = $rowcols;
        }
        if ($fields = $this->cir->next()) {
            $data[] = array_fill(0, count($fields) + 2, '...');
        }
        $this->cir->close();

        return $data;
    }

    /**
     * Getter for noerror
     *
     * @return bool
     */
    public function get_no_error() {
        return $this->noerror;
    }
}
