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
 * Unit tests for generator.
 *
 * @package    tool_powerusers
 * @covers     \tool_powerusers\generator
 * @copyright  2026 David Carrillo <davidmc@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class generator_test extends advanced_testcase {
    /**
     * Test generate_users returns created names in the 4th response element.
     */
    public function test_generate_users_returns_names_on_success(): void {
        $generator = $this->build_generator(
            [
                [
                    'status' => constants::OK,
                    'results' => [
                        ['name' => 'Spider Man'],
                        ['name' => 'Bruce Banner'],
                    ],
                ],
            ],
            [true, true],
            ['Spider Man', 'Bruce Banner']
        );

        $result = $generator->generate_users($this->manual_data('Spider', constants::SEARCH_STARTS_WITH));

        $this->assertTrue($result[0]);
        $this->assertSame(2, $result[1]);
        $this->assertSame('', $result[2]);
        $this->assertSame(['Spider Man', 'Bruce Banner'], $result[3]);
    }

    /**
     * Test manual generation with matches but no creations returns erroruserexists.
     */
    public function test_generate_users_manual_zero_created_returns_erroruserexists(): void {
        $generator = $this->build_generator(
            [
                [
                    'status' => constants::OK,
                    'results' => [
                        ['name' => 'Spider Man'],
                    ],
                ],
            ],
            [false],
            ['Spider Man']
        );

        $result = $generator->generate_users($this->manual_data('Spider', constants::SEARCH_STARTS_WITH));

        $this->assertFalse($result[0]);
        $this->assertSame(0, $result[1]);
        $this->assertSame(get_string('erroruserexists', 'tool_powerusers'), $result[2]);
        $this->assertSame([], $result[3]);
    }

    /**
     * Test random generation with zero requested users returns errornousers.
     */
    public function test_generate_users_random_zero_quantity_returns_errornousers(): void {
        $generator = $this->build_generator([], [], ['Spider Man']);

        $result = $generator->generate_users($this->random_data(0));

        $this->assertFalse($result[0]);
        $this->assertSame(0, $result[1]);
        $this->assertSame(get_string('errornousers', 'tool_powerusers'), $result[2]);
        $this->assertSame([], $result[3]);
    }

    /**
     * Test random generation with found results but no creations returns erroruserexists.
     */
    public function test_generate_users_random_zero_created_returns_erroruserexists(): void {
        $generator = $this->build_generator(
            [
                [
                    'status' => constants::OK,
                    'results' => [
                        ['name' => 'Tony Stark'],
                    ],
                ],
            ],
            [false],
            ['Tony Stark']
        );

        $result = $generator->generate_users($this->random_data(1));

        $this->assertFalse($result[0]);
        $this->assertSame(0, $result[1]);
        $this->assertSame(get_string('erroruserexists', 'tool_powerusers'), $result[2]);
        $this->assertSame([], $result[3]);
    }

    /**
     * Test random generation returns errormsg when names list cannot be loaded.
     */
    public function test_generate_users_random_names_list_load_failure_returns_errormsg(): void {
        $generator = $this->build_generator([], [], null);

        $result = $generator->generate_users($this->random_data(1));

        $this->assertFalse($result[0]);
        $this->assertSame(0, $result[1]);
        $this->assertSame(get_string('errormsg', 'tool_powerusers'), $result[2]);
        $this->assertSame([], $result[3]);
    }

    /**
     * Test manual generation with empty name returns errornoname.
     */
    public function test_generate_users_manual_empty_name_returns_errornoname(): void {
        $generator = $this->build_generator([], [], []);

        $result = $generator->generate_users($this->manual_data('', constants::SEARCH_STARTS_WITH));

        $this->assertFalse($result[0]);
        $this->assertSame(0, $result[1]);
        $this->assertSame(get_string('errornoname', 'tool_powerusers'), $result[2]);
    }

    /**
     * Test manual generation with API error returns API message.
     */
    public function test_generate_users_manual_api_error_returns_message(): void {
        $generator = $this->build_generator(
            [
                [
                    'status' => constants::ERROR,
                    'results' => ['message' => 'API Down'],
                ],
            ],
            [],
            []
        );

        $result = $generator->generate_users($this->manual_data('Spider', constants::SEARCH_STARTS_WITH));

        $this->assertFalse($result[0]);
        $this->assertSame('API Down', $result[2]);
    }

    /**
     * Test manual generation with empty results returns errornousers.
     */
    public function test_generate_users_manual_empty_results_returns_errornousers(): void {
        $generator = $this->build_generator(
            [
                [
                    'status' => constants::OK,
                    'results' => [],
                ],
            ],
            [],
            []
        );

        $result = $generator->generate_users($this->manual_data('Spider', constants::SEARCH_STARTS_WITH));

        $this->assertFalse($result[0]);
        $this->assertSame(get_string('errornousers', 'tool_powerusers'), $result[2]);
    }

    /**
     * Test random generation with empty name list returns errornousers.
     */
    public function test_generate_users_random_empty_names_list_returns_errornousers(): void {
        $generator = $this->build_generator([], [], []);

        $result = $generator->generate_users($this->random_data(1));

        $this->assertFalse($result[0]);
        $this->assertSame(0, $result[1]);
        $this->assertSame(get_string('errornousers', 'tool_powerusers'), $result[2]);
    }

    /**
     * Test random generation reaching max attempts.
     */
    public function test_generate_users_random_max_attempts(): void {
        // Mock API always returning error to force reaching max attempts.
        $generator = $this->build_generator([], [], ['Iron Man']);

        $result = $generator->generate_users($this->random_data(5));

        $this->assertFalse($result[0]);
        $this->assertSame(0, $result[1]);
        $this->assertSame(get_string('errornousers', 'tool_powerusers'), $result[2]);
    }

    /**
     * Test generate_users with random password.
     */
    public function test_generate_users_random_password(): void {
        $generator = $this->build_generator(
            [
                [
                    'status' => constants::OK,
                    'results' => [['name' => 'Spider Man']],
                ],
            ],
            [true],
            []
        );

        $data = $this->manual_data('Spider', constants::SEARCH_STARTS_WITH);
        $data->randompassword = 1;

        $result = $generator->generate_users($data);

        $this->assertTrue($result[0]);
        $this->assertSame(1, $result[1]);
    }

    /**
     * Build manual generator request data.
     *
     * @param string $name
     * @param string $searchaccuracy
     * @return stdClass
     */
    private function manual_data(string $name, string $searchaccuracy): stdClass {
        $data = new stdClass();
        $data->type = constants::MANUAL;
        $data->name = $name;
        $data->searchaccuracy = $searchaccuracy;
        $data->randompassword = 0;
        $data->password = 'Password123!';

        return $data;
    }

    /**
     * Build random generator request data.
     *
     * @param int $quantity
     * @return stdClass
     */
    private function random_data(int $quantity): stdClass {
        $data = new stdClass();
        $data->type = constants::RANDOM;
        $data->quantity = $quantity;
        $data->randompassword = 0;
        $data->password = 'Password123!';

        return $data;
    }

    /**
     * Build a generator test double without network or DB side effects.
     *
     * @param array[] $apiresponses queued API responses.
     * @param bool[] $createuserresults queued create-user outcomes.
     * @param array|null $randomnames names list for random generation.
     * @return generator
     */
    private function build_generator(array $apiresponses, array $createuserresults, ?array $randomnames): generator {
        return new class ($apiresponses, $createuserresults, $randomnames) extends generator {
            /** @var array[] */
            private array $apiresponses;

            /** @var bool[] */
            private array $createuserresults;

            /** @var array|null */
            private ?array $randomnames;

            /**
             * Construct the test double with deterministic queues.
             *
             * @param array[] $apiresponses
             * @param bool[] $createuserresults
             * @param array|null $randomnames
             */
            public function __construct(array $apiresponses, array $createuserresults, ?array $randomnames) {
                $this->apiresponses = $apiresponses;
                $this->createuserresults = $createuserresults;
                $this->randomnames = $randomnames;
            }

            /**
             * Return the next queued API response.
             *
             * @param string $name
             * @param string $type
             * @return array
             */
            protected function get_users_from_api(string $name, string $type): array {
                if (!empty($this->apiresponses)) {
                    return array_shift($this->apiresponses);
                }

                return ['status' => constants::ERROR, 'results' => []];
            }

            /**
             * Convert mock payload into generator user structure.
             *
             * @param stdClass $data
             * @return array
             */
            protected function map_user_data(stdClass $data): array {
                $name = trim((string) ($data->name ?? ''));
                [$firstname, $lastname] = explode(' ', $name . ' ', 2);

                return [
                    'username' => strtolower(str_replace(' ', '', $name)),
                    'firstname' => $firstname,
                    'lastname' => trim($lastname) === '' ? ' ' : $lastname,
                    'urlpicture' => '',
                    'description' => '',
                ];
            }

            /**
             * Return the next queued create-user outcome.
             *
             * @param array $record
             * @return bool
             */
            protected function create_user(array $record): bool {
                if (!empty($this->createuserresults)) {
                    return (bool) array_shift($this->createuserresults);
                }

                return false;
            }

            /**
             * Return configured random names list.
             *
             * @return array|null
             */
            protected function load_random_names(): ?array {
                return $this->randomnames;
            }
        };
    }
}
