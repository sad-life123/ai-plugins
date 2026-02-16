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

namespace aiplacement_textprocessor;

use core_ai\placement as base_placement;

/**
 * Class placement.
 *
 * @package    aiplacement_textprocessor
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class placement extends base_placement {

    /**
     * Get the actions that this placement supports.
     *
     * @return array An array of action class names.
     */
    #[\Override]
    public static function get_action_list(): array {
        return [
            \core_ai\aiactions\generate_text::class,
        ];
    }

    /**
     * Get placement name.
     *
     * @return string
     */
    #[\Override]
    public static function get_name(): string {
        return 'textprocessor';
    }
}
