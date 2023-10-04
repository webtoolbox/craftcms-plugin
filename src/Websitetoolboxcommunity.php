<?php
/**
 * Website Toolbox Forum plugin for Craft CMS 4.x
 * Single Sign On Cloud Based plugin for CraftCMS
 * @link      https://websitetoolbox.com/
 * @copyright Copyright (c) 2020 Website Toolbox
 */
namespace websitetoolbox\community;
use websitetoolbox\community\services\Sso as SsoService;
use websitetoolbox\community\models\Settings;
use websitetoolbox\community\assetbundles\Websitetoolboxcommunity\WebsitetoolboxcommunityAsset;
use Craft;
use craft\base\Plugin;
use craft\web\twig\variables\CraftVariable;
use craft\helpers\UrlHelper;
use yii\base\Event;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Db;
use craft\elements\User;
use craft\events\ModelEvent;
use craft\services\Users;
use craft\web\View;
use craft\services\Config;
use craft\web\Request;
use yii\web\Response;
use craft\elements\Entry;
use craft\base\Element;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use craft\services\Plugins;
use craft\events\PluginEvent;

define('WT_SETTINGS_URL', 'https://www.websitetoolbox.com/tool/members/mb/settings');
/**
 * Class Websitetoolboxforum
 * @author    Website Toolbox
 * @package   Websitetoolboxforum
 * @since     3.0.0
 * @property  SsoService $sso
 */
class Websitetoolboxcommunity extends Plugin{
    public static $plugin;
    public static $craft31 = false;    
    public $connection; 
    
    // Public Methods
    public function init(){ 
        parent::init();
        Craft::info(
            Craft::t(
                'websitetoolboxforum',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );                    
        self::$plugin = $this;
        $this->setComponents([
            'sso' => \websitetoolbox\community\services\Sso::class,
        ]);
        self::$craft31 = version_compare(Craft::$app->getVersion(), '3.1', '>=');
        Event::on(Plugins::class, Plugins::EVENT_AFTER_ENABLE_PLUGIN,
            function (PluginEvent $event) {
                $forumUrl = Craft::$app->getPlugins()->getStoredPluginInfo('websitetoolboxforum') ["settings"]["forumUrl"];
                $forumApiKey = Craft::$app->getPlugins()->getStoredPluginInfo('websitetoolboxforum') ["settings"]["forumApiKey"];
                if(isset(Craft::$app->getUser()->getIdentity()->id)){                    
                    Websitetoolboxcommunity::getInstance()->sso->resetCookieOnLogout();
                    $this->setAuthToken($forumUrl, $forumApiKey);
                    $this->loginUsingImgTag($_COOKIE['forumLogoutToken']);                    
                }
            }
        );
        Event::on(\craft\services\Elements::class, \craft\services\Elements::EVENT_BEFORE_SAVE_ELEMENT, function(Event $event) {
            if ($event->element instanceof \craft\elements\User) {
                if($event->element->id){
                    $usersService                       = Craft::$app->getUsers();
                    $userDetailsBeforeUpdate            = $usersService->getUserById($event->element->id);
                    $_SESSION['userEmailBeforeUpdate']  = $userDetailsBeforeUpdate['email'];
                }
            }
        });
        if(!Craft::$app->getRequest()->getIsConsoleRequest()){           
            $token = Craft::$app->getSession()->get(Craft::$app->getUser()->tokenParam);            
            if(!$token && isset($_COOKIE['forumLogoutToken']) && isset(Craft::$app->getPlugins()->getStoredPluginInfo('websitetoolboxforum') ["settings"]["forumUrl"])){
                Event::on(View::class, View::EVENT_END_BODY, function(Event $event) {
                    $forumUrl = Craft::$app->getPlugins()->getStoredPluginInfo('websitetoolboxforum') ["settings"]["forumUrl"];
                    echo '<img src='.$forumUrl.'/register/logout?authtoken='.$_COOKIE['forumLogoutToken'].'" border="0" width="0" height="0" alt="">';
                    Websitetoolboxcommunity::getInstance()->sso->resetCookieOnLogout();
                });
            }
            // If user is already logged in and admin remove user group from plugin setting 
            else if($token && isset($_COOKIE['forumLogoutToken']) && !$this->checkGroupPermission()){
                $forumUrl = Craft::$app->getPlugins()->getStoredPluginInfo('websitetoolboxforum') ["settings"]["forumUrl"];
                echo '<img src='.$forumUrl.'/register/logout?authtoken='.$_COOKIE['forumLogoutToken'].'" border="0" width="1" height="1" alt="">';
                Websitetoolboxcommunity::getInstance()->sso->resetCookieOnLogout();
            }
        } 
        if(!empty(Craft::$app->getPlugins()->getStoredPluginInfo('websitetoolboxforum') ["settings"]["forumUrl"])){
            Event::on(View::class, View::EVENT_BEFORE_RENDER_TEMPLATE,function (Event $event) {
                $token = Craft::$app->getSession()->get(Craft::$app->getUser()->tokenParam); 
                if(!$token){
                    Websitetoolboxcommunity::getInstance()->sso->afterLogOut();
                }
                $forumType  = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumEmbedded',false);
                $forumUrl   = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumUrl');
                if($forumType == 1){                    
                    $token = Craft::$app->getSession()->get(Craft::$app->getUser()->tokenParam); 
                    if( $token){             
                        $jsRender = Websitetoolboxcommunity::getInstance()->sso->renderJsScriptEmbedded($forumUrl,'loggedIn');
                    }else{                        
                        $jsRender = Websitetoolboxcommunity::getInstance()->sso->renderJsScriptEmbedded($forumUrl,'loggedout');
                    }
                 }else{                    
                    $jsRender = Websitetoolboxcommunity::getInstance()->sso->renderJsScriptUnEmbedded();
                }                                
                $view = Craft::$app->getView();
                $view->registerJs($jsRender);
            });
            if(!empty(Craft::$app->getPlugins()->getStoredPluginInfo('websitetoolboxforum') ["settings"]["communityUrl"])){
                // Register site url route for community
                Event::on(
                    \craft\web\UrlManager::class,
                    \craft\web\UrlManager::EVENT_REGISTER_SITE_URL_RULES,
                    function(RegisterUrlRulesEvent $event) {
                        $segement = Craft::$app->getPlugins()->getStoredPluginInfo('websitetoolboxforum') ["settings"]["communityUrl"];
                        $event->rules[$segement] = 'websitetoolboxforum/default/index';
                        $event->rules['webhook'] = 'websitetoolboxforum/default/webhook';
                    }
                );
            }
            if(!isset($_COOKIE['forumAddress'])){
                setcookie("forumAddress", Craft::$app->getPlugins()->getStoredPluginInfo('websitetoolboxforum') ["settings"]["forumUrl"], time() + (86400 * 365),"/");
                $_COOKIE['forumAddress'] = Craft::$app->getPlugins()->getStoredPluginInfo('websitetoolboxforum') ["settings"]["forumUrl"];
            }
            // if default host change from wt admin area
            if(!isset($_REQUEST['forum_url']) && isset($_COOKIE['forumAddress']) && $_COOKIE['forumAddress'] != Craft::$app->getPlugins()->getStoredPluginInfo('websitetoolboxforum') ["settings"]["forumUrl"]){
                $lastestForumUrl = Craft::$app->getPlugins()->getStoredPluginInfo('websitetoolboxforum') ["settings"]["forumUrl"];
                $forumApiKey = Craft::$app->getPlugins()->getStoredPluginInfo('websitetoolboxforum') ["settings"]["forumApiKey"];
                setcookie("forumAddress", $lastestForumUrl, time() + (86400 * 365),"/");
                $_COOKIE['forumAddress'] = $lastestForumUrl;
                if(isset(Craft::$app->getUser()->getIdentity()->id))
                {
                    Websitetoolboxcommunity::getInstance()->sso->resetCookieOnLogout();
                    $this->setAuthToken($lastestForumUrl, $forumApiKey);
                } 
            }
        }
        Event::on(\craft\services\Users::class, \craft\services\Users::EVENT_AFTER_ACTIVATE_USER, function(Event $event) {                         
                Websitetoolboxcommunity::getInstance()->sso->afterUserCreate($event);
             
        });
        Event::on(\craft\services\Elements::class, \craft\services\Elements::EVENT_AFTER_SAVE_ELEMENT, function(Event $event) {            
            if ($event->element instanceof \craft\elements\User) {
                if(isset($_POST['userId']) && $this->checkGroupPermission()){
                    Websitetoolboxcommunity::getInstance()->sso->afterUpdateUser();
                }
            }
        });

        Event::on(\craft\services\Elements::class, \craft\services\Elements::EVENT_AFTER_DELETE_ELEMENT, function(Event $event) {
            if ($event->element instanceof \craft\elements\User) {                           
                Websitetoolboxcommunity::getInstance()->sso->afterDeleteUser($event->element->username);
                Websitetoolboxcommunity::getInstance()->sso->afterLogOut();
            }
        });
          
        Event::on( \yii\base\Component::class, \craft\web\User::EVENT_AFTER_LOGIN, function(Event $event) {
            if($this->checkGroupPermission()){
                Websitetoolboxcommunity::getInstance()->sso->afterLogin();
            }            
        });
        Event::on( \yii\base\Component::class, \craft\web\User::EVENT_AFTER_LOGOUT, function(Event $event) {
            Websitetoolboxcommunity::getInstance()->sso->afterLogOut();
        });        
    }
    protected function createSettingsModel(): ?\craft\base\Model{        
        return new Settings();
    }
    protected function settingsHtml(): ?string{        
        $hashTypes = hash_algos();
        $hashTypes = array_combine($hashTypes, $hashTypes);
        $userGroups = Craft::$app->userGroups->getAllGroups();
        Craft::$app->view->registerAssetBundle(Communityassets::class);
        return Craft::$app->view->renderTemplate(
            'websitetoolboxforum/settings',
            [
                'settings'  => $this->getSettings(),
                'hashTypes' => $hashTypes,
                'userGroups' => $userGroups
            ]
        );
    }
    public function afterSaveSettings(): void{
        $webHookPage = 'webhook';
        $siteUrl = UrlHelper::siteUrl();
        $webhookUrl = $siteUrl.'/'.$webHookPage;
        if(strpos($siteUrl, 'index.php') >= 0){
            $pageTrigger = Craft::$app->getConfig()->general->pageTrigger;
            $webhookUrl = $siteUrl.'?'.$pageTrigger.'='.$webHookPage;
        }
        if(isset($_POST['settings']['forumUsername'])){
            $forumType  = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumEmbedded',false);
            $forumUrl  = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumUrl',false);
            $ssoSetting = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.ssoSetting',false);
            $userName               = $_POST['settings']['forumUsername'];
            $userPassword           = $_POST['settings']['forumPassword'];
            $postData = array('action' => 'checkPluginLogin', 'username' => $userName,'password'=>$userPassword, 'plugin' => 'craft', 'websiteBuilder' => 'craftcms', 'pluginWebhookUrl' => $webhookUrl);           
            $result = $this->sso->sendApiRequest('POST',WT_SETTINGS_URL,$postData,'json');
            if(empty($result) || (isset($result->errorMessage) && $result->errorMessage != '')){
                if(empty($result)){
                    $errorMessage = 'Authentication fail for Websitetoolboxcommunity';
                }else{
                    $errorMessage = $result->errorMessage;
                }
                Craft::$app->getSession()->setError(Craft::t('websitetoolboxforum', $errorMessage));
                Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('settings/plugins/websitetoolboxforum'))->send();
                exit;
            }
            $deleteForumUrlRows     = Craft::$app->getProjectConfig()->remove('plugins.websitetoolboxforum.settings.forumUrl');
            $deleteForumApiKeyRows  = Craft::$app->getProjectConfig()->remove('plugins.websitetoolboxforum.settings.forumApiKey');            

            $affectedForumUrlRows   = Craft::$app->getProjectConfig()->set('plugins.websitetoolboxforum.settings.forumUrl',$result->forumAddress); 
            $affectedForumApiKeyRows = Craft::$app->getProjectConfig()->set('plugins.websitetoolboxforum.settings.forumApiKey',$result->forumApiKey);            
            if(isset($result->secretKey)){
                $deleteSecretKeyRows  = Craft::$app->getProjectConfig()->remove('plugins.websitetoolboxforum.settings.secretKey');
                $affectedSecretKeyRows = Craft::$app->getProjectConfig()->set('plugins.websitetoolboxforum.settings.secretKey', $result->secretKey);
            }
            if($forumUrl !='' && $forumType ==''){
                $embeddedPage = '';
            }else{
                $embeddedPage = 'community';
            }
            if($ssoSetting == ''){                
                $this->setUserGroupAccess('all_users');
            }
        } else{
            $userName = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumUsername',false);
            $userPassword = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumPassword',false);
            $embedOption = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumEmbedded',false);
            if(isset($_POST['settings']['sso_setting'])){
                $ssoSetting = trim($_POST['settings']['sso_setting']);
                $this->setUserGroupAccess($ssoSetting);
            }
            if(isset($_POST['settings']['communityUrl']) && $_POST['settings']['forumEmbedded'] == 1){
                $embeddedPage = $_POST['settings']['communityUrl'];
                if($_POST['settings']['communityUrl'] == ''){
                    $embeddedPage = 'community';
                }
                Craft::$app->getProjectConfig()->set('plugins.websitetoolboxforum.settings.communityUrl', trim($embeddedPage));
            }else if(!isset($_POST['settings']['communityUrl']) && $embedOption == 1){
                $embeddedPage = Craft::$app->getPlugins()->getStoredPluginInfo('websitetoolboxforum') ["settings"]["communityUrl"];
            }else{
                $embeddedPage = '';
            }
            $postData = array('action' => 'checkPluginLogin', 'username' => $userName,'password'=>$userPassword, 'plugin' => 'craft', 'websiteBuilder' => 'craftcms', 'pluginWebhookUrl' => $webhookUrl);            
            $result = $this->sso->sendApiRequest('POST',WT_SETTINGS_URL,$postData,'json');
            if(empty($result) || (isset($result->errorMessage) && $result->errorMessage != '')){
                if(empty($result)){
                    $errorMessage = 'Authentication fail for Websitetoolboxcommunity';
                }else{
                    $errorMessage = $result->errorMessage;
                }
                Craft::$app->getSession()->setNotice(Craft::t('websitetoolboxforum', $errorMessage));
                Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('settings/plugins/websitetoolboxforum'))->send();
                exit;
            }
            $deleteForumUrlRows     = Craft::$app->getProjectConfig()->remove('plugins.websitetoolboxforum.settings.forumUrl');
            $deleteForumApiKeyRows  = Craft::$app->getProjectConfig()->remove('plugins.websitetoolboxforum.settings.forumApiKey');                  

            $affectedForumUrlRows   = Craft::$app->getProjectConfig()->set('plugins.websitetoolboxforum.settings.forumUrl',$result->forumAddress); 
            $affectedForumApiKeyRows = Craft::$app->getProjectConfig()->set('plugins.websitetoolboxforum.settings.forumApiKey',$result->forumApiKey);
            if(isset($result->secretKey)){                
                $deleteSecretKeyRows  = Craft::$app->getProjectConfig()->remove('plugins.websitetoolboxforum.settings.secretKey');
                $affectedSecretKeyRows = Craft::$app->getProjectConfig()->set('plugins.websitetoolboxforum.settings.secretKey', $result->secretKey);
            }
        }        
        $this->setAuthToken($result->forumAddress, $result->forumApiKey);
        // to set embedded url
        $this->updateEmbeddedUrl($userName, $result->forumApiKey, $embeddedPage);
    }
    /**
     * @uses function to add/edit emebedded URL
     * @param forumUserName - string
     * @param forumApiKey - string
     */
    public function updateEmbeddedUrl($forumUserName, $forumApiKey, $embeddedPage){
        if($embeddedPage != ''){
            $siteUrl = UrlHelper::siteUrl();
            $embedUrl = $siteUrl.'/'.$embeddedPage;
            if(strpos($siteUrl, 'index.php') > 0){
                $pageTrigger = Craft::$app->getConfig()->general->pageTrigger;
                $embedUrl = $siteUrl.'?'.$pageTrigger.'='.$embeddedPage;
            }
        }else{
            $embedUrl = '';
        }
        $fields = array(
            'action' => 'modifySSOURLs',
            'forumUsername' => $forumUserName,
            'forumApikey' => $forumApiKey,
            'embed_page_url' => $embedUrl,
            'altEmbedParam' => '',
            'plugin' => 'craft'
        );  
        $response = $this->sso->sendApiRequest('POST',WT_SETTINGS_URL,$fields,'json');
        return $response;
    }
    /**
    * @uses function to create/register user on community and return authtoken to login into community
    * @param forumUrl - string - a url of community
    * @param forumApiKey - sting - Community api key 
    */
    public function setAuthToken($forumUrl, $forumApiKey, array $userData = null){
        $RequestUrl        = $forumUrl."/register/setauthtoken";
        $myUserQuery       = \craft\elements\User::find();
        $loggedinUserEmail = isset($userData['email']) ? $userData['email'] : Craft::$app->getUser()->getIdentity()->email;
        $loggedinUserId    = isset($userData['externalUserid']) ? $userData['externalUserid'] : Craft::$app->getUser()->getIdentity()->id;
        $loggediUserName   = isset($userData['user']) ? $userData['user'] : Craft::$app->getUser()->getIdentity()->username;
        $postData = array(
            'type'=>'json',
            'apikey' => $forumApiKey,
            'user' => $loggediUserName,
            'email'=>$loggedinUserEmail,
            'externalUserid'=>$loggedinUserId
        );
        $response = Websitetoolboxcommunity::getInstance()->sso->sendApiRequest('POST',$RequestUrl,$postData,'json');
        if(isset($response->authtoken) && $response->authtoken !=''){
            setcookie("forumLogInToken", $response->authtoken, time() + (86400 * 365),"/");
            setcookie("forumLogoutToken", $response->authtoken, time() + (86400 * 365),"/");
            setcookie("forumLoginUserid", $response->userid, time() + (86400 * 365),"/");    
        }else{
            if(isset($response->message)){
                Craft::$app->getSession()->setError(Craft::t('websitetoolboxforum', $response->message));    
            }
        }
    }
    /**
     * Function to get all usergroup ids
     */
    public function getAllUserGroups(){
        $userGroups = Craft::$app->userGroups->getAllGroups();
        $allGroupsIdArray = array_column($userGroups, 'id');
        $allGroupsId = implode(',', $allGroupsIdArray);
        return $allGroupsId;
    }
    /**
     * Function to save seletected sso setting to plugin config
     * @param - ssoSetting - string - selected sso option
     */
    private function setUserGroupAccess($ssoSetting){
        if(isset($_POST['settings']['user_roles']) && !empty($_POST['settings']['user_roles'])){
            $allGroupsId = $this->getAllUserGroups();
            $allGroupsIdArray = explode(',', $allGroupsId);            
            $selectedGroup = $_POST['settings']['user_roles'];
            $selectedGroupCount = count($_POST['settings']['user_roles']);
            $selectGroupList = [];

            if($selectedGroupCount > 0){
                for( $i = 0; $i <= $selectedGroupCount; $i++){
                    if(isset($selectedGroup[$i]) && in_array($selectedGroup[$i], $allGroupsIdArray)){
                        $selectGroupList[] = $selectedGroup[$i];
                    }
                }
            }
            $userGroupsId = implode(',', $selectGroupList);
        }else if($ssoSetting == 'all_users'){
            $userGroupsId = $this->getAllUserGroups();            
        }else{
            $userGroupsId = '';
        }
        Craft::$app->getProjectConfig()->set('plugins.websitetoolboxforum.settings.ssoSetting', $ssoSetting);
        Craft::$app->getProjectConfig()->set('plugins.websitetoolboxforum.settings.userGroupsId', $userGroupsId);
    }
    public function loginUsingImgTag($authToken){
        if($this->checkGroupPermission()){
            $forumUrl = Craft::$app->getPlugins()->getStoredPluginInfo('websitetoolboxforum') ["settings"]["forumUrl"];
            echo '<img src='.$forumUrl.'/register/dologin?authtoken='.$authToken.'  width="1" height="1" border="0" alt="">';
        }else{
            $user = Craft::$app->getUser()->getIdentity();
            if ($user) {
                $groups = $user->getGroups();
                $groupIds = [];
                foreach ($groups as $group) {
                    $groupIds[] = $group->id;
                }
            }
            print_r($groupIds);
        }
    }
    /**
     * @uses function to check if user group allow to do sso or not
     */    
     public function checkGroupPermission(){
        if($this->getAllUserGroups()){
            $ssoSetting = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.ssoSetting');
            $allowedGroupsId = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.userGroupsId');

            switch ($ssoSetting) {
                case 'all_users':
                    return true;
                case 'no_users':
                    return false;
                case 'selected_groups':
                    // get identity of logged in user
                    $user = Craft::$app->getUser()->getIdentity();
                    if ($user) {
                        $groups = $user->getGroups();
                        $groupIds = [];
                        foreach ($groups as $group) {
                            $groupIds[] = $group->id;
                        }
                        $allowedGroupsIdArray = explode(',', $allowedGroupsId);
                        $commonGroup = array_intersect($allowedGroupsIdArray, $groupIds);
                        if (empty($commonGroup)) {
                            return false;
                        } else {
                            return true;
                        }
                    }
                    break;
                default:
                    // if admin defined user groups but not sso setting option is not set yet
                    return true;
            }
        }else{
            //in case admin didn't set user group for website user.
            return true;
        }       
    }
}