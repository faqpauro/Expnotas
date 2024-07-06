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
use zip_archive;

class zip_exporter {

    /** @var string Name of the export file name without extension. */
    protected string $exportfilename;

    /** @var zip_archive The zip archive object we store all the files in, if we need to export files as well. */
    private zip_archive $ziparchive;

    /** @var bool Tracks the state if the zip archive already has been closed. */
    private bool $isziparchiveclosed;

    /** @var string full path of the zip archive. */
    private string $zipfilepath;

    /** @var array Array to store all filenames in the zip archive for export. */
    private array $filenamesinzip;

    /**
     * Creates a zip archive to bundle different files in.
     */
    public function __construct() {
        $this->exportfilename = 'Exportfile';
        $this->filenamesinzip = [];
        $this->isziparchiveclosed = true;
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
     * Use this method to add a file which should be exported to the entries_exporter.
     *
     * @param string $filename the name of the file which should be added
     * @param string $filecontent the content of the file as a string
     * @param string $zipsubdir the subdirectory in the zip archive. Defaults to 'files/'.
     * @return void
     * @throws moodle_exception if there is an error adding the file to the zip archive
     */
    public function add_file_from_string(string $filename, string $filecontent, string $zipsubdir = 'files/'): void {
        if (empty($this->filenamesinzip)) {
            // No files added yet, so we need to create a zip archive.
            $this->create_zip_archive();
        }
        if (!str_ends_with($zipsubdir, '/')) {
            $zipsubdir .= '/';
        }
        $zipfilename = $zipsubdir . $filename;
        $this->filenamesinzip[] = $zipfilename;
        $this->ziparchive->add_file_from_string($zipfilename, $filecontent);
    }

    /**
     * Sends the generated export file.
     *
     * Care: By default this function finishes the current PHP request and directly serves the file to the user as download.
     *
     * @param bool $sendtouser true if the file should be sent directly to the user, if false the file content will be returned
     *  as string
     * @return string|null file content as string if $sendtouser is true
     * @throws moodle_exception if there is an issue adding the data file
     * @throws file_serving_exception if the file could not be served properly
     */
    public function send_file(bool $sendtouser = true): null|string {
        $this->finish_zip_archive();

        if ($this->isziparchiveclosed) {
            if ($sendtouser) {
                send_file($this->zipfilepath, $this->exportfilename . '.zip', null, 0, false, true);
                return null;
            } else {
                return file_get_contents($this->zipfilepath);
            }
        } else {
            throw new file_serving_exception('Could not serve zip file, it could not be closed properly.');
        }
    }

    /**
     * Prepares the zip archive.
     *
     * @return void
     */
    private function create_zip_archive(): void {
        $tmpdir = make_request_directory();
        $this->zipfilepath = $tmpdir . '/' . $this->exportfilename . '.zip';
        $this->ziparchive = new zip_archive();
        $this->isziparchiveclosed = !$this->ziparchive->open($this->zipfilepath);
    }

    /**
     * Closes the zip archive.
     *
     * @return void
     */
    private function finish_zip_archive(): void {
        if (!$this->isziparchiveclosed) {
            $this->isziparchiveclosed = $this->ziparchive->close();
        }
    }





}

