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

abstract class multisheet_entries_exporter extends entries_exporter {


    /** @var int Tracks the currently edited worksheet of the export data file. */
    private int $currentworksheet;

    /**
     * @var array The data structure containing the data for the current worksheet.
     */
    protected array $worksheet;

    /**
     * Creates an entries_exporter object.
     *
     * This object can be used to export data to different formats including files.
     */
    public function __construct() {
        parent::__construct();
        $this->currentworksheet = 0;
        $this->worksheet = [];
    }

    /**
     * Adds a row (array of strings) to the current worksheet.
     *
     * @param array $row the row to add, $row has to be a plain array of strings
     * @return void
     */
    public function add_row(array $row): void {
        $this->worksheet[] = $row;
        $this->currentrow++;
    }

    /**
     * Adds a worksheet to the data structure
     */
    public function add_worksheet(string $name) {
        if (!isset($this->exportdata[$name])) {
            $this->exportdata[$name] = $this->worksheet;
            $this->current_row = 0;
            $this->worksheet = [];
            $this->currentworksheet++;
        } else {
            // TODO: Exception handling
            // 'There already exists a worksheet with the given name.';
        }

    }



}
