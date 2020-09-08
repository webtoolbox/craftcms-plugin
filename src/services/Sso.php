<?php
/**
 * Website Toolbox Forum plugin for Craft CMS 3.x
 *
 * Single Sign On Cloud plugin for CraftCMS
 *
 * @link      https://websitetoolbox.com/
 * @copyright Copyright (c) 2019 Website Toolbox
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
             $forumUrl = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumUrl');
            if($forumApiKey){ 
                $myUserQuery = \craft\elements\User::find();
                $userEmail = Craft::$app->getUser()->getIdentity()->email;
                $userId= Craft::$app->getUser()->getIdentity()->id;
                $userName= Craft::$app->getUser()->getIdentity()->username;
                $RequestUrl = $forumUrl."/register/setauthtoken";
                $postData = array('type'=>'json','apikey' => $forumApiKey, 'user' => $userName,'email'=>$userEmail,'externalUserid'=>$userId);
                $result = Websitetoolboxforum::getInstance()->sso->sendRequest($postData,$RequestUrl,'json'); 
                setcookie("forumLogoutToken", $result['authtoken'], time() + 3600,"/");
                setcookie("forumLoginUserid", $result['userid'], time() + 3600,"/");
            }
        }
         
     }
     function afterUserCreate($userId){ 
        $forumUrl = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumUrl',false);
        $forumApiKey = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumApiKey',false); 
        $postData = array( 'type'=>'json','apikey'=>$forumApiKey,'member' => $_POST['username'],
        'externalUserid' => $userId, 
        'email' => $_POST['email']);
        if(isset($_POST['firstName'])){
           $postData['name'] =  $_POST['firstName'];
        }
        if(isset($_POST['firstName'])){
           $postData['name'] .=  " ".$_POST['lastName'];
        }        
        $RequestUrl = $forumUrl . "/register/create_account/";
        $result = Websitetoolboxforum::getInstance()->sso->sendRequest($postData,$RequestUrl,'json'); 
    }
    function afterUpdateUser(){
      $emailToVerify  = $_SESSION['userEmailBeforeUpdate'];
      $userId     = Websitetoolboxforum::getInstance()->sso->getUserid($emailToVerify);
      $userName = $_POST['username'];
      $externalUserid = $_POST['userId'];
      $email = $_POST['email'];;
      $userDetails = array("type"=>"json","email" => $email,
              "username" => $userName,
              "externalUserid" => $externalUserid,
              "name" => $userName);
      $url =  "/users/$userId";
      $response = Websitetoolboxforum::getInstance()->sso->sendApiRequest('POST',$url,$userDetails);
    }
    function getUserid($userEmail){
         if ($userEmail) {
            $data     = array(
                "email" => $userEmail
            );
            $response = Websitetoolboxforum::getInstance()->sso->sendApiRequest('GET', "/users/", $data); 
            if ($response->{'data'}[0]->{'userId'}) {
                 return $response->{'data'}[0]->{'userId'};
            }
        }
    }
    function sendApiRequest($method, $path, $data){
        $forumApiKey = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumApiKey',false);
        $url = WT_API_URL . $path;
        if ($method == "GET") {
            $url = sprintf("%s?%s", $url, http_build_query($data));
        }
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "x-api-key: " . $forumApiKey,
            'Content-Type: application/json'
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if ($method == "POST") {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        } else if ($method == "GET") {
            curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        }
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response);
    }
    function afterDeleteUser($userName){        
        $forumApiKey = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumApiKey',false);
        $forumUrl = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumUrl',false);
        $postData         = array('type'=>'json','apikey' => $forumApiKey,'massaction' => 'decline_mem','usernames' => $userName);
        $RequestUrl =  $forumUrl."/register";
        $result = Websitetoolboxforum::getInstance()->sso->sendRequest($postData,$RequestUrl,'json'); 
    }
    function afterLogOut(){  
      if(isset($_COOKIE['forumLogoutToken'])){
        $forumUrl = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumUrl',false);
        echo '<img src='.$forumUrl.'"/register/logout?authtoken='.$_COOKIE['forumLogoutToken'].'" border="0" width="0" height="0" alt="" id="imageTag">';  
        Websitetoolboxforum::getInstance()->sso->resetCookieOnLogout();  
      }
    }
    function resetCookieOnLogout(){
      setcookie('forumLogoutToken', 0, time() - 3600, "/");
      unset($_COOKIE['forumLogoutToken']);
      setcookie('forumLoginUserid', '', time() - 3600, "/");
      unset($_COOKIE['forumLoginUserid']);
      setcookie('loginRemember', '', time() - 3600, "/");
      unset($_COOKIE['loginRemember']);
   }
   function sendRequest($requestData,$requestUrl,$postType){
      $ch = curl_init();
      curl_setopt($ch,CURLOPT_URL,$requestUrl);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
      if($postType == 'json'){
        curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($requestData));
      }else{
        curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($requestData));
      }
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
      'Content-Type: application/json','Accept: application/json'));      
      curl_setopt($ch, CURLOPT_HEADER, 0);
      $response = curl_exec($ch); 
      $result = json_decode($response, true); 
      return $result;
   }
   function renderJsScriptEmbedded($forumUrl){   

        $js = <<<JS
  (  
   function renderEmbeddedHtmlWithAuthtoken()
  {  var embedUrl  = "{$forumUrl}";    
    var wtbWrap = document.createElement('div');
    wtbWrap.id = "wtEmbedCode";
    var embedScript = document.createElement('script');
    embedScript.id = "embedded_forum";
    embedScript.type = 'text/javascript';
    var wtbToken = getCookie();
    if(typeof wtbToken != 'undefined' && wtbToken){
      embedUrl += "/js/mb/embed.js?authtoken="+wtbToken;
    } 
    embedScript.src = embedUrl; 
    wtbWrap.appendChild(embedScript); 
    document.getElementById('embedForum').appendChild(embedScript);

  })();
JS;
      return $js ;
    }
   
function renderJsScriptUnEmbedded() 
    {   

        $js = <<<JS
        (  
         function renderEmbeddedUnHtmlWithAuthtoken()
        { 
        var links = document.getElementsByTagName('a');
        for(var i = 0; i< links.length; i++){
          var str = links[i].href; 
          for(var j = 0; j< str.length; j++){
            var res = str.split("."); 
            if(res[j] == 'websitetoolbox'){ 
                var linkToChange = links[i]; 
                var wtbToken = getCookie(); 
                if(typeof wtbToken != 'undefined' && wtbToken){
                    linkToChange.setAttribute("href", linkToChange+"?authtoken="+wtbToken);
                }
            }
            
          }
        }
        })();
JS;
        return $js ;
    }
   
}
