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

namespace local_expnotas\local\exporter;

use MoodleODSWorkbook;
use MoodleODSWriter;

class ods_entries_exporter extends multisheet_entries_exporter {
    private const FILEARG = '-';

    /**
     * Returns the file extension of this entries exporter.
     *
     * @see \mod_data\local\exporter\entries_exporter::get_export_data_file_extension()
     */
    public function get_export_data_file_extension(): string {
        return 'ods';
    }


    /**
     * Sets the name of the export file.
     *
     * Only use the basename without path and without extension here.
     *
     * @param string $exportfilename name of the file without path and extension
     * @return void
     */
    public function set_export_file_name(string $exportfilename): void {
        $this->exportfilename = $exportfilename;
    }

    /**
     * Returns the ods data exported by the ODS library for further handling.
     *
     * @see \mod_data\local\exporter\entries_exporter::get_data_file_content()
     */
    public function get_data_file_content(): string {
        global $CFG;
        require_once("$CFG->libdir/odslib.class.php");
        $workbook = new MoodleODSWorkbook(self::FILEARG);
        $filedata = [];
        $workno = 0;
        foreach ($this->exportdata as $worksheetname => $worksheetdata) {
            $rowno = 0;
            $filedata[] = $workbook->add_worksheet($worksheetname);
            foreach ($worksheetdata as $row) {
                $colno = 0;
                foreach ($row as $col) {
                    $filedata[$workno]->write($rowno, $colno, $col);
                    $colno++;
                }
                $rowno++;
            }
            $workno++;
        }
        $writer = new MoodleODSWriter($filedata);
        return $writer->get_file_content();
    }
}
