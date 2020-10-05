<?php

namespace Cleantalk\Antispam;

use Cleantalk\Common\Get;

class RemoteCalls
{
	private static $allowed_remote_actions = array(
	    'sfw_update',
        'sfw_send_logs',
    );

	private $db;
	private $table_prefix;

    public function __construct( $db_object, $db_prefix )
    {
        $this->db = $db_object;
        $this->table_prefix = $db_prefix;
    }
	
	public function check()
    {
		return isset($_GET['spbc_remote_call_token'], $_GET['spbc_remote_call_action'], $_GET['plugin_name']) && in_array(Get::get('plugin_name'), array('antispam','anti-spam', 'apbct'))
			? true
			: false;
	}
	
	public function perform( $apikey )
    {
		$action = Get::get('spbc_remote_call_action');
		$token  = Get::get('spbc_remote_call_token');

        if( in_array( $action, self::$allowed_remote_actions ) )
        {
            if( strtolower( $token ) == strtolower( md5( $apikey ) ) )
            {
                switch ( $action ) {

                    // SFW update
                    case 'sfw_update':
                        $sfw = new SFW( $this->db, $this->table_prefix );
                        $result = $sfw->sfw_update( $apikey, true );
                        /**
                         * @todo CRUNCH
                         */
                        if(is_string($result) && strpos($result, 'FAIL') !== false){
                            $result = json_decode(substr($result, 5), true);
                        }
                        die(empty($result['error']) ? 'OK' : 'FAIL '.json_encode(array('error' => $result['error'])));
                        break;

                    // SFW send logs
                    case 'sfw_send_logs':
                        $sfw = new SFW( $this->db, $this->table_prefix );
                        $result = $sfw->logs__send( $apikey );
                        die(empty($result['error']) ? 'OK' : 'FAIL '.json_encode(array('error' => $result['error'])));
                        break;

                    // Update plugin
                    case 'update_plugin':
                        break;

                    // Install plugin
                    case 'install_plugin':
                        break;
                    // Activate plugin
                    case 'activate_plugin':
                        break;

                    // Insert API key
                    case 'insert_auth_key':
                        break;

                    // Update settins
                    case 'update_settings':
                        break;
                    // Deactivate plugin
                    case 'deactivate_plugin':
                        break;
                    // Uninstall plugin
                    case 'uninstall_plugin':
                        break;
                    // No action found
                    default:
                        die('FAIL '.json_encode(array('error' => 'UNKNOWN_ACTION_2')));
                        break;
                }

            }else
                die('FAIL '.json_encode(array('error' => 'WRONG_TOKEN')));
        }else
            die('FAIL '.json_encode(array('error' => 'UNKNOWN_ACTION')));

	}

}
