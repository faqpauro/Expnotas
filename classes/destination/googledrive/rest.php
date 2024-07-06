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

namespace local_expnotas\destination\googledrive;

defined('MOODLE_INTERNAL') || die();

class rest extends \core\oauth2\rest {

    /**
     * Define the functions of the rest API.
     *
     * @return array Example:
     *  [ 'listFiles' => [ 'method' => 'get', 'endpoint' => 'http://...', 'args' => [ 'folder' => PARAM_STRING ] ] ]
     */
    public function get_api_functions() {
        return [
            'create' => [
                'endpoint' => 'https://www.googleapis.com/drive/v3/files',
                'method' => 'post',
                'args' => [
                    'fields' => PARAM_RAW,
                ],
                'response' => 'json',
            ],
            'upload_metadata' => [
                'endpoint' => 'https://www.googleapis.com/upload/drive/v3/files',
                'method' => 'post',
                'args' => [
                    'uploadType' => PARAM_RAW,
                    'fields' => PARAM_RAW,
                ],
                'response' => 'headers',
            ],
            'upload_content' => [
                'endpoint' => 'https://www.googleapis.com/upload/drive/v3/files',
                'method' => 'put',
                'args' => [
                    'uploadType' => PARAM_RAW,
                    'upload_id' => PARAM_RAW,
                ],
                'response' => 'json',
            ],
            'update' => [
                'endpoint' => 'https://www.googleapis.com/drive/v3/files/{fileid}',
                'method' => 'patch',
                'args' => [
                    'fileid' => PARAM_RAW,
                    'addParents' => PARAM_RAW,
                    'removeParents' => PARAM_RAW,
                ],
                'response' => 'json',
            ],
            'update_content' => [
                'endpoint' => 'https://www.googleapis.com/upload/drive/v3/files/{fileid}',
                'method' => 'patch',
                'args' => [
                    'fileId' => PARAM_RAW,
                    'uploadType' => PARAM_RAW,
                ],
                'response' => 'json',
            ],
            'search' => [
                'endpoint' => 'https://www.googleapis.com/drive/v3/files',
                'method' => 'get',
                'args' => [
                    'q' => PARAM_RAW,
                ],
                'response' => 'json',
            ],
        ];
    }
}
