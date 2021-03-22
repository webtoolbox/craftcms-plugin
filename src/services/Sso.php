<?php
/**
 * Website Toolbox Forum plugin for Craft CMS 3.x
 * Single Sign On Cloud Based plugin for CraftCMS
 * @link      https://websitetoolbox.com/
 * @copyright Copyright (c) 2020 Website Toolbox
 */
namespace websitetoolbox\websitetoolboxforum\services;
use websitetoolbox\websitetoolboxforum\models\Settings;
use websitetoolbox\websitetoolboxforum\Websitetoolboxforum;
use Craft;
use craft\base\Component;
use craft\web\View;
use craft\services\Config;
define('WT_API_URL', 'https://api.websitetoolbox.com/v1/api');
/**
 * @author    Website Toolbox
 * @package   Websitetoolboxforum
 * @since     3.0.0
 */
class Sso extends Component
{    
     function afterLogin(){  
          $token = Craft::$app->getSession()->get(Craft::$app->getUser()->tokenParam); 
          if($token){ 
               $forumApiKey = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumApiKey');
               $forumUrl    = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumUrl');
               if($forumApiKey){ 
                    $myUserQuery  = \craft\elements\User::find();
                    $userEmail    = Craft::$app->getUser()->getIdentity()->email;
                    $userId       = Craft::$app->getUser()->getIdentity()->id;
                    $userName     = Craft::$app->getUser()->getIdentity()->username;
                    $RequestUrl   = $forumUrl."/register/setauthtoken";
                    $postData     = array('type'=>'json','apikey' => $forumApiKey, 'user' => $userName,'email'=>$userEmail,'externalUserid'=>$userId);
                    $result       = Websitetoolboxforum::getInstance()->sso->sendApiRequest('POST',$RequestUrl,$postData,'json'); 
                    setcookie("forumLogoutToken", $result->authtoken, time() + 3600,"/");
                    setcookie("forumLoginUserid", $result->userid, time() + 3600,"/");
              }
          }         
     }
     function afterUserCreate($userId){ 
        $forumUrl     = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumUrl',false);
        $forumApiKey  = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumApiKey',false); 
        if(!isset($_POST['newPassword'])){
            if(isset($_POST['username'])){
                $userName     = $_POST['username'];
            }
            if(isset($_POST['email'])){
                $userEmail    = $_POST['email'];
            }
            $postData     = array( 
                              'type'=>'json',
                              'apikey'          => $forumApiKey,
                              'member'          => $userName,
                              'externalUserid'  => $userId, 
                              'email'           => $userEmail);
            if(isset($_POST['firstName'])){
               $postData['name']  =  $_POST['firstName'];
            }
            if(isset($_POST['lastName'])){
               $postData['name'] .=  " ".$_POST['lastName'];
            }        
            $RequestUrl           = $forumUrl . "/register/create_account/";
            $result               = Websitetoolboxforum::getInstance()->sso->sendApiRequest('POST',$RequestUrl,$postData,'json');      
            $RequestUrl           = $forumUrl."/register/setauthtoken";
            $postData             = array('type'=>'json','apikey' => $forumApiKey, 'user' => $userName,'email'=>$userEmail,'externalUserid'=>$userId);
            $result               = Websitetoolboxforum::getInstance()->sso->sendApiRequest('POST',$RequestUrl,$postData,'json'); 

            setcookie("forumLogoutToken", $result->authtoken, time() + 3600,"/");
            setcookie("forumLoginUserid", $result->userid, time() + 3600,"/");  
        }
    }
    function afterUpdateUser(){
      $emailToVerify  = $_SESSION['userEmailBeforeUpdate'];
      $userId         = Websitetoolboxforum::getInstance()->sso->getUserid($emailToVerify);
      $userName       = $_POST['username'];
      $externalUserid = $_POST['userId'];
      $email          = $_POST['email'];
      if(isset($_POST['firstName'])){
           $fullName  =  $_POST['firstName'];
      }
      if(isset($_POST['lastName'])){
         $fullName .=  " ".$_POST['lastName'];
      }
      $userDetails    = array(
                          "type"=>"json",
                          "email"          => $email,
                          "username"       => $userName,
                          "externalUserid" => $externalUserid,
                          "name"           => $fullName);
      $url            = WT_API_URL ."/users/$userId"; 
      $response       = Websitetoolboxforum::getInstance()->sso->sendApiRequest('POST',$url,$userDetails,'json','forumApikey');
    }
    function getUserid($userEmail){         
         if ($userEmail) {
            $data     = array(
                           "email" => $userEmail);
            $url      = WT_API_URL . "/users/";
             
            $response = Websitetoolboxforum::getInstance()->sso->sendApiRequest('GET', $url, $data,'json','forumApikey');          
               
            if ($response->{'data'}[0]->{'userId'}) {
                 return $response->{'data'}[0]->{'userId'};
            } 
        }
    }
    function sendApiRequest($method, $url, $requestData, $postType, $apiKey=''){               
        if ($method == "GET") {
            $url = sprintf("%s?%s", $url, http_build_query($requestData));
        }
        $curl         = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        echo $forumApiKey  = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumApiKey',false);
        if($apiKey != ''){ 
            $headers = array(
                          "x-api-key: " . $forumApiKey,
                          'Content-Type: application/json',);                       
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers );
        }else{
            $headers = array(                                                                          
                          'Content-Type: application/json',
                          'Accept: application/json');
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);    
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if ($method == "POST") {
            curl_setopt($curl, CURLOPT_POST, true);
            if($postType == 'json'){
              curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($requestData));
            }else{
              curl_setopt($curl,CURLOPT_POSTFIELDS,http_build_query($requestData));
            }
        } elseif ($method == "GET") {
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        }
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response);
    }
   function afterDeleteUser($userName){        
        $forumApiKey  = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumApiKey',false);
        $forumUrl     = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumUrl',false);
        $postData     = array(
                          'type'      =>'json',
                          'apikey'    => $forumApiKey,
                          'massaction'=> 'decline_mem',
                          'usernames' => $userName);
        $RequestUrl   =  $forumUrl."/register";
        $result       = Websitetoolboxforum::getInstance()->sso->sendApiRequest('POST',$RequestUrl,$postData,'json');
    }
    function afterLogOut(){  
      if(isset($_COOKIE['forumLogoutToken'])){
        $forumUrl     = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumUrl',false);
        echo '<img src='.$forumUrl.'"/register/logout?authtoken='.$_COOKIE['forumLogoutToken'].'" border="0" width="0" height="0" alt="" id="imageTag">';  
        Websitetoolboxforum::getInstance()->sso->resetCookieOnLogout();  
      }
    }
    function resetCookieOnLogout(){
      setcookie('forumLogoutToken', 0, time() - 3600, "/");
      setcookie('forumLoginUserid', '', time() - 3600, "/");
      setcookie('loginRemember', '', time() - 3600, "/");
   }
   function renderJsScriptEmbedded($forumUrl){ 
        if(isset($_COOKIE['forumLogoutToken'])){
            $cookieForumLogoutToken = $_COOKIE['forumLogoutToken'];
        }else{
            $cookieForumLogoutToken = 0;
        }
        $js = <<<JS
      (  
       function renderEmbeddedHtmlWithAuthtoken()
      {  var embedUrl  = "{$forumUrl}";   
         var cookieForumLogoutToken = "{$cookieForumLogoutToken}";
        var wtbWrap = document.createElement('div');
        wtbWrap.id = "wtEmbedCode";
        var embedScript = document.createElement('script');
        embedScript.id = "embedded_forum";
        embedScript.type = 'text/javascript';
        if(typeof cookieForumLogoutToken != 'undefined' && cookieForumLogoutToken != 0){
          embedUrl += "/js/mb/embed.js?authtoken="+cookieForumLogoutToken;
        } else{
          embedUrl += "/js/mb/embed.js";
        }
        embedScript.src = embedUrl; 
        wtbWrap.appendChild(embedScript); 
        document.getElementById('embedForum').appendChild(embedScript);

      })();
JS;
      return $js ;
    }   
  function renderJsScriptUnEmbedded(){  
        if(isset($_COOKIE['forumLogoutToken'])){
            $cookieForumLogoutToken = $_COOKIE['forumLogoutToken'];
        }else{
            $cookieForumLogoutToken = 0;
        } 
        $js = <<<JS
        (  
         function renderEmbeddedUnHtmlWithAuthtoken()
        { 
        var forumUrl  = "{$forumUrl}"+"/";  
        var cookieForumLogoutToken = "{$cookieForumLogoutToken}";
        var links = document.getElementsByTagName('a');
        for(var i = 0; i< links.length; i++){
          var str = links[i].href;  
            if(str == forumUrl){  
                var linkToChange = links[i]; 
                if(typeof cookieForumLogoutToken != 'undefined' && cookieForumLogoutToken != 0){
                    linkToChange.setAttribute("href", linkToChange+"?authtoken="+cookieForumLogoutToken);
                }
            }            
        }
        })();
JS;
        return $js ;
    }   
}
