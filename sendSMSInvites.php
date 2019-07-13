<?php
/*
*	This class implements a plugin that extends Limesurvey v.3.15+ 
*	The sendSMSInvites Plugin adds the feature of sending survey invitations to mobiles via SMS
*	To differentiate between the survey invites that will be sent via email and 
*	those to be sent via SMS, an extra attribute (attribute_1) needs to be added with the value NA 
*	for email invites and the recipient's mobile number for SMS invites.
*	It is tested with Limesurvey Version 3.15.0+181008
*	@author: Mira Zeit
*	@version: 2.0.1
*/
class sendSMSInvites extends \LimeSurvey\PluginManager\PluginBase
{
	// Extension Info
	protected $storage = 'DbStorage';
	static protected $description = "Send SMS Functionality";
	static protected $name = 'sendSMSInvites';
	
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
		//Import Plugin configs
		$config_path = (string)$this->getDir() . '\pluginConfig.php';
		$plugin_configs = include($config_path);
		
		$oEvent = $this->getEvent();
		$surveyId = (string)$oEvent->get('survey');
		$typeOfEmail = $oEvent->get("type");

		// Before changing any settings we need to check that:
			// 1. the sendSMSService is enabled by the admin for this specific survey
		$pluginEnabled = strcmp($this->get('EnableSendSMS','survey',$surveyId),'1')==0;
			// 2. Check the type of email, invitaiton or reminder => send, confirmation just ignore (not included in my plans :/).
		$vaildEmailType = (((strcmp($typeOfEmail,'invitation')==0) or (strcmp($typeOfEmail,'reminder')==0)) or (strcmp($typeOfEmail,'confirm')==0));
		$ourTokenData = $oEvent->get("token");
		if($pluginEnabled and $vaildEmailType){
			// Then we need to check if the admin added an extra attribute
			if(isset($ourTokenData['attribute_1'])){
				// 3. This invite should be send via SMS and not to the Email account
				$mobile = (string)$ourTokenData['attribute_1'];
				if(strcmp($mobile,'NA')!=0 and !empty($mobile)){
					// disable sending email for this token and send SMS
					$this->event->set("send",false);

					// we get the token data and prepare the survey link 
					$SMS_message = $this->get('MessageBody','survey',$surveyId);	// The MessageBody entered by the admin
					$participantToken = $ourTokenData['token'];
					$participantFirstName = (string)$ourTokenData['firstname'];
					$participantLastName = (string)$ourTokenData['lastname'];
					$surveyLink = 'http://'. $_SERVER['SERVER_NAME'] . '/index.php/survey/index/sid/' . $surveyId . '/token/' . $participantToken;		

					/* This part will no longer be used - (Optional)
					$api_url = "https://www.googleapis.com/urlshortener/v1/url?key=". $plugin_configs['google_api_key']; 
					$shorten_parameters = array("longUrl" => $surveyLink);
					$content_type = "Content-Type:application/json";
					$jsonrequest = json_encode($shorten_parameters);
					$short_URL="";			

					$response = $this->httpPost($api_url,$jsonrequest,$content_type);				
					$decoded_response = json_decode($response);

					if (json_last_error() == JSON_ERROR_NONE){
						$short_URL=$decoded_response->{'id'};
					}
					if(!$response){
					   print "Failed to connect to Google URL Shortener API.";
					   $short_URL=$surveyLink;
					}*/

					// Setting up the default SMS message in case the admin left it empty.
					if(empty($SMS_message)){
						$SMS_message = "Dear {FIRSTNAME} {LASTNAME}, \n We invite you to participate in the survey: \n {SURVEYURL} \n Survey Team";
					}

					// Replacing the placeholders in the Admin message, so as to have the participant's data.
					$SMS_message_with_Replacement = str_replace("{FIRSTNAME}",$participantFirstName,$SMS_message);
					$SMS_message_with_Replacement = str_replace("{LASTNAME}",$participantLastName,$SMS_message_with_Replacement);
					$SMS_message_ready_to_be_sent = str_replace("{SURVEYURL}",$surveyLink,$SMS_message_with_Replacement);
					
					// Since I don't want to send confirmation SMS, only for reminder and confirmation
					if((strcmp($typeOfEmail,'invitation')==0) or (strcmp($typeOfEmail,'reminder')==0)){
						// setting up the connection with SMS Service Provider then sending SMS msg
						$query_parameters=http_build_query(array("username" => $plugin_configs['SMS_Provider_Username'], "password" => $plugin_configs['SMS_Provider_Passowrd'], "to" => $mobile, "text"=>$SMS_message_ready_to_be_sent));
						$result_of_post = $this->httpPost($plugin_configs['SMS_service_url'],$query_parameters);
						if($result_of_post === FALSE){
							echo("SMS not sent. Please contact the administrator at survey_admin@xyz.com");
							exit;
						}
					}else{}	// Confirmation don't want to send --> change this if you want to enter message and send confirmation SMS
				}
			}else{
				echo("sendSMSInvites Plugin is enabled. If you do not wish to send SMS invitations, disable it. If you intend to use it, the SMS was not sent. Please add an extra attribute with the mobile number or NA for emails.");
				exit;
			}
		}else{} // The SendSMSPlugin is not enabled. Don't change anything!	
	}
	
	/**
	*  This function handles sending the http request. 
	*  Proxy settings should be configured. 
	*  The third argument (request_header) is optional
	*  returns the response from the external page/API
	*/
	private function httpPost($request_url,$request_params,$request_header=null)
	{	
		$curlHandle    = curl_init();	
		$proxy = 'PROXY_URL:PORT_NUMBER';	// These settings needs to be changed !!
		$proxyauth = 'USERNAME:PASSWORD';	// If there is no proxy delete these lines
		
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
