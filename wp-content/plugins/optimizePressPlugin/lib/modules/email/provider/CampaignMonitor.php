<?php

require_once(OP_MOD . 'email/ProviderInterface.php');
require_once(OP_LIB . 'vendor/campaignmonitor/csrest_general.php');
require_once(OP_LIB . 'vendor/campaignmonitor/csrest_clients.php');
require_once(OP_LIB . 'vendor/campaignmonitor/csrest_lists.php');
require_once(OP_LIB . 'vendor/campaignmonitor/csrest_subscribers.php');

/**
 * Campaign Monitor email integration provider
 * @author Luka Peharda <luka.peharda@gmail.com>
 */
class OptimizePress_Modules_Email_Provider_CampaignMonitor implements OptimizePress_Modules_Email_ProviderInterface
{
	const OPTION_NAME_API_KEY = 'campaignmonitor_api_key';

	/**
	 * @var OP_CS_REST_General
	 */
	protected $client = null;

	/**
	 * @var boolean
	 */
	protected $apiKey = false;

	/**
	 * Initializes client object and fetches API KEY
	 */
	public function __construct()
	{
		/*
		 * Fetching API key from the wp_options table
		 */
		$this->apiKey = op_get_option(self::OPTION_NAME_API_KEY);		
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::subscribe()
	 */
	public function getClient()
	{
		if (null === $this->client) {
			$this->client = new OP_CS_REST_General(array('api_key' => $this->apiKey));
		}

		return $this->client;
	}

	/**
	 * Returns Clients Rest HTTP client
	 * @param  string $id
	 * @return OP_CS_REST_Clients
	 */
	public function getClientsClient($id)
	{
		return new OP_CS_REST_Clients($id, array('api_key' => $this->apiKey));
	}

	/**
	 * Returns Lists Rest HTTP client
	 * @param  string $id
	 * @return OP_CS_REST_Lists
	 */
	public function getListsClient($id)
	{
		return new OP_CS_REST_Lists($id, array('api_key' => $this->apiKey));
	}

	/**
	 * Returns Subscribers Rest HTTP client
	 * @param  string $id
	 * @return OP_CS_REST_Subscribers
	 */
	public function getSubscribersClient($id)
	{
		return new OP_CS_REST_Subscribers($id, array('api_key' => $this->apiKey));
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::getLists()
	 */
	public function getLists()
	{
		$clients = $this->getClient()->get_clients();

		$data = array();

		if ($clients->was_successful()) {
			foreach ($clients->response as $client) {

				$lists = $this->getClientsClient($client->ClientID)->get_lists();
				
				if ($lists->was_successful()) {
					foreach ($lists->response as $list) {
						$data[] = array('id' => $list->ListID, 'name' => $list->Name);
					}	
				}				
			}
		}
		
		return $data;
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::getData()
	 */
	public function getData()
	{
		$data = array(
			'lists' => array()
		);

		/*
		 * List parsing
		 */
		$lists = $this->getLists();
		if ($lists > 0) {
			foreach ($lists as $list) {
				$data['lists'][$list['id']] = array('name' => $list['name'], 'fields' => $this->getFormFields($list['id']));
			}
		}

		return $data;
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::getItems()
	 */
	public function getItems()
	{
		$data = array(
			'lists' => array()
		);

		/*
		 * List parsing
		 */
		$lists = $this->getLists();
		if ($lists > 0) {
			foreach ($lists as $list) {
				$data['lists'][$list['id']] = array('name' => $list['name']);
			}
		}

		return $data;
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::subscribe()
	 */
	public function subscribe($data)
	{	
		if (isset($data['list']) && isset($data['email'])) {

			$mergeVars = $this->prepareMergeVars($data['list']);

			$subscriber = array(
				'EmailAddress' => $data['email'],
				'Name' => op_post('Name') ? op_post('Name') : '-',
				'CustomFields' => $mergeVars,
				'Resubscribe' => true,
				'RestartSubscriptionBasedAutoresponders' => true
			);
			
			$status = $this->getSubscribersClient($data['list'])->add($subscriber);
			
			/*
			 * Displays error
			 */
			if (!$status->was_successful()) {				
				error_log('Error ' . $status->response->Code . ': ' . $status->response->Message);
			}

			return true;
		} else {
			wp_die('Mandatory information not present [list and/or email address].');
		}	
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::register()
	 */
	public function register($list, $email, $fname, $lname)
	{
		$subscriber = array(
			'EmailAddress' => $email,
			'Name' => $fname . ' ' . $lname,
			'CustomFields' => array(),
			'Resubscribe' => true,
			'RestartSubscriptionBasedAutoresponders' => true
		);
		
		$status = $this->getSubscribersClient($list)->add($subscriber);
		
		/*
		 * Displays error
		 */
		if (!$status->was_successful()) {			
			error_log('Error ' . $status->response->Code . ': ' . $status->response->Message);
		}

		return true;
	}

	/**
	 * Searches for possible form fields from POST and adds them to the collection
	 * @param  string $id
	 * @return null|array     Null if no value/field found
	 */
	protected function prepareMergeVars($id)
	{
		$vars = array();
		$allowed = array_keys($this->getFormFields($id));

		foreach ($allowed as $name) {
			if ('Name' !== $name && false !== $value = op_post($name)) {
				$vars[] = array('Key' => $name, 'Value' => $value);
			}
		}

		return $vars;
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::isEnabled()
	 */
	public function isEnabled()
	{
		return $this->apiKey === false ? false : true;
	}

	/**
	 * Returns form fields for given list
	 * @param  string $id
	 * @return array
	 */
	public function getFormFields($id)
	{
		$fields = array('Name' => 'Name');

		$vars = $this->getListsClient($id)->get_custom_fields();
		if ($vars->was_successful()) {
			foreach ($vars->response as $var) {
				$fields[str_replace(array('[', ']'), '', $var->Key)] = $var->FieldName;
			}	
		}

		return $fields;
	}

	/**
	 * @see OptimizePress_Modules_Email_ProviderInterface::getListFields()
	 */
	public function getListFields($listId)
	{
		return array('fields' => $this->getFormFields($listId));
	}
}