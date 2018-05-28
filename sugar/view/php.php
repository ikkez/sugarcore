<?php


namespace Sugar\View;


class PHP extends Base implements FileViewInterface {

	protected $filePath = 'layout.html';

	/**
	 * @return string
	 */
	public function getTemplate() {
		return $this->filePath;
	}

	/**
	 * @param string $filePath
	 */
	public function setTemplate($filePath) {
		$this->filePath = $filePath;
	}

	/**
	 * render the view
	 * @param string $mime
	 * @return mixed
	 */
	function render($mime='text/html') {
		if (!empty($this->data))
			\Base::instance()->mset($this->data);
		return \View::instance()->render($this->filePath,$mime);
	}
}