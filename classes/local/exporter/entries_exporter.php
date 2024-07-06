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

use file_serving_exception;
use moodle_exception;

/**
 * Exporter class for exporting data.
 *
 * @package    local_expnotas
 * @copyright  2023 ISB Bayern
 * @author     Philipp Memmel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class entries_exporter {

    /** @var int Tracks the currently edited row of the export data file. */
    protected int $currentrow;

    /**
     * @var array The data structure containing the data for exporting. It's a 2-dimensional array of
     *  rows and columns.
     */
    protected array $exportdata;

    /** @var string Name of the export file name without extension. */
    protected string $exportfilename;

    /** @var string Name of the export file path. */
    protected string $exportfilepath;

    protected int $itemid;

     /**
      * Helper to get the filearea
      *
      * @return array contextid, filearea, itemid are the keys.
      */
    public function get_base_filearea() {
        return [
            'contextid' => SYSCONTEXTID,
            'component' => 'expnotas',
            'filearea'  => 'destination',
        ];
    }

    private function new_file_record_base() {
        return (object)array_merge($this->get_base_filearea(), [
            'itemid' => $this->itemid,
            'filepath' => $this->exportfilepath,
            'filename' => $this->exportfilename,
        ]);
    }

    /**
     * Creates an entries_exporter object.
     *
     * This object can be used to export data to different formats including files.
     */
    public function __construct() {
        $this->currentrow = 0;
        $this->exportdata = [];
        $this->exportfilename = 'Exportfile';
        $this->exportfilepath = '';
        $this->itemid = 0;
    }

    /**
     * Adds a row (array of strings) to the export data.
     *
     * @param array $row the row to add, $row has to be a plain array of strings
     * @return void
     */
    public function add_row(array $row): void {
        $this->exportdata[] = $row;
        $this->currentrow++;
    }

    /**
     * Adds a data string (so the content for a "cell") to the current row.
     *
     * @param string $cellcontent the content to add to the current row
     * @return void
     */
    public function add_to_current_row(string $cellcontent): void {
        $this->exportdata[$this->currentrow][] = $cellcontent;
    }

    /**
     * Signal the entries_exporter to finish the current row and jump to the next row.
     *
     * @return void
     */
    public function next_row(): void {
        $this->currentrow++;
    }

    /**
     * Sets the name of the export file.
     *
     * Only use the basename without path and without extension here.
     *
     * @param string $exportfilename name of the file without path and extension
     * @return void
     */
    public function set_export_filename(string $exportfilename): void {
        $this->exportfilename = $exportfilename;
    }

    /**
     * Sets the file path.
     *
     * @param string $exportfilename name of the file path
     * @return void
     */
    public function set_export_filepath(string $exportfilepath): void {
        $this->exportfilepath = $exportfilepath;
    }
    /**
     * Sets the item id, needed for saving the file
     *
     * @param string $itemd id id of the item
     * @return void
     */
    public function set_export_itemid(string $itemid): void {
        $this->itemid = $itemid;
    }

    /**
     * The entries_exporter will prepare a data file from the rows and columns being added.
     * Overwrite this method to generate the data file as string.
     *
     * @return string the data file as a string
     */
    abstract protected function get_data_file_content(): string;

    /**
     * Overwrite the method to return the file extension your data file will have, for example
     * <code>return 'csv';</code> for a csv file entries_exporter.
     *
     * @return string the file extension of the data file your entries_exporter is using
     */
    abstract protected function get_export_data_file_extension(): string;

    /**
     * Sends the generated export file.
     *
     * Care: By default this function finishes the current PHP request and directly serves the file to the user as download.
     *
     * @return stored_file
     */
    public function send_file(bool $sendtouser = false) {
        if ($sendtouser) {
            send_file($this->get_data_file_content(),
                $this->exportfilename . '.' . $this->get_export_data_file_extension(),
                null, 0, true, true);
            return null;
        } else {
            $fs = get_file_storage();
            $filerecord = $this->new_file_record_base($this->exportfilename, $this->exportfilepath);
            return $fs->create_file_from_string($filerecord, $this->get_data_file_content());
        }
    }
}
