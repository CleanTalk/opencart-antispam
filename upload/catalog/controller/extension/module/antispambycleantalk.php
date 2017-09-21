<?php
class ControllerExtensionModuleAntispamByCleantalk extends Controller {
	public function index() {
		$this->load->language('extension/module/antispambycleantalk');

		if (isset($this->session->data['agechecked_agecheckid'])) {
			$data['agechecked_agecheckid'] = $this->session->data['agechecked_agecheckid'];
		} else {
			$data['agechecked_agecheckid'] = '';
		}

		if (isset($this->session->data['agechecked_ageverifiedid'])) {
			$data['agechecked_ageverifiedid'] = $this->session->data['agechecked_ageverifiedid'];
		} else {
			$data['agechecked_ageverifiedid'] = '';
		}

		return $this->load->view('extension/module/antispambycleantalk', $data);
	}

	public function check() {
		$error = false;

		$agechecked_agecheckid = $this->request->post['agechecked_agecheckid'];
		$agechecked_ageverifiedid = $this->request->post['agechecked_ageverifiedid'];
		if ($agechecked_agecheckid == '' && $agechecked_ageverifiedid == '') {
			$error = true;
		}elseif ($agechecked_agecheckid == '') {
			$error = true;
		}elseif ($agechecked_ageverifiedid == '') {
			$error = true;
		}else{
			/*$url =  $this->config->get('module_agechecked_ageverification_apiurl').'/jsapi/getagecheck/?merchantkey='.$this->config->get('module_agechecked_ageverification_privatekey').'&agecheckid='.$agechecked_agecheckid; 
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$data = curl_exec($ch);
			curl_close ($ch);
			if ($data === false) {
			   $error = true;
			}else{
			  //$data = preg_split('/^r?$/m', $data, 2);
			  //$data = trim($data[1]);
			  $jd  = (object) json_decode($data, true);

			  if(isset($jd->Error)) $error = true;
			  elseif (($jd->status != 6 || $jd->status != 7) || $jd->agecheckid != $agechecked_agecheckid || $jd->ageverifiedid != $agechecked_ageverifiedid)
			  {
				$error = true;
			  }
			}*/
		}

		if($error){$this->session->data['agechecked_agecheckid'] = ''; $this->session->data['agechecked_ageverifiedid'] = '';}
		else{
		  $this->session->data['agechecked_agecheckid'] = trim($this->request->post['agechecked_agecheckid']);
		  $this->session->data['agechecked_ageverifiedid'] = trim($this->request->post['agechecked_ageverifiedid']);
		}

		return !$error;
	}
}
