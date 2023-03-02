<?php
/**
 * Website Toolbox Forum plugin for Craft CMS 4.x
 * Single Sign On Cloud Based plugin for CraftCMS
 * @link      https://websitetoolbox.com/
 * @copyright Copyright (c) 2020 Website Toolbox
 */
namespace websitetoolbox\websitetoolboxcommunity\models;
use websitetoolbox\websitetoolboxcommunity\Websitetoolboxcommunity;
use Craft;
use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;
use yii\behaviors\AttributeTypecastBehavior;
use craft\helpers\UrlHelper;
/**
 * @author    Website Toolbox
 * @package   Websitetoolboxcommunity
 * @since     4.0.0
 */
class Settings extends Model
{
    // Public Properties    
    public $forumUsername         = '';
    public $forumPassword         = '';
    public $forumEmbedded         = 1;
    public $forumApiKey           = '';
    public $forumUrl              = '';  
    public $communityUrl          = 'community';  
  
    // Public Methods
    protected function makeUrl($page){
        $siteUrl = UrlHelper::siteUrl();                
        if(strpos($siteUrl, 'index.php') >= 0){
            $pageTrigger = Craft::$app->getConfig()->general->pageTrigger;
            $embedPage = $siteUrl.'?'.$pageTrigger.'='.$page;
        }else{
            $embedPage = $siteUrl.$page;
        }        
        return $embedPage;
    }
    public function urlExists($url) {
        $file_headers = @get_headers($url);
        if(!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found') { 
            return false;
        }else {
            return true;
        }
    }
    public function validateUrl($attribute) {        
        $value = $this->$attribute;        
        $file = $this->makeUrl($value);
        // check url is valid or not
        if(!preg_match('/^[a-z0-9\/\_-]+$/', $value)) {            
            $message = 'only alphabets, dash(-) and, underscore(_) are allowed.';
            $this->addError($attribute, $message);
            return;
        }        
    }
    /**   * @inheritdoc     */
    public function rules(): array{
        return [
            [['forumUsername', 'forumPassword'], 'required'],
            [['forumApiKey','forumUrl'], 'string'],
            ['communityUrl', 'validateUrl']
        ];
    }
    public function behaviors(): array{
        // Keep any parent behaviors
        $behaviors = parent::behaviors();
        // Add in the AttributeTypecastBehavior
        $behaviors['typecast'] = [
            'class' => AttributeTypecastBehavior::class,
            // 'attributeTypes' will be composed automatically according to `rules()`
        ];
        // If we're running Craft 3.1 or later, add in the EnvAttributeParserBehavior
        if (Websitetoolboxcommunity::$craft31) {
            $behaviors['parser'] = [
                'class' => EnvAttributeParserBehavior::class,
                'attributes' => [
                    'forumUsername',
                    'forumPassword',
                    'forumApiKey',
                    'forumUrl',
                    'communityUrl'
                ],
            ];
        }
        return $behaviors;
    }
}
