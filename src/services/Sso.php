<?php
/**
 * Website Toolbox Forum plugin for Craft CMS 4.x
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
use craft\helpers\UrlHelper;
define('WT_API_URL', 'https://api.websitetoolbox.com/dev/api');
/**
 * @author    Website Toolbox
 * @package   Websitetoolboxforum
 * @since     4.0.0
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
                    $image        = '';
                    if(Craft::$app->getUser()->getIdentity()->photoId != ''){
                        $image = Craft::$app->getUser()->getIdentity()->photo->url;    
                    }
                    $RequestUrl   = $forumUrl."/register/setauthtoken";                    
                    $postData     = array('type'=>'json','apikey' => $forumApiKey, 'user' => $userName,'email'=>$userEmail,'externalUserid'=>$userId, 'avatarUrl' => $image);
                    $result       = Websitetoolboxforum::getInstance()->sso->sendApiRequest('POST',$RequestUrl,$postData,'json');                    
                    setcookie("forumLogInToken", $result->authtoken, time() + 3600,"/");
                    setcookie("forumLogoutToken", $result->authtoken, time() + 3600,"/");
                    setcookie("forumLoginUserid", $result->userid, time() + 3600,"/");
              }
          }         
     }
    function afterUserCreate($user){        
        $forumUrl     = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumUrl',false);
        $forumApiKey  = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumApiKey',false); 
        $userId       =       $user->user->id;
        if(!isset($user->user->newPassword)){
            if(isset($user->user->username)){
                $userName     = $user->user->username;
            }
            if($user->user->email){
                $userEmail    =$user->user->email;
            }
            $postData     = array( 
                              'type'=>'json',
                              'apikey'          => $forumApiKey,
                              'member'          => $userName,
                              'externalUserid'  => $userId, 
                              'email'           => $userEmail);
            if(isset($user->user->firstName)){
               $postData['name']  =  $user->user->firstName;
            }
            if(isset($user->user->lastName)){
               $postData['name'] .=  " ".$user->user->lastName;
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
      if(isset($_POST['fullName'])){
         $fullName =  $_POST['fullName'];
      }
        
       $_SESSION['isUserUpdated'] = true;        // for image upload 
      $userDetails    = array(
                          "type"           => "json",                          
                          "email"          => $email,
                          "username"       => $userName,
                          "externalUserid" => $externalUserid,
                          "name"           => $fullName
                      );
      $url            = WT_API_URL ."/users/$userId";      
      $response       = Websitetoolboxforum::getInstance()->sso->sendApiRequest('POST',$url,$userDetails,'json','forumApikey');
      if(isset($response->status) && $response->status == 'error'){        
        echo 'Forum :: '.$response->error->message;exit;
      }
    }
    function getUserid($userEmail){              
         if (isset($_COOKIE['forumLoginUserid'])) {
            $data     = array(
                           "email" => $userEmail,                           
                       );
            $userId = $_COOKIE['forumLoginUserid'];            
            $url      = WT_API_URL . "/users/".$userId;                        
            $response = Websitetoolboxforum::getInstance()->sso->sendApiRequest('GET', $url, $data,'json','forumApikey');                        
            if (isset($response->userId)) {
                 return $response->userId;
            } 
        }else{            
            $this->afterLogin();
            //echo 'Invalid user.';exit;
        }
    }
    function sendApiRequest($method, $url, $requestData, $postType, $apiKey=''){                   
        if ($method == "GET") {
            $url = sprintf("%s?%s", $url, http_build_query($requestData));
        }
        
        $curl         = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        $forumApiKey  = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumApiKey',false);        
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
        $cookieForumLogoutToken = $_COOKIE['forumLogoutToken'];
        $forumUrl     = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumUrl',false);
        echo '<img src='.$forumUrl.'/register/logout?authtoken='.$cookieForumLogoutToken.' border="0" width="0" height="0" alt="" id="imageTag">'; 
      }
    }
    function resetCookieOnLogout(){
      setcookie('forumLogInToken', 0, time() - 3600, "/");
      setcookie('forumLogoutToken', 0, time() - 3600, "/");
      setcookie('forumLoginUserid', '', time() - 3600, "/");
      setcookie('loginRemember', '', time() - 3600, "/");
      setcookie('logInForum', '', time() - 3600, "/");
   } 
      
   function renderJsScriptEmbedded($forumUrl,$userStatus)
   {
        if(isset($_COOKIE['forumLogInToken'])){
            $cookieForumLoginToken = $_COOKIE['forumLogInToken'];            
        }else{
            $cookieForumLoginToken = 0;
        }        
        $js = <<<JS
          (  
           function renderEmbeddedHtmlWithAuthtoken()
          { var embedUrl  = "{$forumUrl}";
            var userStatus = "{$userStatus}";
            var cookieForumLoginToken = "{$cookieForumLoginToken}";            
            var wtbWrap = document.createElement('div');
            wtbWrap.id = "wtEmbedCode";            
            var embedScript = document.createElement('script');
            embedScript.id = "embedded_forum";
            embedScript.type = 'text/javascript'; 
            if(typeof cookieForumLoginToken != 'undefined' && cookieForumLoginToken != 0){ 
                if(userStatus == 'loggedIn'){                      
                    embedUrl += "/js/mb/embed.js?authtoken="+cookieForumLoginToken;
                }else{
                    embedUrl += "/js/mb/embed.js?authtoken=0";
                }
            } else{                 
                embedUrl += "/js/mb/embed.js";
            }            
            embedScript.src = embedUrl;
            embedScript.setAttribute('data-version','1.1');
            wtbWrap.appendChild(embedScript);            
            if(document.getElementById('wtEmbedCode') != null){
                document.getElementById('wtEmbedCode').innerHTML = '';
                document.getElementById('wtEmbedCode').appendChild(embedScript);  
                setTimeout(function(){
                    var tempUrl = embedUrl.split('?');
                    if(document.getElementById('embedded_forum')){
                        document.getElementById('embedded_forum') = tempUrl[0];
                    }
                    var date = new Date();
                    date.setTime(date.getTime() - 3600);
                    var expires = "; expires=" + date.toUTCString();                
                    document.cookie = 'forumLogInToken' + "=" + 0  + expires + "; path=/";
                }, 2000)
                
            }
          })();
        JS;
        return $js;
   }   
  function renderJsScriptUnEmbedded(){    
        $baseUrl = UrlHelper::siteUrl();        
        $token = Craft::$app->getSession()->get(Craft::$app->getUser()->tokenParam);
        $forumUrl = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumUrl',false);        
        if(isset($_COOKIE['forumLogoutToken'])){
            $cookieForumLogoutToken = $_COOKIE['forumLogoutToken'];
            if(!isset($_COOKIE['logInForum'])){
                setcookie('logInForum', 1, time() + 3600,"/");                                
                $_COOKIE['logInForum'] = 1;
                echo '<img src='.$forumUrl.'/register/dologin?authtoken='.$cookieForumLogoutToken.' style="width:0px !important;height:0px !important;" border="0" alt="" id="imageTag">'; 
            }
        }else{
            $cookieForumLogoutToken = 0;
        }         
        $cmUrl = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.communityUrl',false);
        $embed = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumEmbedded',false);


        $js = <<<JS
        (  
         function renderEmbeddedUnHtmlWithAuthtoken()
        {
            var embed = "{$embed}";
            var forumUrl = "{$forumUrl}";
            var cmUrl = "{$cmUrl}";
            var baseUrl = "{$baseUrl}";
            var cookieForumLogoutToken = "{$cookieForumLogoutToken}";            
            var authtokenStr = "?authtoken="+cookieForumLogoutToken;
            var forumHref = forumUrl+authtokenStr;            
            var forumLink = baseUrl+cmUrl;
            if(baseUrl.match('index.php')){
                forumLink = baseUrl+"?p="+cmUrl;
            }
            var link = document.querySelector('[href="'+forumLink+'"]');
            if(link != null){
                link.setAttribute("href", forumHref);
                if(document.getElementById('wtEmbedCode')){
                    window.location.href = forumHref; 
                }
            }else{
                if(document.getElementById('wtEmbedCode')){
                    window.location.href = forumHref; 
                }
            }
            if(embed == '' && document.getElementById('wtEmbedCode')){
                document.getElementById('wtEmbedCode').innerHTML = '';
                document.getElementById('wtLoadingIcon').remove();
            }
        })();
JS;
        return $js ;
    
  }   
}
