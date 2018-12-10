# SendSMSInvites
A Limesurvey Plugin that adds the option to send survey invitations via SMS as well as emails.

## The Purpose

This plugin can be used in the case where the email field is missing/NA for some survey recipients, while their mobile numbers are available. In the token list preparation, it is essential to create “Dummy” email accounts for those recipients with no emails so that they can be included in the mailing list. Furthermore, we add an extra attribute with the name of your choice, for example ‘Mobile Number’. This extra attribute will be filled with the mobile number for the invitations to be sent via SMS, and “NA” for those to be sent by email as shown in the following example:

```
firstname	| lastname | email			| Mobile Number
---------------------------------------------------------------------------
John 		| Smith	   | valid_email@domain.com	| NA
Mary		| Anderson | RandomEmail@something.net	| 0099123456789

```

Note: The mobile number needs to be in the 1st extra attribute for the plugin to work properly.

## Getting Started

### Prerequisites

* Limesurvey Version 3.15
* An account at an SMS Gateway which provides a HTTP/HTTPS interface to interact with the plugin via HTTP post requests. 

### SMS Gateway set Up
The SMS Gateway account credentials should be saved in the pluginConfig.php file, depending on the gateway of your choice, the authentication method for your account can be a username & password, OAuth key or an API key. Some changes in line 116 might be needed accordingly. Read the documentation of the Gateway HTTP request to ensure compatibility.

### Installation

In order to install this plugin:
1.	Download the php file and save it locally
2.  Purchase credit from a SMS Gateway and edit the pluginConfig.php file with the credentials.
3. Get a Google URL Shortener API key, which is essential to minimize the cost per SMS to be sent, since the survey url are usually long. The Google API key should be save in the pluginConfig.php file.
4.	Create a folder in the directory plugins located at your Limesurvey server, the folder created has to have the same name as the plugin.
5.	Place the php file in the folder created.
6.	After refreshing the admin page, activate the plugin from Configuration -> Plugin Manager Panel.

### Plugin Settings

This plugin includes two settings; EnableSendSMS and MessageBody. These settings can be set globally from the Plugin Manager -> (sendSMSInvites) -> Configure. The EnableSendSMS is set by default to No, this can be overridden on the survey level from the survey settings. The MessageBody setting gives the survey admin the space to write the SMS that will be sent to the recipients. 

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details

## Acknowledgments

* Stefan Verweij – [Creating Limesurvey Plugins](https://medium.com/@evently/creating-limesurvey-plugins-adcdf8d7e334)
