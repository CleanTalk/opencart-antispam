<?php
class ControllerExtensionModuleAntispamByCleantalk extends Controller {
	public function index() {
		$this->load->language('extension/module/antispambycleantalk');

		$data['antispambycleantalk'] = '';

		return $this->load->view('extension/module/antispambycleantalk', $data);
	}

	public function check() {
		$error = false;

		return !$error;
	}
}
