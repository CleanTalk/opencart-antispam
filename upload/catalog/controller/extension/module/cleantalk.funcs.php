<?php

class CleantalkFuncs
{
	const ENGINE = 'opencart-13';

	private $ct_access_key = '';
	private $ct_status, $ct_check_register, $ct_check_orders, $ct_check_contact, $ct_check_reviews;

	function __construct($access_key, $status, $check_register, $check_orders, $check_contact, $check_reviews)
	{
		$this->ct_access_key = trim($access_key);
		$this->ct_status = $status;
		$this->ct_check_register = $check_register;
		$this->ct_check_orders = $check_orders;
		$this->ct_check_contact = $check_contact;
		$this->ct_check_reviews = $check_reviews;
	}
	
	public function setCookie()
	{
		// Cookie names to validate
		$cookie_test_value = array(
			'cookies_names' => array(),
			'check_value' => trim($this->ct_access_key),
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
	public function onSpamCheck($content_type, $data)
	{
        $ret_val = array();
        $ret_val['allow'] = 1;		
	    if ($this->ct_status)
	    {
	        require_once DIR_APPLICATION . '/controller/extension/module/cleantalk.class.php';
	        $refferrer = null;
	        if (isset($_SERVER['HTTP_REFERER'])) {
	            $refferrer = htmlspecialchars((string) $_SERVER['HTTP_REFERER']);
	        }

	        $user_agent = null;
	        if (isset($_SERVER['HTTP_USER_AGENT'])) {
	            $user_agent = htmlspecialchars((string) $_SERVER['HTTP_USER_AGENT']);
	        }
	        $sender_info = array(
	            'REFFERRER' => $refferrer,
	            'post_url' => $refferrer,
	            'USER_AGENT' => $user_agent,
	            'REFFERRER_PREVIOUS' => isset($_COOKIE['apbct_prev_referer'])?$_COOKIE['apbct_prev_referer']:null,
	        );
	        $js_on = 0;
	        if (isset($_POST['ct_checkjs']) && $_POST['ct_checkjs'] == date("Y"))
	            $js_on = 1;
	        $sender_info = json_encode($sender_info);   
	        $ct = new Cleantalk();
	        $ct->work_url = 'http://moderate.cleantalk.org';
	        $ct->server_url = 'http://moderate.cleantalk.org';	        
	        $ct_request = new CleantalkRequest();
	        $ct_request->auth_key = $this->ct_access_key;
	        $ct_request->sender_email = $data['email'];
	        $ct_request->sender_ip = $ct->cleantalk_get_real_ip();
	        $ct_request->agent = self::ENGINE;
	        $ct_request->js_on = $js_on;
	        $ct_request->sender_info = $sender_info;
	        $ct_request->submit_time = time() - intval($_COOKIE['apbct_timestamp']);
	        $post_info['comment_type'] = $content_type;
			$ct_request->post_info = $post_info;	        
	        switch ($content_type)
	        {
	        	case 'register':
	        		$ct_request->sender_nickname = trim($data['firstname']).' '.trim($data['lastname']);
			        if ($this->ct_check_register)
			            $ct_result = $ct->isAllowUser($ct_request);                
			        break;
			    case 'order':
			    	$ct_request->sender_nickname = trim($data['firstname']).' '.trim($data['lastname']);

	                if ($this->ct_check_orders)
	                    $ct_result = $ct->isAllowUser($ct_request);
	                break;
	            case 'contact':
	            	$ct_request->sender_nickname = trim($data['name']);
	            	$ct_request->message = trim($data['enquiry']);
			    	if ($this->ct_check_contact)
			    		$ct_result = $ct->isAllowMessage($ct_request);
			    	break;
			    case 'comment':
			    	$ct_request->sender_nickname = trim($data['name']);
			    	$ct_request->message = trim($data['text']);
			    	if ($this->ct_check_reviews)
			    		$ct_result = $ct->isAllowMessage($ct_request);
			    	break;
	        }
	        if ($ct_result)
	        {
	        	if ($ct_result->errno != 0){
	        		//TODO: inform admin
	        		$ret_val['errno'] = $ct_result->errno;
	        		if ($js_on == 1)
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
	    }
	    
		return $ret_val; 	    		
	}
}