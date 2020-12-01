<p><a href="https://www.websitetoolbox.com/"><img src ="https://github.com/webtoolbox/craftcms-plugin/blob/master/src/wt_logo_blue.svg" width="450" height="100"></a></p> 

# Website Toolbox Forum plugin for Craft CMS 3.x       

## About Website Toolbox Forum  
  Your Craft forum doesn’t need to be basic. Website Toolbox is a cloud-based forum plugin that allows you to easily add a powerful, intuitive, 
  and maintenance-free forum to Craft without slowing it down. No database, servers, or coding required.
  
### Plugin Features
* Embedded Forum: The forum is seamlessly embedded into the layout of your Craft website.
* Single Sign On: Users are automatically signed in to your forum when they sign in to your Craft website.
* Registration Integration: Forum accounts are automatically created for your existing or new Craft users.

### Key Forum Benefits
* Instant setup
* Phone/Chat/Email support
* SEO and mobile friendly
* Fully customizable
* Public or Private
* White label

### Key Forum Features
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
 
### Composer
Open terminal, go to your Craft project folder and use composer to load this plugin. Once loaded you can install via the Craft Control Panel, go to 
Settings → Plugins, locate the plugin and hit “Install”.

	cd /path/to/project
	composer require websitetoolbox/websitetoolboxforum   

## Configuring Website Toolbox Forum
1.	<a href="https://www.websitetoolbox.com/">Create a Website Toolbox Forum.</a>    
2.	Go to your Website Toolbox forum Settings. Dashboard > Settings > Website Toolbox Forum.     
	Add Login Credentials received from Website Toolbox :      
	
<img src="https://github.com/webtoolbox/craftcms-plugin/blob/master/docs/img/settings.jpg" />  

3.	Review the plugin settings and click the Save button.       
	
<img src="https://github.com/webtoolbox/craftcms-plugin/blob/master/docs/img/update-settings.jpg" />				
 
## Configuring Website Toolbox SSO Settings
1.	Go to your Website Toolbox forum Settings. Dashboard > Settings > Website Toolbox Forum.  
2.	Click on <a href='https://www.websitetoolbox.com/tool/members/mb/settings?tab=Single%20Sign%20On'>Single Sign On Section</a> link.       

<img src="https://github.com/webtoolbox/craftcms-plugin/blob/master/docs/img/SSO.png" />
	It will redirect to forum settings page. Please add below details from Craft Website.
<img src="https://github.com/webtoolbox/craftcms-plugin/blob/master/docs/img/SSO-section.png" />

## Forum Front-End Template

### For Embedded Forum:
<div id ="embedded_template">
	Add below code to website’s Forum template page.
	<body><div id="embedForum"></div></body>
	Exclude <body> tag if it’s already included in template header.
</div>
	
## Steps to Create Menu Item For Forum:    
1)	[Non-Embedded Forum Menu](#non-embdded) 
2)	[Embedded Forum Template Menu](#embdded)   

### For Non-Embedded Forum:

<div id="non-embdded"> 

1)	Go to Admin > Dashboard > Settings > Fields > New Field
(For more details please visit. https://craftcms.com/docs/2.x/fields.html#translatable-fields)
	1.	Select Field type as "Matrix".  
			<img src="https://github.com/webtoolbox/craftcms-plugin/blob/master/docs/img/unembedded_menu_step1.png" />  
	2.	In Configuration section:  	
		
		<img src="https://github.com/webtoolbox/craftcms-plugin/blob/master/docs/img/unembedded_menu_step2-a.png" />  
		
		<img src="https://github.com/webtoolbox/craftcms-plugin/blob/master/docs/img/unembedded_menu_step2-b.png" />  


2)	Go to admin > dashboard > settings > Globals > New Global Set   
		1.	Add Name for global set.         
		2.	Go to Field layout.    
		3.	Drag and drop new created field item.       
		
		<img src="https://github.com/webtoolbox/craftcms-plugin/blob/master/docs/img/unembedded_menu_step3.png" />

3) 	Go to Admin > dashboard > Globals 
		1.	Select the new global set you created.      
		2.	Add menu name you want to display and Url for forum (https://forumname.discussion.community).      		
		
		<img src="https://github.com/webtoolbox/craftcms-plugin/blob/master/docs/img/unembedded_menu_step4.png" />  

</div>
### For embedded forum page:  
<div id="embdded"> 	

1.	Follow steps 1 & 2 below "Steps to Create menu item to add forum Url".  
2.	Go to Admin > dashboard > Globals 
3.	Select the new global set you created.      
4.	Add Menu name you want to display.
5.	Add Url for forum template page created using [above instructions](#embedded_template).   
	i.e. If your template name is "Forum.twig/forum.html". Then template path would be like "/forum".     		
	<img src="https://github.com/webtoolbox/craftcms-plugin/blob/master/docs/img/embeddedforum-step1.png" />   
</div>

   
