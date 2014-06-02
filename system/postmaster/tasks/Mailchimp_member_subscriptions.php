<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mailchimp_member_subscriptions_postmaster_task extends Base_task {
	
	protected $title = 'Mailchimp Member Subscriptions';
	
	protected $hooks = array(
		array(
			'hook'   => 'entry_submission_end',
			'method' => 'entry_submission_end',
			'priority' => 2
		)
	);
		 	 
	protected $fields = array(
		
		'api_key' => array(
			'label' => 'API Key',
			'id'	=> 'api_key',
			'description' => 'Your Mailchimp API Key'
		),

		'list_id' => array(
			'label' => 'List ID',
			'id'	=> 'list_id',
			'description' => 'Your Mailchimp List ID'
		),

		'group_id' => array(
			'label' => 'Group ID',
			'id'	=> 'group_id',
			'description' => 'The Group ID used to segment your subscriptions. If this field is left blank, a group will be automatically created.'
		),
	);

	protected $default_settings = array(
		'api_key' => '',
		'list_id' => '',
		'group_id' => '',
	);
		
	public function __construct()
	{
		parent::__construct();
		
	}
	
	public function entry_submission_end($entry_id, $meta, $data)
	{
		$settings = $this->get_settings();

		if(!empty($settings->api_key) && !empty($settings->list_id))
		{
			$entry = $this->channel_data->get_channel_entry($entry_id)->row();
			$member = $this->channel_data->get_member($meta['author_id'])->row();
			$service = $this->EE->postmaster_lib->load_service('Mailchimp');

			if(empty($settings->group_id))
			{
				$group_id = $service->create_group(
					$settings->api_key, 
					$settings->list_id, 
					'Individual Subscriptions',
					array(
						$member->email,
					)
				);

				if(!is_int($group_id))
				{
					ee()->output->fatal_error($group_id->error);
				}

				$settings->group_id = $group_id;

				$this->task['settings'] = json_decode($this->task['settings']);

				$this->task['settings']->{$this->name} = $settings;

				$this->task['settings'] = json_encode($this->task['settings']);

				$this->EE->postmaster_model->edit_task($this->task['id'], $this->task);
			}
			else
			{
				$grouping = $service->add_grouping(
					$settings->api_key, 
					$settings->list_id, 
					$member->email,
					$settings->group_id
				);
			}
		}

		return $data;
	}
}