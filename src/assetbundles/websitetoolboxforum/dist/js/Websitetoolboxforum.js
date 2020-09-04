/**
 * Website Toolbox Forum plugin for Craft CMS
 *
 * Website Toolbox Forum JS
 *
 * @author    Website Toolbox 
 * @copyright Copyright (c) 2019 Website Toolbox
 * @link      https://websitetoolbox.com/
 * @package   Website Toolbox Forum
 * @since     3.0.0
 */
 
 function getCookie(){
 	var i,x,y,ARRcookies=document.cookie.split(";");
    for (i=0;i<ARRcookies.length;i++) {
        x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
        y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
        x=x.replace(/^\s+|\s+$/g,"");
        if (x=='forumLogoutToken') {
            var forumToken = y;          
        }
    }
    return forumToken;
 }
