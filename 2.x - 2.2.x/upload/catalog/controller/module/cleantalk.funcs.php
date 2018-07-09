<?php
require_once DIR_APPLICATION . '/controller/module/cleantalk.class.php';

class CleantalkFuncs
{
    const ENGINE = 'opencart-15';

    private $ct_access_key = '';

    function __construct($access_key = '')
    {
        $this->ct_access_key = trim($access_key);
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
            setcookie('apbct_prev_referer', $_SERVER['HTTP_REFERER'], 0, '/');
            $cookie_test_value['cookies_names'][] = 'apbct_prev_referer';
            $cookie_test_value['check_value'] .= $_SERVER['HTTP_REFERER'];
        }           
        // Submit time
        $apbct_timestamp = time();
        setcookie('apbct_timestamp', $apbct_timestamp, 0, '/');
        $cookie_test_value['cookies_names'][] = 'apbct_timestamp';
        $cookie_test_value['check_value'] .= $apbct_timestamp;

        // Cookies test
        $cookie_test_value['check_value'] = md5($cookie_test_value['check_value']);
        setcookie('apbct_cookies_test', json_encode($cookie_test_value), 0, '/');
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
    public function onSpamCheck($content_type, $data)
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
        $ct_request->sender_ip = $ct->cleantalk_get_real_ip();
        $ct_request->agent = self::ENGINE;
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
}