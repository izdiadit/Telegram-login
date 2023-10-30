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

/**
 * Telegram authentication plugin.

 * @package    auth_telegram
 * @copyright  2023 Mortada ELgaily <mortada.elgaily@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . "/formslib.php");
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/authlib.php');

use core\output\notification;

/**
 * Phone OTP authentication plugin.
 *
 * @see self::user_login()
 * @see self::get_user_field()
 * @package    auth_otp
 * @copyright  2021 Brain Station 23 ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class auth_plugin_telegram extends auth_plugin_base
{
    /**
     * The name of the component. Used by the configuration.
     */
    const COMPONENT_NAME = 'auth_telegram';

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->authtype = 'telegram';
        $this->config = get_config(self::COMPONENT_NAME);
    }

    /**
     * Hook for overriding behaviour of login page.
     *  */
    function loginpage_hook()
    {
        global $PAGE, $CFG;
        echo "
        <script type='text/javascript'>
             var botusername = " . json_encode(get_config('auth_telegram', 'botusername')) . ";
        </script>";
        $PAGE->requires->jquery();
        $PAGE->requires->js_init_code("buttonsAddMethod = 'auto';");
        $content = str_replace(array("\n", "\r"), array("\\\n", "\\\r",), $this->get_buttons_string());
        $PAGE->requires->js_init_code("buttonsCode = '$content';");
        $PAGE->requires->js(new moodle_url($CFG->wwwroot . "/auth/telegram/script.js"));
        
    }

    /**
     * @return string
     */
    private function get_buttons_string()
    {
        global $CFG;

        $content = '
                    <div id="telegram-login-container" class="btn  btn-block mt-3" >
                    <!-- The Telegram login widget will be appended here by the JavaScript file -->
                </div>';

        return $content;
    }


    
}