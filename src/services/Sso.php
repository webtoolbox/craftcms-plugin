<?php
/**
 * Website Toolbox Community plugin for Craft CMS 3.x
 * Single Sign On Cloud Based plugin for CraftCMS
 * @link      https://websitetoolbox.com/
 * @copyright Copyright (c) 2020 Website Toolbox
 */
namespace websitetoolbox\websitetoolboxcommunity\services;
use websitetoolbox\websitetoolboxcommunity\models\Settings;
use websitetoolbox\websitetoolboxcommunity\Websitetoolboxcommunity;
use Craft;
use craft\base\Component;
use craft\web\View;
use craft\services\Config;
use craft\helpers\UrlHelper;
define('WT_API_URL', 'https://api.websitetoolbox.com/v1/api');
/**
 * @author    Website Toolbox
 * @package   Websitetoolboxcommunity
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
                    $result       = Websitetoolboxcommunity::getInstance()->sso->sendApiRequest('POST',$RequestUrl,$postData,'json'); 
                    if(isset($result->authtoken) && $result->authtoken !=''){
                        setcookie("forumLogInToken", $result->authtoken, time() + (86400 * 365),"/");
                        setcookie("forumLogoutToken", $result->authtoken, time() + (86400 * 365),"/");
                        setcookie("forumLoginUserid", $result->userid, time() + (86400 * 365),"/");
                    }
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
            $result               = Websitetoolboxcommunity::getInstance()->sso->sendApiRequest('POST',$RequestUrl,$postData,'json'); 
            if(Craft::$app->getSession()->get(Craft::$app->getUser()->tokenParam) && isset(Craft::$app->getUser()->getIdentity()->email) && ($user->user->email == Craft::$app->getUser()->getIdentity()->email)){
                $RequestUrl           = $forumUrl."/register/setauthtoken";
                $postData             = array('type'=>'json','apikey' => $forumApiKey, 'user' => $userName,'email'=>$userEmail,'externalUserid'=>$userId);
                $result               = Websitetoolboxcommunity::getInstance()->sso->sendApiRequest('POST',$RequestUrl,$postData,'json');
                if(isset($result->authtoken)){
                    setcookie("forumLogInToken", $result->authtoken, time() + (86400 * 365),"/");
                    setcookie("forumLogoutToken",$result->authtoken, time() + (86400 * 365),"/");
                    setcookie("forumLoginUserid", $result->userid, time() + (86400 * 365),"/");
                }
            }
        }
    }
    function afterUpdateUser(){
      $emailToVerify  = $_SESSION['userEmailBeforeUpdate'];
      $userId         = Websitetoolboxcommunity::getInstance()->sso->getUserid($emailToVerify);
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
      $response       = Websitetoolboxcommunity::getInstance()->sso->sendApiRequest('POST',$url,$userDetails,'json','forumApikey');
    }
    function getUserid($userEmail){
         if ($userEmail) {
            $data     = array(
                           "email" => $userEmail);
            $url      = WT_API_URL . "/users/";             
            $response = Websitetoolboxcommunity::getInstance()->sso->sendApiRequest('GET', $url, $data,'json','forumApikey');          
            if (isset($response->{'data'}[0]->{'userId'})) {
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
        $result       = Websitetoolboxcommunity::getInstance()->sso->sendApiRequest('POST',$RequestUrl,$postData,'json');
    }
    function afterLogOut(){  
      if(isset($_COOKIE['forumLogoutToken'])){
        $forumUrl     = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumUrl',false);
        $actualLink = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $logoutUrl = $forumUrl.'/register/logout?authtoken='.$_COOKIE['forumLogoutToken'].'&redirect='.$actualLink;
        Websitetoolboxcommunity::getInstance()->sso->resetCookieOnLogout();
        header("Location:".$logoutUrl);exit;
      }
    }
    function resetCookieOnLogout(){
      setcookie('forumLogInToken', 0, time() - (86400 * 365), "/");
      setcookie('forumLogoutToken', 0, time() - (86400 * 365), "/");
      setcookie('forumLoginUserid', '', time() - (86400 * 365), "/");
      setcookie('loginRemember', '', time() - (86400 * 365), "/");
      setcookie('logInForum', '', time() - 3600, "/");
   }
   function renderJsScriptEmbedded($forumUrl,$userStatus){ 
        if((isset($_COOKIE['forumLogInToken']) && $_COOKIE['forumLogInToken'] != '')){
            $cookieForumLoginToken = $_COOKIE['forumLogInToken'];            
            setcookie("forumLogInToken", '', time() - (86400 * 365),"/"); 
            $_COOKIE['forumLogInToken'] = '';
            echo '<img src='.$forumUrl.'/register/dologin?authtoken='.$cookieForumLoginToken.'  width="0" height="0" border="0" alt="">';
        }
        $js = <<<JS
          (  
           function renderEmbeddedHtmlWithAuthtoken()
          { var embedUrl  = "{$forumUrl}";
            var userStatus = "{$userStatus}";
            var wtbWrap = document.createElement('div');
            wtbWrap.id = "wtEmbedCode";
            var embedScript = document.createElement('script');
            embedScript.id = "embedded_forum";
            embedScript.type = 'text/javascript'; 
            embedUrl += "/js/mb/embed.js";
            embedScript.src = embedUrl;
            embedScript.setAttribute('data-version','1.1'); 
            wtbWrap.appendChild(embedScript);
            if(document.getElementById('wtEmbedCode') != null){
                document.getElementById('wtEmbedCode').innerHTML = '';
                document.getElementById('wtEmbedCode').appendChild(embedScript);  
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
            setcookie('logInForum', 1, time() + (3600),"/");                                
            $_COOKIE['logInForum'] = 1;
            echo '<img src='.$forumUrl.'/register/dologin?authtoken='.$cookieForumLogoutToken.' width="0" height="0" border="0" alt="">'; 
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
        }
        if(embed == '' && document.getElementById('wtEmbedCode')){
            document.getElementById('wtEmbedCode').innerHTML = '';
            if(document.getElementById('wtLoadingIcon')){
                document.getElementById('wtLoadingIcon').remove();
            }
        }
    })();
JS;
    return $js ;
  }   
}
