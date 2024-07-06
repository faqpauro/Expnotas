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

namespace local_expnotas;

abstract class destination {

    /**
     * Lists all the available destinations
     * @return array
     */
    public static function get_destinations() {
        return [
            'googledrive' => get_string('googledrive', 'local_expnotas'),
        ];
    }

    /**
     * Lists all the available destinations, plus a download option
     * @return array
     */
    public static function get_form_destinations() {
        return array_merge(self::get_destinations(),
            ['download' => get_string('download', 'local_expnotas')]
            );
    }

    /**
     * The data gets sent
     */
    abstract protected function send();
}

