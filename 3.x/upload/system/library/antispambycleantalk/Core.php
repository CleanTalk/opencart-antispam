<?php

namespace AntispamByCleantalk;

use Cleantalk\Antispam\RemoteCalls;
use Cleantalk\Antispam\SFW;
use Cleantalk\Antispam\Cleantalk;
use Cleantalk\Antispam\CleantalkRequest;
use Cleantalk\Antispam\Helper;

class Core
{
    const VERSION = '2.0';

    private $agent;

    private $ct_access_key = '';

    private $is_active = false;

    /*
     * Temporary variable for containing blocking comment.
     * @type string
     */
    private $comment = '';

    private static $instance;

    /**
     * @param \Registry  $registry  Registry Object
     *
     * @return self object
     */
    public static function get_instance( \Registry $registry )
    {
        if (is_null(static::$instance)) {
            static::$instance = new static( $registry );
        }
        return static::$instance;
    }

    private function __construct( \Registry $registry )
    {
        $this->autoloader();
        $this->agent = 'opencart-' . str_replace( '.', '', self::VERSION );
        $this->rc = new RemoteCalls( $registry->get('db'), DB_PREFIX );
        $this->sfw = new SFW( $registry->get('db'), DB_PREFIX );
    }

    private function autoloader() {
        /**
         * Autoloader for \Cleantalk\* classes
         *
         * @param string $class
         *
         * @return void
         */
        spl_autoload_register( function( $class ){
            // Register class auto loader
            // Custom modules1
            if(strpos($class, 'Cleantalk') !== false){
                $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
                $class_file = __DIR__ . DIRECTORY_SEPARATOR . $class . '.php';
                if(file_exists($class_file)){
                    require_once($class_file);
                }
            }
        });
    }

    public function init( \Config $config )
    {
        $this->ct_access_key = $config->get( 'module_antispambycleantalk_access_key' );
        $this->is_active = (bool) $config->get('module_antispambycleantalk_status');

        $this->setCookie();
    }

    public function setCookie()
    {
        // Cookie names to validate
        $cookie_test_value = array(
            'cookies_names' => array(),
            'check_value' => $this->ct_access_key,
        );
        // Pervious referer
        if(!empty($_SERVER['HTTP_REFERER'])){
            Helper::apbct_cookie__set('apbct_prev_referer', $_SERVER['HTTP_REFERER'], 0, '/');
            $cookie_test_value['cookies_names'][] = 'apbct_prev_referer';
            $cookie_test_value['check_value'] .= $_SERVER['HTTP_REFERER'];
        }
        // Submit time
        $apbct_timestamp = time();
        Helper::apbct_cookie__set('apbct_timestamp', $apbct_timestamp, 0, '/');
        $cookie_test_value['cookies_names'][] = 'apbct_timestamp';
        $cookie_test_value['check_value'] .= $apbct_timestamp;

        // Cookies test
        $cookie_test_value['check_value'] = md5($cookie_test_value['check_value']);
        Helper::apbct_cookie__set('apbct_cookies_test', json_encode($cookie_test_value), 0, '/');
    }

    public function apbctCookiesTest()
    {
        if(isset($_COOKIE['apbct_cookies_test'])){

            $cookie_test = json_decode(stripslashes($_COOKIE['apbct_cookies_test']), true);

            $check_srting = $this->ct_access_key;
            foreach($cookie_test['cookies_names'] as $cookie_name){
                $check_srting .= isset($_COOKIE[$cookie_name]) ? $_COOKIE[$cookie_name] : '';
            } unset($cokie_name);

            if($cookie_test['check_value'] == md5($check_srting)){
                return 1;
            }else{
                return 0;
            }
        }else{
            return null;
        }
    }

    /**
     * Checking if this request is spam ot not.
     *
     * @param \Controller $controller
     *
     * @return bool true|false
     */
    public function is_spam(\Controller $controller )
    {
        switch( get_class( $controller ) ) {
            case 'ControllerAccountRegister'  :
            case 'ControllerAffiliateRegister':
            case 'ControllerCheckoutRegister' :
                $ct_result = $this->onSpamCheck( 'register', $controller->request->post );
                break;
            case 'ControllerJournal3Checkout' :
                $ct_result = $this->onSpamCheck( 'register', $controller->request->post['order_data'] );
                break;
            case 'ControllerCheckoutGuest' :
                $ct_result = $this->onSpamCheck( 'order', $controller->request->post );
                break;
            case 'ControllerProductProduct' :
                $ct_result = $this->onSpamCheck( 'comment', $controller->request->post );
                break;
            case 'ControllerInformationContact' :
                $ct_result = $this->onSpamCheck( 'contact', $controller->request->post );
                break;
            case 'ControllerJournal3Form'       :
                $ct_result = $this->onSpamCheck( 'general_comment', $controller->request->post['item'] );
                break;
            case 'ControllerAccountReturn' :
                $ct_result = $this->onSpamCheck( 'return', $controller->request->post );
                break;
            default:
                $ct_result = $this->onSpamCheck( 'general_comment', $controller->request->post );
                break;

        }
        if ( $ct_result['allow'] == 0 ) {
            $this->comment = $ct_result['comment'];
            return true;
        } else {
            return false;
        }
    }

    public function get_block_comment()
    {
        return $this->comment;
    }

    private function onSpamCheck($content_type, $data)
    {
        $ret_val = array();
        $ret_val['allow'] = 1;

        $sender_info = json_encode(array(
            'REFFERRER' => isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : null,
            'page_url' => isset($_SERVER['SERVER_NAME'], $_SERVER['REQUEST_URI']) ? htmlspecialchars($_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']) : null,
            'USER_AGENT' => isset($_SERVER['HTTP_USER_AGENT']) ? htmlspecialchars($_SERVER['HTTP_USER_AGENT']) : null,
            'fields_number' => sizeof($data),
            'REFFERRER_PREVIOUS' => isset($_COOKIE['apbct_prev_referer']) ? $_COOKIE['apbct_prev_referer'] : null,
            'cookies_enabled' => $this->apbctCookiesTest(),
            'mouse_cursor_positions' => isset($_COOKIE['apbct_pointer_data']) ? json_decode(stripslashes($_COOKIE['apbct_pointer_data']), true) : null,
            'js_timezone'            => isset($_COOKIE['apbct_timezone']) ? $_COOKIE['apbct_timezone'] : null,
            'key_press_timestamp'    => isset($_COOKIE['apbct_fkp_timestamp']) ? $_COOKIE['apbct_fkp_timestamp'] : null,
            'page_set_timestamp'     => isset($_COOKIE['apbct_ps_timestamp']) ? $_COOKIE['apbct_ps_timestamp'] : null,
            'form_visible_inputs'    => !empty($_COOKIE['apbct_visible_fields_count']) ? $_COOKIE['apbct_visible_fields_count'] : null,
            'apbct_visible_fields'   => !empty($_COOKIE['apbct_visible_fields']) ? $_COOKIE['apbct_visible_fields'] : null,

        ));
        $post_info = json_encode(array(
            'comment_type' => $content_type,
            'post_url' => isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : null,
        ));
        $js_on = 0;
        if (isset($_POST['ct_checkjs']) && $_POST['ct_checkjs'] == date("Y"))
            $js_on = 1;
        $ct = new Cleantalk();
        $ct->work_url = 'http://moderate.cleantalk.org';
        $ct->server_url = 'http://moderate.cleantalk.org';
        $ct_request = new CleantalkRequest();
        $ct_request->auth_key = $this->ct_access_key;
        $ct_request->sender_ip       = Helper::ip__get(array('real'), false);
        $ct_request->x_forwarded_for = Helper::ip__get(array('x_forwarded_for'), false);
        $ct_request->x_real_ip       = Helper::ip__get(array('x_real_ip'), false);
        $ct_request->agent = $this->agent;
        $ct_request->js_on = $js_on;
        $ct_request->sender_info = $sender_info;
        $ct_request->submit_time = isset($_COOKIE['apbct_timestamp']) ? time() - intval($_COOKIE['apbct_timestamp']) : 0;
        $ct_request->post_info = $post_info;
        switch ($content_type)
        {
            case 'register':
                $ct_request->sender_email = $data['email'];
                $ct_request->sender_nickname = trim($data['firstname']).' '.trim($data['lastname']);
                $ct_result = $ct->isAllowUser($ct_request);
                break;
            case 'order':
                $ct_request->sender_email = $data['email'];
                $ct_request->sender_nickname = trim($data['firstname']).' '.trim($data['lastname']);
                $ct_result = $ct->isAllowUser($ct_request);
                break;
            case 'contact':
                $ct_request->sender_email = $data['email'];
                $ct_request->sender_nickname = trim($data['name']);
                $ct_request->message = trim($data['enquiry']);
                $ct_result = $ct->isAllowMessage($ct_request);
                break;
            case 'comment':
                $ct_request->sender_nickname = trim($data['name']);
                $ct_request->message = trim($data['text']);
                $ct_result = $ct->isAllowMessage($ct_request);
                break;
            case 'return':
                $ct_request->sender_email = $data['email'];
                $ct_request->sender_nickname = trim($data['firstname']) . ' ' . trim($data['lastname']);
                $ct_request->message = trim($data['comment']);
                $ct_result = $ct->isAllowMessage($ct_request);
                break;
            case 'general_comment' :
                $fields = $this->get_fields_any( $data );
                $ct_request->sender_email    = ($fields['email']    ? $fields['email']    : '');
                $ct_request->sender_nickname = ($fields['nickname'] ? $fields['nickname'] : '');
                $ct_request->message         = ($fields['message']  ? implode( "\n\n", $fields['message'] )  : '');
                $ct_result = $ct->isAllowMessage($ct_request);
                break;
        }
        if (isset($ct_result))
        {
            if ($ct_result->errno != 0){
                //TODO: inform admin
                $ret_val['errno'] = $ct_result->errno;
                if ($js_on == 0)
                {
                    $ret_val['allow'] = 0;
                    $ret_val['comment'] = 'Cleantalk. Javascript Disabled';
                }
            }
            else
            {
                if ($ct_result->allow == 0)
                {
                    $ret_val['allow'] = 0;
                    $ret_val['comment'] = $ct_result->comment;
                }
            }
        }

        return $ret_val;
    }

    /*
    * Get data from an ARRAY recursively
    * @return array
    */
    private function get_fields_any($arr, $message=array(), $email = null, $nickname = array('nick' => '', 'first' => '', 'last' => ''), $subject = null, $contact = true, $prev_name = ''){

        //Skip request if fields exists
        $skip_params = array(
            'ipn_track_id', 	// PayPal IPN #
            'txn_type', 		// PayPal transaction type
            'payment_status', 	// PayPal payment status
            'ccbill_ipn', 		// CCBill IPN
            'ct_checkjs', 		// skip ct_checkjs field
            'api_mode',         // DigiStore-API
            'loadLastCommentId' // Plugin: WP Discuz. ticket_id=5571
        );

        // Fields to replace with ****
        $obfuscate_params = array(
            'password',
            'pass',
            'pwd',
            'pswd'
        );

        // Skip feilds with these strings and known service fields
        $skip_fields_with_strings = array(
            // Common
            'ct_checkjs', //Do not send ct_checkjs
            'nonce', //nonce for strings such as 'rsvp_nonce_name'
            'security',
            // 'action',
            'http_referer',
            'referer-page',
            'timestamp',
            'captcha',
            // Formidable Form
            'form_key',
            'submit_entry',
            // Custom Contact Forms
            'form_id',
            'ccf_form',
            'form_page',
            // Qu Forms
            'iphorm_uid',
            'form_url',
            'post_id',
            'iphorm_ajax',
            'iphorm_id',
            // Fast SecureContact Froms
            'fs_postonce_1',
            'fscf_submitted',
            'mailto_id',
            'si_contact_action',
            // Ninja Forms
            'formData_id',
            'formData_settings',
            'formData_fields_\d+_id',
            'formData_fields_\d+_files.*',
            // E_signature
            'recipient_signature',
            'output_\d+_\w{0,2}',
            // Contact Form by Web-Settler protection
            '_formId',
            '_returnLink',
            // Social login and more
            '_save',
            '_facebook',
            '_social',
            'user_login-',
            // Contact Form 7
            '_wpcf7',
            'ebd_settings',
            'ebd_downloads_',
            'ecole_origine',
            'signature',
            // Ultimate Form Builder
            'form_data_%d_name',
        );

        // Reset $message if we have a sign-up data
        $skip_message_post = array(
            'edd_action', // Easy Digital Downloads
        );

        if( $this->apbct_array( array( $_POST, $_GET ) )->get_keys( $skip_params )->result() )
            $contact = false;

        if(count($arr)){

            foreach($arr as $key => $value){

                if(gettype($value) == 'string'){

                    $tmp = strpos($value, '\\') !== false ? stripslashes($value) : $value;
                    $decoded_json_value = json_decode($tmp, true);

                    // Decoding JSON
                    if($decoded_json_value !== null){
                        $value = $decoded_json_value;

                        // Ajax Contact Forms. Get data from such strings:
                        // acfw30_name %% Blocked~acfw30_email %% s@cleantalk.org
                        // acfw30_textarea %% msg
                    }elseif(preg_match('/^\S+\s%%\s\S+.+$/', $value)){
                        $value = explode('~', $value);
                        foreach ($value as &$val){
                            $tmp = explode(' %% ', $val);
                            $val = array($tmp[0] => $tmp[1]);
                        }
                    }
                }

                if(!is_array($value) && !is_object($value)){

                    if (in_array($key, $skip_params, true) && $key != 0 && $key != '' || preg_match("/^ct_checkjs/", $key))
                        $contact = false;

                    if($value === '')
                        continue;

                    // Skipping fields names with strings from (array)skip_fields_with_strings
                    foreach($skip_fields_with_strings as $needle){
                        if (preg_match("/".$needle."/", $prev_name.$key) == 1){
                            continue(2);
                        }
                    }unset($needle);

                    // Obfuscating params
                    foreach($obfuscate_params as $needle){
                        if (strpos($key, $needle) !== false){
                            $value = $this->obfuscate_param($value);
                            continue(2);
                        }
                    }unset($needle);

                    $value_for_email = trim( $value);    // Removes shortcodes to do better spam filtration on server side.

                    // Email
                    if ( ! $email && preg_match( "/^\S+@\S+\.\S+$/", $value_for_email ) ) {
                        $email = $value_for_email;

                        // Removes whitespaces
                        $value = urldecode( trim( $value ) ); // Fully cleaned message

                        // Names
                    }elseif (preg_match("/name/i", $key)){

                        preg_match("/((name.?)?(your|first|for)(.?name)?)/", $key, $match_forename);
                        preg_match("/((name.?)?(last|family|second|sur)(.?name)?)/", $key, $match_surname);
                        preg_match("/(name.?)?(nick|user)(.?name)?/", $key, $match_nickname);

                        if(count($match_forename) > 1)
                            $nickname['first'] = $value;
                        elseif(count($match_surname) > 1)
                            $nickname['last'] = $value;
                        elseif(count($match_nickname) > 1)
                            $nickname['nick'] = $value;
                        else
                            $message[$prev_name.$key] = $value;

                        // Subject
                    }elseif ($subject === null && preg_match("/subject/i", $key)){
                        $subject = $value;

                        // Message
                    }else{
                        $message[$prev_name.$key] = $value;
                    }

                }elseif(!is_object($value)){

                    $prev_name_original = $prev_name;
                    $prev_name = ($prev_name === '' ? $key.'_' : $prev_name.$key.'_');

                    $temp = $this->get_fields_any($value, $message, $email, $nickname, $subject, $contact, $prev_name);

                    $message 	= $temp['message'];
                    $email 		= ($temp['email'] 		? $temp['email'] : null);
                    $nickname 	= ($temp['nickname'] 	? $temp['nickname'] : null);
                    $subject 	= ($temp['subject'] 	? $temp['subject'] : null);
                    if($contact === true)
                        $contact = ($temp['contact'] === false ? false : true);
                    $prev_name 	= $prev_name_original;
                }
            } unset($key, $value);
        }

        foreach ($skip_message_post as $v) {
            if (isset($_POST[$v])) {
                $message = null;
                break;
            }
        } unset($v);

        //If top iteration, returns compiled name field. Example: "Nickname Firtsname Lastname".
        if($prev_name === ''){
            if(!empty($nickname)){
                $nickname_str = '';
                foreach($nickname as $value){
                    $nickname_str .= ($value ? $value." " : "");
                }unset($value);
            }
            $nickname = $nickname_str;
        }

        $return_param = array(
            'email' 	=> $email,
            'nickname' 	=> $nickname,
            'subject' 	=> $subject,
            'contact' 	=> $contact,
            'message' 	=> $message
        );
        return $return_param;
    }

    /**
     * Masks a value with asterisks (*)
     * @return string
     */
    private function obfuscate_param( $value = null ) {
        if ($value && (!is_object($value) || !is_array($value))) {
            $length = strlen($value);
            $value = str_repeat('*', $length);
        }
        return $value;
    }

    private function apbct_array( $array ){
        return new \Cleantalk\Common\Arr( $array );
    }

}
