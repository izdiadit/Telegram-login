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
 * TODO describe file telegram_auth
 * @package    auth_telegram
 * @copyright  2023 Mortada ELgaily <mortada.elgaily@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');


// Place bot token of your bot here.
define('BOT_TOKEN', get_config('auth_telegram', 'bottoken'));
// The Telegram hash is required to authorize.
if (!isset($_GET['hash'])) {
    die('Telegram hash not found');
}


// Official Telegram authorization - function.
function check_telegram_authorization($authdata) {
    $checkhash = $authdata['hash'];
    unset($authdata['hash']);
    $datacheckarr = [];
    foreach ($authdata as $key => $value) {
        $datacheckarr[] = $key . '=' . $value;
    }
    sort($datacheckarr);
    $datacheckstring = implode("\n", $datacheckarr);
    $secretkey = hash('sha256', BOT_TOKEN, true);
    $hash = hash_hmac('sha256', $datacheckstring, $secretkey);
    if (strcmp($hash, $checkhash) !== 0) {
        throw new Exception('Data is NOT from Telegram');
    }
    if ((time() - $authdata['auth_date']) > 86400) {
        throw new Exception('Data is outdated');
    }
    return $authdata;
}


// User authentication - function.
function user_authentication($db, $authdata) {
    // Creating user - function.
    function create_newuser($db, $authdata) {
        global $PAGE, $CFG, $DB;
        // User not found, so create it.
        $id = $db->execute(
            "INSERT INTO {auth_telegram_login}
            (id, first_name, last_name, telegram_id, telegram_username, profile_picture,auth_date,added,updated)
            VALUES (NULL,'".$authdata['first_name']."', '".$authdata['last_name']."', '".$authdata['id']."', '"
            .$authdata['username']."', '".$authdata['photo_url']."','".$authdata['auth_date']."',1,1)" );
            global $CFG, $DB;
            require_once($CFG->dirroot . '/user/profile/lib.php');
            require_once($CFG->dirroot . '/user/lib.php');
            $notify = true;
            $user = new stdClass();
            $user->auth = "telegram";
            $user->username = strtolower("telegram_".$authdata['username']);
            $user->firstname = $authdata['first_name'];
            $user->lastname = $authdata['last_name'];
            $user->confirmed = 1;
            $user->mnethostid = 1;
            $user->firstaccess = 0;
            $user->timecreated = time();
            $user->password = '';
            $user->email = '';
        if (empty($user->calendartype)) {
            $user->calendartype = $CFG->calendartype;
        }
            $user->id = user_create_user($user, false, false);
            // Save any custom profile field information.
            profile_save_data($user);
            // Trigger event.
            \core\event\user_created::create_from_userid($user->id)->trigger();
            user_login($DB, $user->username, $authdata);
    }

    // Updating user - function.
    function user_login($db, $username, $authdata) {
         global $PAGE, $CFG, $DB;
        // User found, so update it.
        $user = $DB->get_record('user', array('username' => $username));
        if (!$user) {
            create_newuser($DB, $authdata);
        }

        complete_user_login($user); // Triggers the login event.
        \core\session\manager::apply_concurrent_login_limit($user->id, session_id());
        redirect($CFG->wwwroot.'/my/');
    }

    // User checker - function.
    function check_user_exists($db, $authdata) {
        // Get the user Telegram ID.
        $targetid = $authdata['id'];
        $sql = "SELECT *
        FROM {auth_telegram_login}
        WHERE " . $db->sql_equal('telegram_id', ':telegram_id', false, true);
        $params = array(
          'telegram_id' => $targetid
        );
        if ( $db->record_exists_sql($sql, $params) ) {
            $records = $db->get_record_sql($sql, $params);
            if ($records->telegram_id === $targetid) {
                return true;
            }
        }
    }
    function get_user($db, $authdata) {
        $targetid = $authdata['id'];
        $sql = "SELECT *
        FROM {auth_telegram_login}
        WHERE " . $db->sql_equal('telegram_id', ':telegram_id', false, true);
        $params = array(
          'telegram_id' => $targetid
        );
        if ( $db->record_exists_sql($sql, $params) ) {
            $records = $db->get_record_sql($sql, $params);
            if ($records->telegram_id === $targetid) {
                return $records->telegram_username;
            }
        }
    }
    global $PAGE, $CFG, $DB;
    // Check the user.
    if ( check_user_exists($DB, $authdata) == true ) {
        // User found, so update it.
        $username = get_user($db, $authdata);
        $username = strtolower("telegram_".$username);
        user_login($DB, $username, $authdata);
    } else {
        // User not found, so create it.
        create_newuser($DB, $authdata);
    }
    // Create logged in user session.
    $_SESSION = [
        'logged-in' => true,
        'telegram_id' => $authdata['id']
    ];
}
// Start the process.
try {
    // Get the authorized user data from Telegram widget.
    $authdata = check_telegram_authorization($_GET);

    // Authenticate the user.
    user_authentication( $DB, $authdata);
} catch (Exception $e) {
    // Display errors.
    die($e->getMessage());
}
