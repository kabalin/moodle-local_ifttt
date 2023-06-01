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

namespace local_ifttt\tool_dynamicrule\outcome;

use core\message\message;
use tool_dynamicrule\rule;
use tool_organisation\organisation;

/**
 * IFTTT Outcome
 *
 * @package   local_ifttt
 * @author    Ruslan Kabalin <ruslan.kabalin@gmail.com>
 * @copyright 2023 Moodle Pty Ltd <support@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ifttt extends \tool_dynamicrule\outcome_base {

    /** @var curl */
    private $curl;
    /** @var moodle_url */
    private $webhookurl;

    /**
     * Returns the title of the outcome
     *
     * @return string The title as formated string
     */
    public function get_title(): string {
        return get_string('iftttwebhook', 'local_ifttt');
    }

    /**
     * Returns string for outcome category.
     *
     * @return string
     */
    public function get_category(): string {
        return get_string('external', 'local_ifttt');
    }

    /**
     * If the current user is able to use this outcome.
     *
     * @return bool
     */
    public static function is_available(): bool {
        return !empty(get_config('local_ifttt', 'webhookskey'));
    }

    /**
     * Outcome not available label.
     *
     * @return string
     */
    public function get_not_available_label(): string {
        return get_string('pluginisnotconfigured', 'local_ifttt');
    }

    /**
     * Adds outcome's elements to the given mform
     *
     * @param \MoodleQuickForm $mform The form to add elements to
     */
    public function get_config_form(\MoodleQuickForm $mform) {
        global $OUTPUT;
        $mform->addElement('text', 'markereventname', get_string('markereventname', 'local_ifttt'));
        $mform->addRule('markereventname', null, 'required', null, 'client');
        $mform->setType('markereventname', PARAM_TEXT);
        $mform->addHelpButton('markereventname', 'markereventname', 'local_ifttt');

        $group = [];
        $group[] = $mform->createElement('text', 'eventvalue1', '', ['placeholder' => 'value1']);
        $group[] = $mform->createElement('text', 'eventvalue2', '', ['placeholder' => 'value2']);
        $group[] = $mform->createElement('text', 'eventvalue3', '', ['placeholder' => 'value3']);
        $mform->addGroup($group, 'eventvaluesgroup', get_string('eventvalues', 'local_ifttt'), \html_writer::div('', 'w-100 mt-2'), false);
        $mform->setType('eventvalue', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('eventvaluesgroup', 'eventvalues', 'local_ifttt');
    }

    /**
     * Validates the configform of the outcome
     *
     * @param array $data Data from the form
     * @return array Array with errors for each element
     */
    public function validate_config_form(array $data): array {
        $errors = [];
        if (empty($data['markereventname'])) {
            $errors['markereventname'] = get_string('errorinvalidmarkereventname', 'local_ifttt');
        }
        return $errors;
    }

    /**
     * Helper function called before outcome is applied to user.
     */
    public function setup_for_applying(): void {
        $curl = new \curl();
        $curl->setopt(array('CURLOPT_TIMEOUT' => 3, 'CURLOPT_CONNECTTIMEOUT' => 3));
        $curl->setHeader(['Cache-Control: no-cache', 'Content-Type: application/json']);
        $this->curl = $curl;

        $eventname = $this->get_configdata()['markereventname'];
        $key = get_config('local_ifttt', 'webhookskey');
        $url = "https://maker.ifttt.com/trigger/{$eventname}/with/key/{$key}";
        $this->webhookurl = new \moodle_url($url);
    }

    /**
     * Apply this outcome to a given user.
     *
     * @param \stdClass $user The user object to apply the outcome to
     */
    public function apply_to_user(\stdClass $user): void {
        $values = [
            'value1' => $this->get_configdata()['eventvalue1'],
            'value2' => $this->get_configdata()['eventvalue2'],
            'value3' => $this->get_configdata()['eventvalue3'],
        ];

        // Trigger event.
        $response = $this->curl->post($this->webhookurl, json_encode($values));

        // See if we succeded.
        $info = $this->curl->get_info();
        if ($curlerrno = $this->curl->get_errno()) {
            $debug = "Unexpected response, CURL error number: {$curlerrno} Error: {$curl->error}";
            throw new \moodle_exception('webhookcallerror', 'local_ifttt', '', null, $debug);
        } else if ((int)$info['http_code'] >= 400) {
            $debug = "Unexpected response, HTTP code {$info['http_code']}, Response: {$response}";
            throw new \moodle_exception('webhookcallerror', 'local_ifttt', '', null, $debug);
        }
    }

    /**
     * Return the description for the outcome.
     *
     * @return string
     */
    public function get_description(): string {
        $options = ['context' => \context_system::instance(), 'escape' => false];
        return get_string('outcomeiftttdescription', 'local_ifttt', format_string($this->get_configdata()['markereventname'], true, $options));
    }

    /**
     * Check if configuration is valid.
     *
     * @return bool
     */
    public function is_configuration_valid(): bool {
        return !(empty($this->get_configdata()['markereventname']) || empty(get_config('local_ifttt', 'webhookskey')));
    }

    /**
     * If the current user is able to add this outcome.
     *
     * @return bool
     */
    public function user_can_add(): bool {
        return true;
    }

    /**
     * If the current user is able to edit this outcome.
     *
     * @param array $configdata
     * @return bool
     */
    public function user_can_edit(array $configdata): bool {
        return true;
    }

    /**
     * Which rule types this outcome supports.
     *
     * @return int Rule types bitwise added.
     */
    public function supports_rule_types(): int {
        return rule::TYPE_NORMAL + rule::TYPE_SHARED;
    }
}
