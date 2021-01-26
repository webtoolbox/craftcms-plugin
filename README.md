<p><a href="https://www.websitetoolbox.com/"><img src ="https://github.com/webtoolbox/craftcms-plugin/blob/master/src/wt_logo_blue.svg" width="450" height="100"></a></p> 
# Website Toolbox Forums plugin for Craft CMS 3.x

## About Website Toolbox Forum  
  Your Craft forum doesn’t need to be basic. Website Toolbox is a cloud-based forum plugin that allows you to easily add a powerful, intuitive, 
  and maintenance-free forum to Craft without slowing it down. No database, servers, or coding required.

### Plugin Features
* Embedded Forum : The forum is seamlessly embedded into the layout of your Craft website.
* Single Sign On : Users are automatically signed in to your forum when they sign in to your Craft website.
* Registration Integration : Forum accounts are automatically created for your existing or new Craft users.
![Screenshot](./docs/img/plugin-logo.png)

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
Related: [Website Toolboxforums for Craft 3.x](https://github.com/webtoolbox/craftcms-plugin)

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
1.	Visit the <a href='https://www.websitetoolbox.com/tool/members/mb/settings?tab=Single%20Sign%20On'>Single Sign On Section</a>.       	
2.	Add your website Log In, Log Out and Sign Up links.
<img src="https://github.com/webtoolbox/craftcms-plugin/blob/master/docs/img/SSO-section.png" />

### For Embedded Forum Front-End Template   
<div id ="embedded_template">

	Add below code to website’s Forum template page.
	<body><div id="embedForum"></div></body>
	Exclude the <body> tag if it’s already included in template header.
</div>

## Steps to Create Menu Item For Forum    
1)	[Non-Embedded Forum Menu](#non-embdded) 
2)	[Embedded Forum Template Menu](#embdded)   


<div id="non-embdded">  

### For Non-Embedded Forum Menu   
</div>
## Installation

1)	Go to Admin > Dashboard > Settings > Fields > New Field  
(For more details please visit. https://craftcms.com/docs/2.x/fields.html#translatable-fields)  
2)	Select Field type as "Matrix".  
<img src="https://github.com/webtoolbox/craftcms-plugin/blob/master/docs/img/unembedded_menu_step1.png" />  
In Configuration section :
To install Website Toolbox Forums, follow these steps:

3)	Add details for "Menu Name"    
<img src="https://github.com/webtoolbox/craftcms-plugin/blob/master/docs/img/unembedded_menu_step2-a.png" />    
4)	Add details for "URL"    
<img src="https://github.com/webtoolbox/craftcms-plugin/blob/master/docs/img/unembedded_menu_step2-b.png" />  

5)	Go to admin > dashboard > settings > Globals > New Global Set    
6)	Add Name for global set.          
7)	Go to Field layout.    
8)	Drag and drop new created field item.       

<img src="https://github.com/webtoolbox/craftcms-plugin/blob/master/docs/img/unembedded_menu_step3.png" />
You can also install Website Toolbox Forums via the **Plugin Store** in the Craft CP.

9)	Go to Admin > dashboard > Globals    
10)	Select the new global set you created.         
11)	Add menu name you want to display and Url for forum (https://forumname.discussion.community).      		

<img src="https://github.com/webtoolbox/craftcms-plugin/blob/master/docs/img/unembedded_menu_step4.png" />  

<div id="embdded"> 

### For Embedded Forum Template Menu     	  
</div>
Website Toolbox Forums works on Craft 3.x.

1.	Follow steps 1 & 2 [Steps to Create menu item to add forum Url](#non-embdded).   
2.	Go to Admin > dashboard > Globals   
3.	Select the new global set you created.        
4.	Add Menu name you want to display.  
5.	Add Url for forum template page created using above [instructions](#embedded_template).     
	i.e. If your template name is "Forum.twig/forum.html". Then template path would be like "/forum".        		
	<img src="https://github.com/webtoolbox/craftcms-plugin/blob/master/docs/img/embeddedforum-step1.png" />     
