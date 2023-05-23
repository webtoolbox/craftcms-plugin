<?php
/**
 * Website Toolbox Community plugin for Craft CMS 3.x
 * Single Sign On Cloud Based plugin for CraftCMS
 * @link      https://websitetoolbox.com/
 * @copyright Copyright (c) 2020 Website Toolbox
 */
namespace websitetoolbox\websitetoolboxcommunity;
use websitetoolbox\websitetoolboxcommunity\services\Sso as SsoService;
use websitetoolbox\websitetoolboxcommunity\models\Settings;
use websitetoolbox\websitetoolboxcommunity\assetbundles\Websitetoolboxcommunity\WebsitetoolboxforumAsset;
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


define('WT_SETTINGS_URL', 'https://www.websitetoolbox.com/tool/members/mb/settings');

/**
 * Class Websitetoolboxcommunity
 * @author    Website Toolbox
 * @package   Websitetoolboxcommunity
 * @since     3.0.0
 * @property  SsoService $sso
 */
class Websitetoolboxcommunity extends Plugin
{
    public static $plugin;
    public static $craft31 = false;
    public $schemaVersion = '1.0.0';
    public $connection; 
    // Public Methods
  
    public function init()
    { 
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
            'sso' => \websitetoolbox\websitetoolboxcommunity\services\Sso::class,
        ]);
        self::$craft31 = version_compare(Craft::$app->getVersion(), '3.1', '>=');
        Event::on(\craft\services\Elements::class, \craft\services\Elements::EVENT_BEFORE_SAVE_ELEMENT, function(Event $event) {
            if ($event->element instanceof \craft\elements\User) {
                if($event->element->id){
                    $usersService                       = Craft::$app->getUsers();
                    $userDetailsBeforeUpdate            = $usersService->getUserById($event->element->id);
                    $_SESSION['userEmailBeforeUpdate']  = $userDetailsBeforeUpdate['email'];
                }
            }
        });
        $token = Craft::$app->getSession()->get(Craft::$app->getUser()->tokenParam);
        if(!$token && isset($_COOKIE['forumLogoutToken'])){
            Event::on(View::class, View::EVENT_END_BODY, function(Event $event) {
                    $forumUrl = Craft::$app->getPlugins()->getStoredPluginInfo('websitetoolboxforum') ["settings"]["forumUrl"];
                    echo '<img src='.$forumUrl.'/register/logout?authtoken='.$_COOKIE['forumLogoutToken'].'" border="0" width="0" height="0" alt="" id="imageTag">';
                    Websitetoolboxcommunity::getInstance()->sso->resetCookieOnLogout();
            });
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
                $view ->registerJs($jsRender);
            });
        }
        Event::on(\craft\services\Users::class, \craft\services\Users::EVENT_AFTER_ACTIVATE_USER, function(Event $event) {         
                Websitetoolboxcommunity::getInstance()->sso->afterUserCreate($event);
             
        });
        Event::on(\craft\services\Elements::class, \craft\services\Elements::EVENT_AFTER_SAVE_ELEMENT, function(Event $event) {
            if ($event->element instanceof \craft\elements\User) {
                if(isset($_POST['userId'])){
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
            Websitetoolboxcommunity::getInstance()->sso->afterLogin();
        });
        Event::on( \yii\base\Component::class, \craft\web\User::EVENT_AFTER_LOGOUT, function(Event $event) {
            Websitetoolboxcommunity::getInstance()->sso->afterLogOut();
        });


    }
    protected function createSettingsModel(){
        return new Settings();
    }
    protected function settingsHtml(): string{
        $hashTypes = hash_algos();
        $hashTypes = array_combine($hashTypes, $hashTypes);
        return Craft::$app->view->renderTemplate(
            'websitetoolboxforum/settings',
            [
                'settings'  => $this->getSettings(),
                'hashTypes' => $hashTypes,
            ]
        );
        
    }    
    public function afterSaveSettings(){ 
        if(isset($_POST['settings']['forumUsername'])){
            $userName               = $_POST['settings']['forumUsername'];
            $userPassword           = $_POST['settings']['forumPassword'];
            $postData               = array('action' => 'checkPluginLogin', 'username' => $userName,'password'=>$userPassword,'plugin' => 'craft', 'websiteBuilder' => 'craftcms');
            $result                 = $this->sso->sendApiRequest('POST',WT_SETTINGS_URL,$postData,'json');
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
        } else{
            $userName               = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumUsername',false);;
            $userPassword           = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumPassword',false);;
            $postData               = array('action' => 'checkPluginLogin', 'username' => $userName,'password'=>$userPassword,'plugin' => 'craft', 'websiteBuilder' => 'craftcms');
            $result                 = $this->sso->sendApiRequest('POST',WT_SETTINGS_URL,$postData,'json'); 
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
        } 
 
        $RequestUrl   = $result->forumAddress."/register/setauthtoken";
        $myUserQuery  = \craft\elements\User::find();
        $loggedinUserEmail    = Craft::$app->getUser()->getIdentity()->email;
        $loggedinUserId       = Craft::$app->getUser()->getIdentity()->id;
        $loggediUserName     = Craft::$app->getUser()->getIdentity()->username;
        $postData     = array('type'=>'json','apikey' => $result->forumApiKey, 'user' => $loggediUserName,'email'=>$loggedinUserEmail,'externalUserid'=>$loggedinUserId);
        $result       = Websitetoolboxcommunity::getInstance()->sso->sendApiRequest('POST',$RequestUrl,$postData,'json'); 
        if(isset($result->authtoken) && $result->authtoken !=''){
            setcookie("forumLogInToken", $result->authtoken, time() + (86400 * 365),"/");   
            setcookie("forumLogoutToken", $result->authtoken, time() + (86400 * 365),"/");  
            setcookie("forumLoginUserid", $result->userid, time() + (86400 * 365),"/");   
        }else{
            if(isset($response->message)){
                Craft::$app->getSession()->setError(Craft::t('websitetoolboxforum', $response->message));    
            }
        }    
    }
}
   