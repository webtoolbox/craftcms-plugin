<p><a href="https://www.websitetoolbox.com/"><img src ="https://github.com/webtoolbox/craftcms-plugin/blob/master/src/wt_logo_blue.svg" width="450" height="100"></a></p> 

# Website Toolbox Forum plugin for Craft CMS 3.x       

# About Website Toolbox Forum  
  Your CraftCMS forum doesn’t need to be basic. Website Toolbox is a cloud-based forum plugin that allows you to easily add a powerful, intuitive, 
  and maintenance-free forum to CraftCMS without slowing it down. No database, servers, or coding required.
  
### PLUGIN FEATURES
* Embedded forum: The forum is seamlessly embedded into the layout of your WordPress website.
* Single sign on: Users are automatically signed in to your forum when they sign in to your WordPress website.
* Registration integration: Forum accounts are automatically created for your existing or new WordPress users.

### KEY FORUM BENEFITS
* Instant setup
* Phone/Chat/Email support
* SEO and mobile friendly
* Fully customizable
* Public or Private
* White label

## KEY FORUM FEATURES
* Make money using ads or subscription fees
* Facebook integration
* Instant messaging
* Chat room
* Share files, photos, and videos
* “Like” posts
* User reputation
* Easy theme editor
* Announce and organize events
* Create polls
* Set user permissions
* Moderate content and users
* Import or export your data

## Requirements
This plugin requires Craft CMS 3.0.0 or later.   
## Installation
	
### Plugin Store  
 Log into your control panel, hit up the 'Plugin Store', search for this plugin and install.
 
### Composer:
Open terminal, go to your Craft project folder and use composer to load this plugin. Once loaded you can install via the Craft Control Panel, go to 
Settings → Plugins, locate the plugin and hit “Install”.

	cd /path/to/project
	composer require websitetoolbox/websitetoolboxforum   

## Configuring Website Toolbox Forum
1.	<a href="https://www.websitetoolbox.com/">Create a Website Toolbox forum here.</a>    
2.	Go to your Website Toolbox forum Settings. Dashboard > Settings > Website Toolbox Forum    
	Add Login Credentials received from Website Toolbox :      
	
	<img src="https://github.com/webtoolbox/craftcms-plugin/blob/master/docs/img/settings.jpg" />
#### Review the plugin settings and click the Save button.       
	
<img src="https://github.com/webtoolbox/craftcms-plugin/blob/master/docs/img/update-settings.jpg" />				
 

3.	Click on <a href='https://www.websitetoolbox.com/tool/members/mb/settings?tab=Single%20Sign%20On'>Single Sign On Section</a> link.       

<img src="https://github.com/webtoolbox/craftcms-plugin/blob/master/docs/img/SSO.png" />
	It will redirect to forum settings page. Please add below details from Craft Website.
<img src="https://github.com/webtoolbox/craftcms-plugin/blob/master/docs/img/SSO-section.png" />

## Forum Front-End Templates
1)	[Chapter 1](#Chapter1) Non-Embedde Forum 
### For Non-Embedded Forum:
Website Toolbox Forums(Un-Embedded Single Sign On (SSO)).
<div id="Chapter1">### Steps to Create menu item to add forum Url:</div>

1)	Go to Admin > Dashboard > Settings > Fields > New Field
	- Fill the details like name, handle  
(For more details please visit. https://craftcms.com/docs/2.x/fields.html#translatable-fields)
	- Select Field type as "Matrix" 
			<img src="https://github.com/webtoolbox/craftcms-plugin/blob/master/docs/img/unembedded_menu_step1.png" />  
	- In Configuration section:  	
		- Add asked details to create menu for forum link:  
		Example:  
		- New block type: Link  
		- New field:  
		Name: Forum (Name of the menu) , Field type: Plain Text  
		<img src="https://github.com/webtoolbox/craftcms-plugin/blob/master/docs/img/unembedded_menu_step2-a.png" />  
		Name: Forum Url (Menu Url) , Field type: Plain Text
		<img src="https://github.com/webtoolbox/craftcms-plugin/blob/master/docs/img/unembedded_menu_step2-b.png" />  
		Save these 2 fields.

2)	Go to admin > dashboard > settings > Globals > New Global Set   
		- Add Name for global set       
		- In Field layout drag and drop new created field item.       
		<img src="https://github.com/webtoolbox/craftcms-plugin/blob/master/docs/img/unembedded_menu_step3.png" />

3) 	Go to Admin > dashboard > Globals > Select the new global set you created.      
		- Add Menu name you want to display and Url for forum (https://forumname.discussion.community).      		
		<img src="https://github.com/webtoolbox/craftcms-plugin/blob/master/docs/img/unembedded_menu_step4.png" />  

### For Embedded Forum:
Front-End Template Code for Website Toolbox Forums (Embedded Single Sign On (SSO))
Your Website Toolbox  Forum template can look something like this:
	Add below code to website’s Forum page.
	<body><div id="embedForum"></div></body>
	Exclude <body> tag if it’s already included in template header.
	
### Steps to Create menu item to point embedded forum page:  
Follow steps 1 & 2 below "Steps to Create menu item to add forum Url".  
Go to Admin > dashboard > Globals > Select the new global set you created.      
	- Add Menu name you want to display and Url for forum template page created using above instructions.   
	i.e. If your template name is "Forum.twig/forum.html". Then template path would be like "/forum".     		
	<img src="https://github.com/webtoolbox/craftcms-plugin/blob/master/docs/img/embeddedforum-step1.png" />   

   
