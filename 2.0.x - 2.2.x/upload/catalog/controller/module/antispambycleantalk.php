<?php
class ControllerModuleAntispamByCleantalk extends Controller {
	public function index() {
		$this->load->language('module/antispambycleantalk');

		$data['antispambycleantalk'] = '';

		return $this->load->view('module/antispambycleantalk', $data);
	}

	public function check() {
		$error = false;

		return !$error;
	}
}
