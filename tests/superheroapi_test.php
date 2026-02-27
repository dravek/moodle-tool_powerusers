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

declare(strict_types=1);

namespace tool_powerusers;

use advanced_testcase;
use stdClass;

/**
 * Unit tests for superheroapi mapper.
 *
 * @package    tool_powerusers
 * @covers     \tool_powerusers\superheroapi
 * @copyright  2026 David Carrillo <davidmc@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class superheroapi_test extends advanced_testcase {
    /**
     * Test get_user_data maps object payload fields.
     */
    public function test_get_user_data_maps_object_payload(): void {
        $data = new stdClass();
        $data->name = 'Spider Man';
        $data->biography = (object) [
            'full-name' => 'Peter Parker',
            'publisher' => 'Marvel Comics',
            'first-appearance' => 'Amazing Fantasy #15',
        ];
        $data->image = (object) [
            'url' => 'https://example.test/spiderman.jpg',
        ];

        $result = superheroapi::get_user_data($data);

        $this->assertSame('spiderman', $result['username']);
        $this->assertSame('Spider', $result['firstname']);
        $this->assertSame('Man ', $result['lastname']);
        $this->assertSame('https://example.test/spiderman.jpg', $result['urlpicture']);
        $this->assertSame(
            'Full name: Peter Parker | Publisher: Marvel Comics | First appearance: Amazing Fantasy #15',
            $result['description']
        );
    }

    /**
     * Test get_user_data maps array payload fields.
     */
    public function test_get_user_data_maps_array_payload(): void {
        $data = new stdClass();
        $data->name = 'Batman';
        $data->biography = [
            'full-name' => 'Bruce Wayne',
            'publisher' => 'DC Comics',
            'first-appearance' => 'Detective Comics #27',
        ];
        $data->image = [
            'url' => 'https://example.test/batman.jpg',
        ];

        $result = superheroapi::get_user_data($data);

        $this->assertSame('batman', $result['username']);
        $this->assertSame('Batman', $result['firstname']);
        $this->assertSame(' ', $result['lastname']);
        $this->assertSame('https://example.test/batman.jpg', $result['urlpicture']);
        $this->assertSame(
            'Full name: Bruce Wayne | Publisher: DC Comics | First appearance: Detective Comics #27',
            $result['description']
        );
    }
}
