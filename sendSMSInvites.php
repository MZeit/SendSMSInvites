<?php
/*
*	This class implements a plugin that extends Limesurvey v.2.6+ 
*	The sendSMSInvites Plugin adds the feature of sending survey invitations to mobiles via SMS
*	To differentiate between the survey invites that should be sent via email and 
*	those to be sent via SMS, an extra attribute (attribute_1) was added with the value NA 
*	for email invites and the recipient's mobile number for SMS invites.
*	It was tested with Limesurvey 2.67.3+170728
*	@author: Mira Zeit
*	@version: 1.0.0
*/
class sendSMSInvites extends \ls\pluginmanager\PluginBase
{
	// Extension Info
	protected $storage = 'DbStorage';
	static protected $description = "Send SMS Functionality";
	static protected $name = 'sendSMSInvites';
	
	protected $plugin_configs = array(
		'google_api_key' => '******************************',
		'SMS_service_url' => '*****************************',
		'SMS_Provider_Username' => '*****',
		'SMS_Provider_Passowrd' => '********'
	);
	
	protected $settings =array(
		'EnableSendSMS' => array(
			'type' => 'select',
			'options'=>array(
				0=>'No',
				1=>'Yes'
			),
			'default'=>0,
			'label' => 'Enable sending SMS invites to mobiles?',
			'help'=>'Overwritable in each Survey setting',
		),
		'MessageBody'=>array(
			'type'=>'text',
			'label'=>'Enter the message body to be sent to survey participant\'s mobile:',
			'help' =>'You may use the placeholders {FIRSTNAME}, {LASTNAME} and {SURVEYURL}.',
			'default'=>"Dear {FIRSTNAME} {LASTNAME}, \n We invite you to participate in the survey below: \n {SURVEYURL} \n Survey Team",
		)
	);
	
	// Register custom function/s
	public function init()
	{
		// Settings to display errors for better debugging
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
		error_reporting(E_ALL);

		$this->subscribe('beforeTokenEmail');
		$this->subscribe('beforeSurveySettings');
		$this->subscribe('newSurveySettings');
	}
	/**
	* This function handles sending SMS messages
	* If it's an email invite, it doesn't interfere and keeps the settings as they are 
	*/
	public function beforeTokenEmail()
	{
		// First we need to check if the sendEmailService is enabled by the admin for this specific survey
		$oEvent = $this->getEvent();
		$surveyId = (string)$oEvent->get('survey');
		$isPluginEnabled = $this->get('EnableSendSMS','survey',$surveyId);
				
		if(strcmp($isPluginEnabled,'1')==0){
			
			$ourTokenData = $this->event->get("token");			
			$mobile = (string)$ourTokenData->attribute_1;
			$type_of_email = $oEvent->get("type");
			if(strcmp($type_of_email,'invitation')==0 or strcmp($type_of_email,'reminder')==0 ){
			
				//Next we check if this token should be sent via email or SMS
				if(strcmp($mobile,'NA')!=0){

					// disable sending email for this token and send SMS
					$this->event->set("send",false);

					// we get the token data and prepare the survey link 
					$SMS_message = $this->get('MessageBody','survey',$surveyId);	// The MessageBody entered by the admin
					$participantToken = $ourTokenData->token;
					$participantFirstName = (string)$ourTokenData->firstname;
					$participantLastName = (string)$ourTokenData->lastname;
					$surveyLink = 'http://'. $_SERVER['SERVER_NAME'] . '/index.php/survey/index/sid/' . $surveyId . '/token/' . $participantToken;		

					$api_url = "https://www.googleapis.com/urlshortener/v1/url?key=". $this->plugin_configs['google_api_key']; 
					$shorten_parameters = array("longUrl" => $surveyLink);
					$content_type = "Content-Type:application/json";
					$jsonrequest = json_encode($shorten_parameters);
					$short_URL="";			

					$response = $this->httpPost($api_url,$jsonrequest,$content_type);				
					$decoded_response = json_decode($response);

					if (json_last_error() == JSON_ERROR_NONE){
						$short_URL=$decoded_response->{'id'};
					}
					else {
					   print "Failed to connect to Google URL Shortener API.";
					   $short_URL=$surveyLink;
					   //exit(1);
					}

					// Setting up the default SMS message in case the admin left it empty.
					if(empty($SMS_message)){
						$SMS_message = "Dear {FIRSTNAME} {LASTNAME}, \n We invite you to participate in the survey: \n {SURVEYURL} \n Survey Team";
					}

					// Replacing the placeholders in the Admin message, so as to have the participant's data.
					$SMS_message_with_Replacement = str_replace("{FIRSTNAME}",$participantFirstName,$SMS_message);
					$SMS_message_with_Replacement = str_replace("{LASTNAME}",$participantLastName,$SMS_message_with_Replacement);
					$SMS_message_ready_to_be_sent = str_replace("{SURVEYURL}",$short_URL,$SMS_message_with_Replacement);

					// setting up the connection with SMS Service Provider then sending SMS msg
					$SMS_service_url= "SMS_Provider_API";
					$parameters=array("username" => $this->plugin_configs['SMS_Provider_Username'], "password" => $this->plugin_configs['SMS_Provider_Passowrd'], "to" => $mobile, "text"=>$SMS_message_ready_to_be_sent);
					$query_parameters=http_build_query($parameters);
					$result_of_post = $this->httpPost($this->plugin_configs['SMS_service_url'],$query_parameters);
					if($result_of_post === FALSE){
						echo("SMS not sent. Please contact the administrator at survey_admin@xyz.com");
					}
				}
			}
		}else{} // The SendSMSPlugin is not enabled. Don't change anything!	
	}
	
	/**
	* 	This function handles sending the http request. 
	*   Proxy settings should be configured. 
	* 	The third argument (request_header) is optional
	*   returns the response from the external page/API
	*/
	private function httpPost($request_url,$request_params,$request_header=null)
	{	
		$curlHandle    = curl_init();	
		$proxy = 'PROXY_URL:PORT_NUMBER';		// These settings needs to be changed !!
		$proxyauth = 'USERNAME:PASSWORD';
		
		curl_setopt($curlHandle, CURLOPT_URL, $request_url);
		curl_setopt($curlHandle, CURLOPT_POST, true);
		curl_setopt($curlHandle, CURLOPT_HEADER, false);
		curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $request_params);
		curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
		
		if(!is_null($request_header))
		{
			curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array($request_header));
		}
		
		curl_setopt($curlHandle, CURLOPT_PROXY, $proxy);
		curl_setopt($curlHandle, CURLOPT_PROXYUSERPWD, $proxyauth);

		$response = curl_exec($curlHandle);
		curl_close($curlHandle);
		
		return $response;
	}
	
	/**
     * This event is fired by the administration panel to gather extra settings
     * available for a survey. These settings override the global settings.
     * The plugin should return setting meta data.
     * @param PluginEvent $event
     */
    public function beforeSurveySettings()
    {
        $event = $this->event;
        $event->set("surveysettings.{$this->id}", array(
            'name' => get_class($this),
            'settings' => array(
                'EnableSendSMS' => array(
			'type' => 'select',
			'options'=>array(
				0=>'No',
				1=>'Yes'
			),
			'default'=>0,
			'label' => 'Enable sending SMS invites to mobiles?',
                    	'current' => $this->get('EnableSendSMS', 'Survey', $event->get('survey'), $this->get('EnableSendSMS',null,null,$this->settings['EnableSendSMS']['default'])),
                ),
                'MessageBody'=>array(
			'type'=>'text',
			'label'=>'Enter the message body to be sent to survey participant\'s mobile:',
			'help' =>'You may use the placeholders {FIRSTNAME}, {LASTNAME} and {SURVEYURL}.',
			'default'=>"Dear {FIRSTNAME} {LASTNAME}, \n We invite you to participate in the survey below: \n {SURVEYURL} \n Survey Team",
			'current' => $this->get('MessageBody', 'Survey', $event->get('survey'),$this->get('MessageBody',null,null,$this->settings['MessageBody']['default'])),
		)
            )
         ));
    }
	
	public function newSurveySettings()
    {
        $event = $this->event;
        foreach ($event->get('settings') as $name => $value)
        {
            /* In order use survey setting, if not set, use global, if not set use default */
            $default=$event->get($name,null,null,isset($this->settings[$name]['default'])?$this->settings[$name]['default']:NULL);
            $this->set($name, $value, 'Survey', $event->get('survey'),$default);
        }
    }		
}
?>
