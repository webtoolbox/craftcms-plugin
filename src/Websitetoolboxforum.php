<?php
/**
 * Website Toolbox Forum plugin for Craft CMS 3.x
 * Single Sign On Cloud Based plugin for CraftCMS
 * @link      https://websitetoolbox.com/
 * @copyright Copyright (c) 2020 Website Toolbox
 */
namespace websitetoolbox\websitetoolboxforum;
use websitetoolbox\websitetoolboxforum\services\Sso as SsoService;
use websitetoolbox\websitetoolboxforum\models\Settings;
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
define('WT_SETTINGS_URL', 'https://www.websitetoolbox.com/tool/members/mb/settings');

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
        $token = Craft::$app->getSession()->get(Craft::$app->getUser()->tokenParam);
        if(!$token && isset($_COOKIE['forumLogoutToken'])){
            Event::on(View::class, View::EVENT_END_BODY, function(Event $event) {
                    $forumUrl = Craft::$app->getPlugins()->getStoredPluginInfo('websitetoolboxforum') ["settings"]["forumUrl"];
                    echo '<img src='.$forumUrl.'/register/logout?authtoken='.$_COOKIE['forumLogoutToken'].'" border="0" width="0" height="0" alt="" id="imageTag">';
                    Websitetoolboxforum::getInstance()->sso->resetCookieOnLogout();
            });
        } 
        if(!empty(Craft::$app->getPlugins()->getStoredPluginInfo('websitetoolboxforum') ["settings"]["forumUrl"])){           
            Event::on(View::class, View::EVENT_BEFORE_RENDER_TEMPLATE,function (Event $event) {
                $token = Craft::$app->getSession()->get(Craft::$app->getUser()->tokenParam); 
                if(!$token){
                    Websitetoolboxforum::getInstance()->sso->afterLogOut();
                }
                $forumType  = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumEmbedded',false);
                $forumUrl   = Craft::$app->getPlugins()->getStoredPluginInfo('websitetoolboxforum') ["settings"]["forumUrl"]; 
                //$view       = Craft::$app->getView();                 
               // echo '<script>' . $view->renderPhpFile(__DIR__ . '/assetbundles/websitetoolboxforum/dist/js/websitetoolboxforum.js') . '</script>';             
                if($forumType == 1){ 
                    $jsRender = Websitetoolboxforum::getInstance()->sso->renderJsScriptEmbedded($forumUrl);
                 }else{ 
                    $jsRender = Websitetoolboxforum::getInstance()->sso->renderJsScriptUnEmbedded();
                }
                
                $view = Craft::$app->getView();
                $view ->registerJs($jsRender);
            });
        }
        Event::on(\craft\services\Elements::class, \craft\services\Elements::EVENT_AFTER_SAVE_ELEMENT, function(Event $event) {
            if ($event->element instanceof \craft\elements\User) {
                if(isset($_POST['userId'])){
                    Websitetoolboxforum::getInstance()->sso->afterUpdateUser();
                }else{
                    Websitetoolboxforum::getInstance()->sso->afterUserCreate($event);
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
        } else{
            $userName               = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumUsername',false);
            $userPassword           = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumPassword',false); 
        } 
        $postData               = array('action' => 'checkPluginLogin', 'username' => $userName,'password'=>$userPassword,'plugin' => 'craft',);
        $result                 = $this->sso->sendApiRequest('POST',WT_SETTINGS_URL,$postData,'query'); 
        Craft::$app->getProjectConfig()->set("plugins.websitetoolboxforum.settings.forumUrl",$result->forumAddress); 
        Craft::$app->getProjectConfig()->set("plugins.websitetoolboxforum.settings.forumApiKey",$result->forumApiKey); 
        $RequestUrl   = $result->forumAddress."/register/setauthtoken";
        $userEmail    = Craft::$app->getUser()->getIdentity()->email;
        $userId       = Craft::$app->getUser()->getIdentity()->id;
        $postData     = array('type'=>'json','apikey' => $result->forumApiKey, 'user' => $userName,'email'=>$userEmail,'externalUserid'=>$userId);
        $result       = Websitetoolboxforum::getInstance()->sso->sendApiRequest('POST',$RequestUrl,$postData,'json'); 
        setcookie("forumLogoutToken", $result->authtoken, time() + 3600,"/");
        setcookie("forumLoginUserid", $result->userid, time() + 3600,"/"); 
    }
}
 
