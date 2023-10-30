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
// defined('MOODLE_INTERNAL') || die();
require_once('../../config.php');


// Place bot token of your bot here
define('BOT_TOKEN',get_config('auth_telegram', 'bottoken'));

// The Telegram hash is required to authorize
if (!isset($_GET['hash'])) {
    die('Telegram hash not found');
}


// Official Telegram authorization - function
function checkTelegramAuthorization($auth_data)
{
    $check_hash = $auth_data['hash'];
    unset($auth_data['hash']);
    $data_check_arr = [];
    foreach ($auth_data as $key => $value) {
        $data_check_arr[] = $key . '=' . $value;
    }
    sort($data_check_arr);
    $data_check_string = implode("\n", $data_check_arr);
    $secret_key = hash('sha256', BOT_TOKEN, true);
    $hash = hash_hmac('sha256', $data_check_string, $secret_key);
    if (strcmp($hash, $check_hash) !== 0) {
        throw new Exception('Data is NOT from Telegram');
    }
    if ((time() - $auth_data['auth_date']) > 86400) {
        throw new Exception('Data is outdated');
    }
    return $auth_data;
}


// User authentication - function
function userAuthentication($db, $auth_data)
{
    // Creating user - function
    function createNewUser($db, $auth_data)
    {
        global $PAGE, $CFG, $DB;
      
        // User not found, so create it
        $id = $db->execute(
            "INSERT INTO {auth_telegram_login} (id, first_name, last_name, telegram_id, telegram_username, profile_picture,auth_date,added,updated) VALUES (NULL,'".$auth_data['first_name']."', '".$auth_data['last_name']."', '".$auth_data['id']."', '".$auth_data['username']."', '".$auth_data['photo_url']."','".$auth_data['auth_date']."',1,1)" );
            global $CFG, $DB;
            require_once($CFG->dirroot . '/user/profile/lib.php');
            require_once($CFG->dirroot . '/user/lib.php');
            $notify=true;
            $user = new stdClass();
            $user->auth = "telegram";
            $user->username = strtolower("telegram_".$auth_data['username']);
            $user->firstname = $auth_data['first_name'];
            $user->lastname = $auth_data['last_name'];
            $user->confirmed = 1;
            $user->mnethostid = 1;
            $user->firstaccess = 0;
            $user->timecreated = time();
            $user->password = '';

            $user->email = $user->username . "@telegram.com";
            if (empty($user->calendartype)) {
                $user->calendartype = $CFG->calendartype;
            }
            $user->id = user_create_user($user, false, false);
    
            // Save any custom profile field information.
            profile_save_data($user);
    
            // Trigger event.
            \core\event\user_created::create_from_userid($user->id)->trigger();
            UserLogin($DB,$user->username,$auth_data);

    }

    // Updating user - function
   function UserLogin($db, $username,$auth_data)
    {
         global $PAGE, $CFG, $DB;
        // User found, so update it
       if( !($user = $DB->get_record('user', array('username'=>$username)))){
        createNewUser($DB, $auth_data);
       }

        complete_user_login($user); // Triggers the login event.
    
        \core\session\manager::apply_concurrent_login_limit($user->id, session_id());
        
        redirect($CFG->wwwroot.'/my/');
    }

    // User checker - function
    function checkUserExists($db, $auth_data)
    {
        //global $PAGE, $CFG, $DB;
        // Get the user Telegram ID
        $target_id = $auth_data['id'];
        $sql = "SELECT *
        FROM {auth_telegram_login}
        WHERE " . $db->sql_equal('telegram_id', ':telegram_id', false, true);
        $params = array(
          'telegram_id' => $target_id
        );
        if ($db->record_exists_sql($sql, $params)) {
            $records = $db->get_record_sql($sql, $params);
           if($records->telegram_id=== $target_id) {
            return TRUE;
        }
           }
        
    }
    function getUser($db, $auth_data)
    {
       // global $PAGE, $CFG, $DB;
        $target_id = $auth_data['id'];
        $sql = "SELECT *
        FROM {auth_telegram_login}
        WHERE " . $db->sql_equal('telegram_id', ':telegram_id', false, true);
        $params = array(
          'telegram_id' => $target_id
        );
        if ($db->record_exists_sql($sql, $params)) {
            $records = $db->get_record_sql($sql, $params);
           if($records->telegram_id=== $target_id) {
            return $records->telegram_username;
        }
           }
        
    }
    global $PAGE, $CFG, $DB;

    // Check the user
    if (checkUserExists($DB, $auth_data) == TRUE) {
        // User found, so update it
        $username = getUser($db, $auth_data);
        $username = strtolower("telegram_".$username);
        UserLogin($DB, $username,$auth_data);
    } else {
        // User not found, so create it
        createNewUser($DB, $auth_data);
    }

    // Create logged in user session
    $_SESSION = [
        'logged-in' => TRUE,
        'telegram_id' => $auth_data['id']
    ];
}


// Start the process
try {
    // Get the authorized user data from Telegram widget
    $auth_data = checkTelegramAuthorization($_GET);

    // Authenticate the user
    userAuthentication( $DB,$auth_data);
} catch (Exception $e) {
    // Display errors
    die($e->getMessage());
}