<?php

require_once(OP_MOD . 'email/ProviderInterface.php');
require_once(OP_LIB . 'vendor/infusionsoft/isdk.php');

/**
 * Infusionsoft email integration provider
 * @author Luka Peharda <luka.peharda@gmail.com>
 */
class OptimizePress_Modules_Email_Provider_Infusionsoft implements OptimizePress_Modules_Email_ProviderInterface
{
	const OPTION_NAME_ACCOUNT_ID = 'infusionsoft_account_id';
	const OPTION_NAME_API_KEY = 'infusionsoft_api_key';

	/**
	 * @var OP_iSDK
	 */
	protected $client = null;

	/**
	 * @var string
	 */
	protected $accountId;

	/**
	 * @var string
	 */
	protected $apiKey;

	public function __construct()
	{
		$this->accountId = op_get_option(self::OPTION_NAME_ACCOUNT_ID);
		$this->apiKey = op_get_option(self::OPTION_NAME_API_KEY);
	}

	public function getClient()
	{
		if (null === $this->client) {
			$this->client = new OP_iSDK();
			$this->client->cfgCon($this->accountId, $this->apiKey);
		}

		return $this->client;
	}

	public function subscribe($data)
	{
		if (isset($data['email'])) {

			$name = op_post('name');
			$params = array(
				'FirstName' => false !== $name ? $name : '',
				'Email' => $data['email']
			);

			try {
				$this->getClient()->addCon($params);
			} catch (Exception $e) {
				error_log('Exception while doing optin: ' . print_r($e->getMessage(), true));
			}
			
			return true;
		} else {
			wp_die('Mandatory information not present [email address].');
		}
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::register()
	 */
	public function register($list, $email, $fname, $lname)
	{
		$params = array(
			'FirstName' => $fname,
			'LastName' 	=> $lname,
			'Email' 	=> $email
		);

		try {
			$contactId = $this->getClient()->addCon($params);
			$this->getClient()->campAssign($contactId, $list);
		} catch (Exception $e) {
			error_log('Exception while doing optin: ' . print_r($e->getMessage(), true));
		}
		
		return true;
	}

	public function getLists()
	{
		return $this->getClient()->getWebFormMap();
	}

	public function getData()
	{
		$data = array(
			'lists' => array()
		);

		/*
		 * List parsing
		 */
		$lists = $this->getLists();
		if (count($lists) > 0) {
			foreach ($lists as $key => $name) {
				$formData = $this->parseHtmlForm($key);
				$data['lists'][$key] = array('name' => $name, 'fields' => $formData['fields'], 'action' => $formData['action'], 'hidden' => $formData['hidden']);
			}
		}

		return $data;
	}

	public function getItems()
	{
		$data = array(
			'lists' => array()
		);

		/*
		 * List parsing
		 */
		$lists = $this->getLists();
		if (count($lists) > 0) {
			foreach ($lists as $key => $name) {
				$data['lists'][$key] = array('name' => $name);
			}
		}

		return $data;
	}

	public function getListFields($listId)
	{
		return $this->parseHtmlForm($listId);
	}

	public function getFollowUpSequences()
	{
		$sequences = array();

		$data = $this->getClient()->dsQuery('Campaign', 100, 0, array('Id' => '%'), array('Id', 'Name'));
		if (count($data) > 0) {
			foreach ($data as $row) {
				$sequences[$row['Id']] = array('name' => $row['Name']);
			}
		}

		return array('lists' => $sequences);
	}

	public function isEnabled()
	{
		if (false !== $this->accountId && false !== $this->apiKey) {
			return true;
		} else {
			return false;
		}
	}

	protected function parseHtmlForm($id)
	{
		$data = array('action' => '', 'fields' => array(), 'hidden' => array());

		$doc = new DOMDocument();
		if ($doc->loadHTML($this->getClient()->getWebFormHtml($id))) {
			$xpath = new DOMXPath($doc);
			$form = $xpath->query('//form');
	    	if ($form->length > 0) {
	   			$data['action'] = $form->item(0)->getAttribute('action');
	   			$inputs = $xpath->query('//input');
	   			foreach ($inputs as $input) {
	   				if ('hidden' === $input->getAttribute('type')) {
	   					$data['hidden'][esc_attr($input->getAttribute('name'))] = $input->getAttribute('value');
	   				} elseif ('inf_field_Email' !== esc_attr($input->getAttribute('name'))) {
	   					$data['fields'][esc_attr($input->getAttribute('name'))] = $input->getAttribute('name');
	   				}
	   			}
	    	} 	
		}	

		return $data;
	}
}