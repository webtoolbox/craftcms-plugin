<?php
/**
 * Website Toolbox Forum plugin for Craft CMS 4.x
 * Single Sign On Cloud Based plugin for CraftCMS
 * @link      https://websitetoolbox.com/
 * @copyright Copyright (c) 2020 Website Toolbox
 */
namespace websitetoolbox\websitetoolboxforum;
use websitetoolbox\websitetoolboxforum\services\Sso as SsoService;
use websitetoolbox\websitetoolboxforum\models\Settings;
use websitetoolbox\websitetoolboxforum\assetbundles\Websitetoolboxforum\WebsitetoolboxforumAsset;
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

define('WT_SETTINGS_URL', 'https://beta4.websitetoolbox.com/tool/members/mb/settings');
/**
 * Class Websitetoolboxforum
 * @author    Website Toolbox
 * @package   Websitetoolboxforum
 * @since     3.0.0
 * @property  SsoService $sso
 */
class Websitetoolboxforum extends Plugin
{
    public static $plugin;
    public static $craft31 = false;
    //public string $schemaVersion = '1.0.0';
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
        
        /*Craft::$app->getProjectConfig()->remove('plugins.websitetoolboxforum.settings.forumUrl');
        Craft::$app->getProjectConfig()->remove('plugins.websitetoolboxforum.settings.forumApiKey');
        Craft::$app->getProjectConfig()->remove('plugins.websitetoolboxforum.settings.forumEmbedded');
        Craft::$app->getProjectConfig()->remove('plugins.websitetoolboxforum.settings.forumUsername');
        Craft::$app->getProjectConfig()->remove('plugins.websitetoolboxforum.settings.forumPassword');
        Craft::$app->getProjectConfig()->remove('plugins.websitetoolboxforum.settings.communityUrl');*/        
        $this->setComponents([
            'sso' => \websitetoolbox\websitetoolboxforum\services\Sso::class,
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
        if(!Craft::$app->getRequest()->getIsConsoleRequest()){
            $token = Craft::$app->getSession()->get(Craft::$app->getUser()->tokenParam);

            if(!$token && isset($_COOKIE['forumLogoutToken']) && isset(Craft::$app->getPlugins()->getStoredPluginInfo('websitetoolboxforum') ["settings"]["forumUrl"])){
                Event::on(View::class, View::EVENT_END_BODY, function(Event $event) {
                        $forumUrl = Craft::$app->getPlugins()->getStoredPluginInfo('websitetoolboxforum') ["settings"]["forumUrl"];
                        echo '<img src='.$forumUrl.'/register/logout?authtoken='.$_COOKIE['forumLogoutToken'].'" border="0" width="0" height="0" alt="" id="imageTag">';
                        Websitetoolboxforum::getInstance()->sso->resetCookieOnLogout();
                });
            }
        }
 
        if(!empty(Craft::$app->getPlugins()->getStoredPluginInfo('websitetoolboxforum') ["settings"]["forumUrl"])){            
            Event::on(View::class, View::EVENT_BEFORE_RENDER_TEMPLATE,function (Event $event) {                
                $token = Craft::$app->getSession()->get(Craft::$app->getUser()->tokenParam); 
                if(!$token){
                    Websitetoolboxforum::getInstance()->sso->afterLogOut();
                }
                $forumType  = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumEmbedded',false);
                $forumUrl   = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumUrl');                         
                if($forumType == 1){                    
                    $token = Craft::$app->getSession()->get(Craft::$app->getUser()->tokenParam); 
                    if( $token){                        
                        $jsRender = Websitetoolboxforum::getInstance()->sso->renderJsScriptEmbedded($forumUrl,'loggedIn');
                    }else{                        
                        $jsRender = Websitetoolboxforum::getInstance()->sso->renderJsScriptEmbedded($forumUrl,'loggedout');
                    }
                 }else{                    
                    $jsRender = Websitetoolboxforum::getInstance()->sso->renderJsScriptUnEmbedded();
                }                                
                $view = Craft::$app->getView();
                $view->registerJs($jsRender);
            });
            if(!empty(Craft::$app->getPlugins()->getStoredPluginInfo('websitetoolboxforum') ["settings"]["communityUrl"]))
            {
                // Register site url route for forum
                Event::on(
                    \craft\web\UrlManager::class,
                    \craft\web\UrlManager::EVENT_REGISTER_SITE_URL_RULES,
                    function(RegisterUrlRulesEvent $event) {                        
                        $segement = Craft::$app->getPlugins()->getStoredPluginInfo('websitetoolboxforum') ["settings"]["communityUrl"];
                        $event->rules[$segement] = 'websitetoolboxforum/default/index';
                    }
                );
            }            
        }
        Event::on(\craft\services\Users::class, \craft\services\Users::EVENT_AFTER_ACTIVATE_USER, function(Event $event) {                         
                Websitetoolboxforum::getInstance()->sso->afterUserCreate($event);
             
        });
        Event::on(\craft\services\Elements::class, \craft\services\Elements::EVENT_AFTER_SAVE_ELEMENT, function(Event $event) {            
            if ($event->element instanceof \craft\elements\User) {
                if(isset($_POST['userId'])){                    
                    Websitetoolboxforum::getInstance()->sso->afterUpdateUser();
                }
            }
        });

        Event::on(\craft\services\Elements::class, \craft\services\Elements::EVENT_AFTER_DELETE_ELEMENT, function(Event $event) {
            if ($event->element instanceof \craft\elements\User) {                           
                Websitetoolboxforum::getInstance()->sso->afterDeleteUser($event->element->username);
                Websitetoolboxforum::getInstance()->sso->afterLogOut();
            }
        });  
        Event::on( \yii\base\Component::class, \craft\web\User::EVENT_AFTER_LOGIN, function(Event $event) {            
            Websitetoolboxforum::getInstance()->sso->afterLogin();
        });
        Event::on( \yii\base\Component::class, \craft\web\User::EVENT_AFTER_LOGOUT, function(Event $event) {            
            Websitetoolboxforum::getInstance()->sso->afterLogOut();
        });        
    }    
    protected function createSettingsModel(): ?\craft\base\Model{        
        return new Settings();
    }
    protected function settingsHtml(): ?string{        
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
    public function afterSaveSettings(): void{
        if(isset($_POST['settings']['forumUsername'])){ 
            $forumType  = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumEmbedded',false);            
            $forumUrl  = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumUrl',false);

            $userName               = $_POST['settings']['forumUsername'];
            $userPassword           = $_POST['settings']['forumPassword'];            
            
            
            $postData = array('action' => 'checkPluginLogin', 'username' => $userName,'password'=>$userPassword, 'plugin' => 'craft', 'websiteBuilder' => 'craftcms');           
            $result = $this->sso->sendApiRequest('POST',WT_SETTINGS_URL,$postData,'json');
            
            if(empty($result) || (isset($result->errorMessage) && $result->errorMessage != '')){
                if(empty($result)){
                    $errorMessage = 'Authentication fail for Websitetoolboxforum';
                }else{
                    $errorMessage = $result->errorMessage;
                }
                Craft::$app->getSession()->setError(Craft::t('websitetoolboxforum', $errorMessage));
                Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('settings/plugins/websitetoolboxforum'))->send();exit;
            }
            $deleteForumUrlRows     = Craft::$app->getProjectConfig()->remove('plugins.websitetoolboxforum.settings.forumUrl');
            $deleteForumApiKeyRows  = Craft::$app->getProjectConfig()->remove('plugins.websitetoolboxforum.settings.forumApiKey');
            
            $affectedForumUrlRows   = Craft::$app->getProjectConfig()->set('plugins.websitetoolboxforum.settings.forumUrl',$result->forumAddress); 

            $affectedForumApiKeyRows = Craft::$app->getProjectConfig()->set('plugins.websitetoolboxforum.settings.forumApiKey',$result->forumApiKey);
            if($forumUrl !='' && $forumType ==''){
                $embeddedPage = '';
            }else{
                $embeddedPage = 'forum';
            }
            
        } else{              
            $userName               = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumUsername',false);
            $userPassword           = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumPassword',false);
            $embeddedPage = '';
            if(isset($_POST['settings']['communityUrl']) && $_POST['settings']['forumEmbedded'] == 1){
                $embeddedPage = $_POST['settings']['communityUrl'];
                if($_POST['settings']['communityUrl'] == ''){
                    $embeddedPage = 'forum';
                }
                Craft::$app->getProjectConfig()->set('plugins.websitetoolboxforum.settings.communityUrl', trim($embeddedPage));
            }
            $postData               = array('action' => 'checkPluginLogin', 'username' => $userName,'password'=>$userPassword, 'plugin' => 'craft', 'websiteBuilder' => 'craftcms');            
            $result                 = $this->sso->sendApiRequest('POST',WT_SETTINGS_URL,$postData,'json');

            if(empty($result) || (isset($result->errorMessage) && $result->errorMessage != '')){
                if(empty($result)){
                    $errorMessage = 'Authentication fail for Websitetoolboxforum';
                }else{
                    $errorMessage = $result->errorMessage;
                }
                Craft::$app->getSession()->setNotice(Craft::t('websitetoolboxforum', $errorMessage));
                Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('settings/plugins/websitetoolboxforum'))->send();exit;
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
        $response       = Websitetoolboxforum::getInstance()->sso->sendApiRequest('POST',$RequestUrl,$postData,'json');         
        setcookie("forumLogoutToken", $response->authtoken, time() + 3600,"/");
        setcookie("forumLoginUserid", $response->userid, time() + 3600,"/");         
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
            if(strpos($siteUrl, 'index.php') > 0)
            {
                $embedUrl = $siteUrl.'?p='.$embeddedPage;
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
}