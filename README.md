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
1.	Download the `php files` as well as the `config.xml` and save it locally
2.  Purchase credit from a SMS Gateway and edit the `pluginConfig.php` file with the credentials.
3.	Create a folder in the directory plugins located at your Limesurvey server, the folder created has to have the same name as the plugin.
4.	Place the `php files` as weel as the `config.xml` in the folder created.
5.	After refreshing the admin page, activate the plugin from Configuration -> Plugin Manager Panel.
6. Optional Step, if you wish to shorten the survey URL in the SMS, uncomment the lines 86 - 102. Then, create an account at a service that shortens URLs (like Bitly, ...) and change the code accordingly to set it up. 

### Plugin Settings

This plugin includes two settings; EnableSendSMS and MessageBody. These settings can be set globally from the Plugin Manager -> (sendSMSInvites) -> Configure. The EnableSendSMS is set by default to No, this can be overridden on the survey level from the survey settings. The MessageBody setting gives the survey admin the space to write the SMS that will be sent to the recipients. 

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details

## Acknowledgments

* Stefan Verweij – [Creating Limesurvey Plugins](https://medium.com/@evently/creating-limesurvey-plugins-adcdf8d7e334)
