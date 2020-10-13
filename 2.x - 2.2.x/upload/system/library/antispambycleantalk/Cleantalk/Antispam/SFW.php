<?php

namespace Cleantalk\Antispam;

use Cleantalk\Common\Get;
use Cleantalk\Common\Server;

/**
 * CleanTalk SpamFireWall class.
 * Compatible with OpenCart CMS.
 *
 * @author        Cleantalk team (welcome@cleantalk.org)
 * @copyright (C) 2014 CleanTalk team (http://cleantalk.org)
 * @license       GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 */
class SFW
{
	const WRITE_LIMIT = 5000;
	
	public $ip_array = array();
	
	public $results = array();
	public $blocked_ip = '';
	public $result = false;
	public $pass = true;
	
	public $test = false;
	
	/**
	 * @var array of arrays array(origin => array(
		'ip'      => '192.168.0.1',
		'network' => '192.168.0.0',
		'mask'    => '24',
	    'status'  => -1 (blocked) | 1 (passed)
		)
	 */
	public $all_ips = array();
	
	/**
	 * @var array of arrays array(origin => array(
		'ip'      => '192.168.0.1',
		)
	 */
	public $passed_ips = array();
	
	/**
	 * @var array of arrays array(origin => array(
		'ip'      => '192.168.0.1',
		'network' => '192.168.0.0',
		'mask'    => '24',
		)
	 */
	public $blocked_ips = array();

	// Database
    private $db;
	private $table_prefix;
    private $data_table;
    private $log_table;
	
	//Debug
    private $debug;
    private $debug_data = '';

	public function __construct( $db_object, $db_prefix )
	{
        $this->db = $db_object;
        $this->table_prefix = $db_prefix;
        $this->data_table = $db_prefix . 'cleantalk_sfw';
        $this->log_table  = $db_prefix . 'cleantalk_sfw_logs';
		
		$this->debug = isset($_GET['debug']) && intval($_GET['debug']) === 1 ? true : false;

        $this->ip_array = $this->ip__get( array('real'), true );
	}

    /**
     * This method do the main logic of the SFW checking
     *
     * @param $apikey
     * @return void
     */
    public function run( $apikey )
    {
        $is_sfw_check  = true;

        foreach( $this->ip_array as $key => $value )
        {
            if( isset( $_COOKIE['ct_sfw_pass_key'] ) && $_COOKIE['ct_sfw_pass_key'] == md5( $value . $apikey ) )
            {
                $is_sfw_check = false;

                if ( isset( $_COOKIE['ct_sfw_passed'] ) )
                {
                    @setcookie( 'ct_sfw_passed' ); //Deleting cookie
                    $this->logs__update( $value, 'passed' );
                }
            }
        }


        if ( $is_sfw_check )
        {
            $this->ip_check();
            /*if ( $this->pass )
            {
                $this->logs__update( $this->blocked_ip, 'blocked' );
                $this->sfw_die( $apikey );
            }*/
            // Pass remote calls
            if( $this->pass === false ){
                if(isset($_GET['spbc_remote_call_token'], $_GET['spbc_remote_call_action'], $_GET['plugin_name'])){
                    foreach( $this->blocked_ips as $ip ){
                        $resolved = Helper::ip__resolve($ip['ip']);
                        if($resolved && preg_match('/cleantalk\.org/', $resolved) === 1 || $resolved === 'back'){
                            $this->pass = true;
                        }
                    } unset($ip);
                }
            }

            if( $this->pass === false ){
                foreach( $this->blocked_ips as $ip ){
                    $this->logs__update( $ip['ip'], 'blocked' );
                }
                $this->sfw_die( $apikey, '', Server::get('HTTP_HOST') );
            }else{
                reset($this->passed_ips);
                if( ! headers_sent() && key( $this->passed_ips ) )
                    Helper::apbct_cookie__set( 'ct_sfw_pass_key', md5( $this->passed_ips[ key( $this->passed_ips ) ]['ip'] . $apikey ), time() + 86400 * 30, '/', null, false );
            }
        }
    }
	
	/**
	 * Getting arrays of IP (REMOTE_ADDR, X-Forwarded-For, X-Real-Ip, Cf_Connecting_Ip)
	 *
	 * @param array $ips_input type of IP you want to receive
	 * @param bool  $v4_only
	 *
	 * @return array|mixed|null
	 */
	private function ip__get( $ips_input = array('real', 'remote_addr', 'x_forwarded_for', 'x_real_ip', 'cloud_flare'), $v4_only = true )
    {
		
		$result = Helper::ip__get( $ips_input, $v4_only );
		
		$result = !empty($result) ? array( 'real' => $result ) : array();
		
		if( isset( $_GET['sfw_test_ip'] ) )
		{
			if( Helper::ip__validate( $_GET['sfw_test_ip'] ) !== false ){
				$result['sfw_test'] = $_GET['sfw_test_ip'];
				$this->test = true;
			}
		}
		
		return $result;
		
	}
	
	/**
	 * Checks IP via Database
	 */
	private function ip_check()
	{
		foreach( $this->ip_array as $origin => $current_ip )
		{
			$current_ip_v4 = sprintf("%u", ip2long($current_ip));
			for ( $needles = array(), $m = 6; $m <= 32; $m ++ ) {
				$mask      = sprintf( "%u", ip2long( long2ip( - 1 << ( 32 - (int) $m ) ) ) );
				$needles[] = bindec( decbin( $mask ) & decbin( $current_ip_v4 ) );
			}
			$needles = array_unique( $needles );

			$query = "SELECT
				network, mask, status
				FROM " . $this->data_table . "
				WHERE network IN (". implode( ',', $needles ) .") 
				AND	network = " . $current_ip_v4 . " & mask
				ORDER BY status DESC LIMIT 1;";
            $result = $this->db->query($query);

			if( ! empty( $result->row ) ){

                if ( 1 == $result->row['status'] ) {
                    // It is the White Listed network - will be passed.
                    $this->passed_ips[$origin] = array(
                        'ip'     => $current_ip,
                    );
                    $this->all_ips[$origin] = array(
                        'ip'     => $current_ip,
                        'status' => 1,
                    );
                    break;
                } else {
                    $this->pass = false;
                    $this->blocked_ips[$origin] = array(
                        'ip'      => $current_ip,
                        'network' => long2ip($result->row['network']),
                        'mask'    => Helper::ip__mask__long_to_number($result->row['mask']),
                    );
                    $this->all_ips[$origin] = array(
                        'ip'      => $current_ip,
                        'network' => long2ip($result->row['network']),
                        'mask'    => Helper::ip__mask__long_to_number($result->row['mask']),
                        'status'  => -1,
                    );
                }

			}else{
				$this->passed_ips[$origin] = array(
					'ip'     => $current_ip,
				);
				$this->all_ips[$origin] = array(
					'ip'     => $current_ip,
					'status' => 1,
				);
			}		
		}
	}
	
	/**
	 * Add entry to SFW log.
	 * Writes to database.
	 *
	 * @param string $ip
	 * @param string $result "blocked" or "passed"
	 */
	public function logs__update( $ip, $result )
    {
		if($ip === NULL || $result === NULL){
			return;
		}
		
		$blocked = ($result == 'blocked' ? ' + 1' : '');
		$time = time();

		$query = "INSERT INTO ".$this->log_table."
		SET 
			ip = '$ip',
			all_entries = 1,
			blocked_entries = 1,
			entries_timestamp = '".intval($time)."'
		ON DUPLICATE KEY 
		UPDATE 
			all_entries = all_entries + 1,
			blocked_entries = blocked_entries".strval($blocked).",
			entries_timestamp = '".intval($time)."'";

		$this->db->query($query);
	}
	
	/**
	 * Sends and wipe SFW log
	 *
	 * @param string $ct_key API key
	 *
	 * @return array|bool array('error' => STRING)
	 */
	public function logs__send($ct_key)
    {
		//Getting logs
		$query = "SELECT * FROM " . $this->log_table . ";";
		$result = $this->db->query($query);

		if( count( $result->rows ) ){
			
			//Compile logs
			$data = array();
			foreach( $result->rows as $key => $value ){
				$data[] = array( trim($value['ip']), $value['all_entries'], $value['all_entries']-$value['blocked_entries'], $value['entries_timestamp'] );
			}
			unset($key, $value);
			
			//Sending the request
			$result = API::method__sfw_logs( $ct_key, $data );
			//Checking answer and deleting all lines from the table
			if( empty( $result['error'] ) ){
				if( $result['rows'] == count($data) ){
					$this->db->query( "TRUNCATE TABLE " . $this->log_table . ";" );
					return $result;
				}
				return array('error' => 'SENT_AND_RECEIVED_LOGS_COUNT_DOESNT_MACH');
			}else{
				return $result;
			}
				
		} else {
		    return $result = array( 'rows' => 0 );
        }
	}

	public function sfw_update( $api_key, $immediate = false )
    {
        if( ! empty( $api_key ) ) {

            $api_server    = !empty( Get::get( 'api_server' ) )    ? urldecode( Get::get( 'api_server' ) )    : null;
            $data_id       = !empty( Get::get( 'data_id' ) )       ? urldecode( Get::get( 'data_id' ) )       : null;
            $file_url_nums = !empty( Get::get( 'file_url_nums' ) ) ? urldecode( Get::get( 'file_url_nums' ) ) : null;
            $file_url_nums = isset($file_url_nums) ? explode(',', $file_url_nums) : null;

            if( ! isset( $api_server, $data_id, $file_url_nums ) ){

                $result = $this->get_sfw_file( $api_key, $immediate );

                return ! empty( $result['error'] )
                    ? $result
                    : true;

            }elseif( $api_server && $data_id && is_array( $file_url_nums ) && count( $file_url_nums ) ){

                $result = $this->sfw_update_db( $api_server, $data_id, $file_url_nums[0] );

                if( empty( $result['error'] ) ){

                    array_shift( $file_url_nums );

                    if ( count( $file_url_nums ) ) {
                        Helper::http__request(
                            Server::get('HTTP_HOST'),
                            array(
                                'spbc_remote_call_token'  => md5($api_key),
                                'spbc_remote_call_action' => 'sfw_update',
                                'plugin_name'             => 'apbct',
                                'api_server'              => $api_server,
                                'data_id'                 => $data_id,
                                'file_url_nums'           => implode(',', $file_url_nums),
                            ),
                            array('get', 'async')
                        );
                    } else {
                        return $result;
                    }
                }else
                    return $result;
            }else
                return array('error' => 'SFW_UPDATE WRONG_FILE_URLS');
        }

        return array('error' => 'APIKEY_IS_EMPTY');
	}

	private function get_sfw_file( $api_key, $immediate )
    {
        $result = API::method__get_2s_blacklists_db( $api_key, 'multifiles', '2_0' );

        if( empty( $result['error'] ) ){

            if( ! empty( $result['file_url'] ) ){

                if( Helper::http__request( $result['file_url'], array(), 'get_code' ) === 200 ) {

                    if( ini_get('allow_url_fopen') ) {

                        $pattenrs = array();
                        $pattenrs[] = 'get';

                        if(!$immediate) $pattenrs[] = 'async';

                        // Clear SFW table
                        $this->db->query("TRUNCATE TABLE {$this->data_table};");
                        $truncate_check = $this->db->query("SELECT COUNT(network) as cnt FROM {$this->data_table};"); // Check if it is clear
                        if($truncate_check->row['cnt'] != 0){
                            $this->db->query("DELETE FROM {$this->data_table};"); // Truncate table
                            $truncate_check = $this->db->query("SELECT COUNT(network) as cnt FROM {$this->data_table};"); // Check if it is clear
                            if($truncate_check->row['cnt'] != 0){
                                return array('error' => 'COULD_NOT_CLEAR_SFW_TABLE'); // throw an error
                            }
                        }

                        if (preg_match('/multifiles/', $result['file_url'])) {

                            $api_server = preg_replace( '@https://(api.*?)\.cleantalk\.org/.*?(bl_list_[0-9a-z]*?)\.multifiles\.csv\.gz@', '$1', $result['file_url'] );
                            $data_id    = preg_replace( '@https://(api.*?)\.cleantalk\.org/.*?(bl_list_[0-9a-z]*?)\.multifiles\.csv\.gz@', '$2', $result['file_url'] );

                            $gf = \gzopen($result['file_url'], 'rb');

                            if ($gf) {

                                $file_url_nums = array();

                                while( ! \gzeof($gf) ) {
                                    $file_url = trim( \gzgets($gf, 1024) );
                                    $file_url_nums[] = preg_replace( '@(https://.*)\.(\d*)(\.csv\.gz)@', '$2', $file_url );
                                }

                                \gzclose($gf);

                                return Helper::http__request(
                                    Server::get('HTTP_HOST'),
                                    array(
                                        'spbc_remote_call_token'  => md5( $api_key ),
                                        'spbc_remote_call_action' => 'sfw_update',
                                        'plugin_name'             => 'apbct',
                                        'api_server'              => $api_server,
                                        'data_id'                 => $data_id,
                                        'file_url_nums'           => implode(',', $file_url_nums),
                                    ),
                                    $pattenrs
                                );
                            }else
                                return array('error' => 'COULD_NOT_OPEN_REMOTE_FILE_SFW');
                        } else
                            return array('error' => 'COULD_NOT_GET_MULTIFILE');
                    }else
                        return array('error' => 'ERROR_ALLOW_URL_FOPEN_DISABLED');
                }else
                    return array('error' => 'NO_FILE_URL_PROVIDED');
            }else
                return array('error' => 'BAD_RESPONSE');
        }else
            return $result;
    }

    private function sfw_update_db( $api_server = null, $data_id = null, $file_url_num = null )
    {
        $file_url = 'https://' . $api_server . '.cleantalk.org/store/' . $data_id . '.' . $file_url_num . '.csv.gz';

        if( Helper::http__request( $file_url, array(), 'get_code') === 200 ){ // Check if it's there

            $gf = \gzopen($file_url, 'rb');

            if($gf){

                if( ! \gzeof($gf) ){

                    for( $count_result = 0; ! \gzeof($gf); ){

                        $query = "INSERT INTO ".$this->data_table." VALUES %s";

                        for($i=0, $values = array(); self::WRITE_LIMIT !== $i && ! \gzeof($gf); $i++, $count_result++){

                            $entry = trim( \gzgets($gf, 1024) );

                            if(empty($entry)) continue;

                            $entry = explode(',', $entry);

                            // Cast result to int
                            $ip   = preg_replace('/[^\d]*/', '', $entry[0]);
                            $mask = preg_replace('/[^\d]*/', '', $entry[1]);
                            $private = isset($entry[2]) ? $entry[2] : 0;

                            if(!$ip || !$mask) continue;

                            $values[] = '('. $ip .','. $mask .','. $private .')';

                        }

                        if(!empty($values)){
                            $query = sprintf($query, implode(',', $values).';');
                            $this->db->query($query);
                        }

                    }

                    \gzclose($gf);
                    return $count_result;

                }else
                    return array('error' => 'ERROR_GZ_EMPTY');
            }else
                return array('error' => 'ERROR_OPEN_GZ_FILE');
        }else
            return array('error' => 'NO_REMOTE_FILE_FOUND');
    }
	
	/**
	 * Shows DIE page.
	 * Stops script executing.
	 *
	 * @param string $api_key
	 * @param string $cookie_prefix
	 * @param string $cookie_domain
	 * @param bool   $test
	 */
    public function sfw_die( $api_key, $cookie_prefix = '', $cookie_domain = '', $test = false )
    {
        // Headers
        if (headers_sent() === false) {
            header('Expires: ' . date(DATE_RFC822, mktime(0, 0, 0, 1, 1, 1971)));
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Cache-Control: post-check=0, pre-check=0', FALSE);
            header('Pragma: no-cache');
            header("HTTP/1.0 403 Forbidden");
        }

        // File exists?
        if ( file_exists(__DIR__ . DIRECTORY_SEPARATOR . "sfw_die_page.html") ) {

            $sfw_die_page = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . "sfw_die_page.html");

            // Translation
            $request_uri = Server::get('REQUEST_URI');
            $sfw_die_page = str_replace('{SFW_DIE_NOTICE_IP}', __('SpamFireWall is activated for your IP ', 'cleantalk-spam-protect'), $sfw_die_page);
            $sfw_die_page = str_replace('{SFW_DIE_MAKE_SURE_JS_ENABLED}', __('To continue working with web site, please make sure that you have enabled JavaScript.', 'cleantalk-spam-protect'), $sfw_die_page);
            $sfw_die_page = str_replace('{SFW_DIE_CLICK_TO_PASS}', __('Please click the link below to pass the protection,', 'cleantalk-spam-protect'), $sfw_die_page);
            $sfw_die_page = str_replace('{SFW_DIE_YOU_WILL_BE_REDIRECTED}', sprintf(__('Or you will be automatically redirected to the requested page after %d seconds.', 'cleantalk-spam-protect'), 3), $sfw_die_page);
            $sfw_die_page = str_replace('{CLEANTALK_TITLE}', __('Antispam by CleanTalk', 'cleantalk-spam-protect'), $sfw_die_page);
            $sfw_die_page = str_replace('{TEST_TITLE}', ($this->test ? __('This is the testing page for SpamFireWall', 'cleantalk-spam-protect') : ''), $sfw_die_page);

            if ($this->test) {
                $sfw_die_page = str_replace('{REAL_IP__HEADER}', 'Real IP:', $sfw_die_page);
                $sfw_die_page = str_replace('{TEST_IP__HEADER}', 'Test IP:', $sfw_die_page);
                $sfw_die_page = str_replace('{TEST_IP}', $this->all_ips['sfw_test']['ip'], $sfw_die_page);
                $sfw_die_page = str_replace('{REAL_IP}', $this->all_ips['real']['ip'], $sfw_die_page);
                $sfw_die_page = str_replace('{TEST_IP_BLOCKED}', $this->all_ips['sfw_test']['status'] == 1 ? 'Passed' : 'Blocked', $sfw_die_page);
                $sfw_die_page = str_replace('{REAL_IP_BLOCKED}', $this->all_ips['real']['status'] == 1 ? 'Passed' : 'Blocked', $sfw_die_page);
            } else {
                $sfw_die_page = str_replace('{REAL_IP__HEADER}', '', $sfw_die_page);
                $sfw_die_page = str_replace('{TEST_IP__HEADER}', '', $sfw_die_page);
                $sfw_die_page = str_replace('{TEST_IP}', '', $sfw_die_page);
                $sfw_die_page = str_replace('{REAL_IP}', '', $sfw_die_page);
                $sfw_die_page = str_replace('{TEST_IP_BLOCKED}', '', $sfw_die_page);
                $sfw_die_page = str_replace('{REAL_IP_BLOCKED}', '', $sfw_die_page);
            }

            $sfw_die_page = str_replace('{REMOTE_ADDRESS}', $this->blocked_ips ? $this->blocked_ips[key($this->blocked_ips)]['ip'] : '', $sfw_die_page);

            // Service info
            $sfw_die_page = str_replace('{REQUEST_URI}', $request_uri, $sfw_die_page);
            $sfw_die_page = str_replace('{COOKIE_PREFIX}', $cookie_prefix, $sfw_die_page);
            $sfw_die_page = str_replace('{COOKIE_DOMAIN}', $cookie_domain, $sfw_die_page);
            //$sfw_die_page = str_replace('{SERVICE_ID}', $apbct->data['service_id'], $sfw_die_page);
            $sfw_die_page = str_replace('{HOST}', Server::get('HTTP_HOST'), $sfw_die_page);

            $sfw_die_page = str_replace(
                '{SFW_COOKIE}',
                $this->test
                    ? $this->all_ips['sfw_test']['ip']
                    : md5(current(end($this->blocked_ips)) . $api_key),
                $sfw_die_page
            );

            if ($this->debug) {
                $debug = '<h1>IP and Networks</h1>'
                    . var_export($this->all_ips, true)
                    . '<h1>Blocked IPs</h1>'
                    . var_export($this->blocked_ips, true)
                    . '<h1>Passed IPs</h1>'
                    . var_export($this->passed_ips, true)
                    . '<h1>Headers</h1>'
                    . var_export(apache_request_headers(), true)
                    . '<h1>REMOTE_ADDR</h1>'
                    . var_export(Server::get('REMOTE_ADDR'), true)
                    . '<h1>SERVER_ADDR</h1>'
                    . var_export(Server::get('REMOTE_ADDR'), true)
                    . '<h1>IP_ARRAY</h1>'
                    . var_export($this->ip_array, true)
                    . '<h1>ADDITIONAL</h1>'
                    . var_export($this->debug_data, true);
            } else
                $debug = '';

            $sfw_die_page = str_replace("{DEBUG}", $debug, $sfw_die_page);
            $sfw_die_page = str_replace('{GENERATED}', "<p>The page was generated at&nbsp;" . date("D, d M Y H:i:s") . "</p>", $sfw_die_page);

            die($sfw_die_page);

        } else {
            die("IP BLACKLISTED");
        }
    }
}

/**
 * Fix for compatibility for any CMS
 */
if( ! function_exists( '__' ) ) {
    function __( $text, $domain = 'default' ) {
        return $text;
    }
}
