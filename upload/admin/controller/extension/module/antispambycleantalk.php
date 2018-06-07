<?php
class ControllerExtensionModuleAntispamByCleantalk extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/antispambycleantalk');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_antispambycleantalk', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/antispambycleantalk', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/antispambycleantalk', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		if (isset($this->request->post['module_antispambycleantalk_status'])) {
			$data['module_antispambycleantalk_status'] = $this->request->post['module_antispambycleantalk_status'];
		} else {
			$data['module_antispambycleantalk_status'] = $this->config->get('module_antispambycleantalk_status');
		}
		if (isset($this->request->post['module_antispambycleantalk_check_registrations'])) {
			$data['module_antispambycleantalk_check_registrations'] = $this->request->post['module_antispambycleantalk_check_registrations'];
		} else {
			$data['module_antispambycleantalk_check_registrations'] = $this->config->get('module_antispambycleantalk_check_registrations');
		}
		if (isset($this->request->post['module_antispambycleantalk_check_orders'])) {
			$data['module_antispambycleantalk_check_orders'] = $this->request->post['module_antispambycleantalk_check_orders'];
		} else {
			$data['module_antispambycleantalk_check_orders'] = $this->config->get('module_antispambycleantalk_check_orders');
		}
		if (isset($this->request->post['module_antispambycleantalk_check_contact_form'])) {
			$data['module_antispambycleantalk_check_contact_form'] = $this->request->post['module_antispambycleantalk_check_contact_form'];
		} else {
			$data['module_antispambycleantalk_check_contact_form'] = $this->config->get('module_antispambycleantalk_check_contact_form');
		}		
		if (isset($this->request->post['module_antispambycleantalk_access_key'])) {
			$data['module_antispambycleantalk_access_key'] = $this->request->post['module_antispambycleantalk_access_key'];
		} elseif($this->config->get('module_antispambycleantalk_access_key')) {
			$data['module_antispambycleantalk_access_key'] = $this->config->get('module_antispambycleantalk_access_key');
		}else {
			$data['module_antispambycleantalk_access_key'] = '';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/antispambycleantalk', $data));
	}

	public function install(){
		/*$check = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "order` LIKE 'agechecked_ageverificationid'");
		if(!$check->num_rows){
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "order` ADD `agechecked_ageverificationid` VARCHAR(255) NULL");
		}
		*/
	}

	public function uninstall(){
		$this->load->model('setting/setting');

		$this->model_setting_setting->deleteSetting('module_antispambycleantalk');
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/antispambycleantalk')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}