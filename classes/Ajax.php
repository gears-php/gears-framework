<?php
namespace Gears\Framework;

class Ajax
{
	const MSG_ERROR = 'error';
	const MSG_WARNING = 'warning';
	const MSG_NOTICE = 'notice';
	const MSG_INFO = 'info';

	private $messages = [];

	/**
	 *
	 */
	private $data = [];

	public function addError($text)
	{
		$this->messages[self::MSG_ERROR][] = $text;
	}

	public function addWarning($text)
	{
		$this->messages[self::MSG_WARNING][] = $text;
	}

	public function addNotice($text)
	{
		$this->messages[self::MSG_NOTICE][] = $text;
	}

	public function addInfo($text)
	{
		$this->messages[self::MSG_INFO][] = $text;
	}

	/**
	 *
	 */
	public function setData(array $data)
	{
		$this->data = $data;
	}

	/**
	 *
	 */
	public function addData($name, $value)
	{
		$this->data[$name] = $value;
	}

	/**
	 *
	 */
	public function sendResponse()
	{
		echo json_encode([
			'messages' => $this->messages,
			'data' => $this->data
		]);
	}
}