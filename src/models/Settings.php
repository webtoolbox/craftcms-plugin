<?php
/**
 * Website Toolbox Forum plugin for Craft CMS 4.x
 * Single Sign On Cloud Based plugin for CraftCMS
 * @link      https://websitetoolbox.com/
 * @copyright Copyright (c) 2020 Website Toolbox
 */
namespace websitetoolbox\websitetoolboxforum\models;
use websitetoolbox\websitetoolboxforum\Websitetoolboxforum;
use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;
use yii\behaviors\AttributeTypecastBehavior;
use craft\helpers\UrlHelper;
/**
 * @author    Website Toolbox
 * @package   Websitetoolboxforum
 * @since     4.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================
    public $forumUsername         = '';
    public $forumPassword         = '';
    public $forumEmbedded         = 1;
    public $forumApiKey           = '';
    public $forumUrl              = '';  
    public $communityUrl          = 'forum';  
  
    // Public Methods
    protected function makeUrl($page){
        $siteUrl = UrlHelper::siteUrl();
        if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') 
            $url = "https://"; 
        else 
            $url = "http://";             
        $url.= $_SERVER['HTTP_HOST']; 
        $url.= $_SERVER['REQUEST_URI'];  
        if(strpos($url, 'index.php?') >= 0){
            $embedPage = $siteUrl.'index.php?p='.$page;
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
        if(!preg_match('/^[a-z\/_]+$/', $value)) {
            $message = 'only alphabet in lowercase letters allow.';
            $this->addError($attribute, $message);
            return;
        }
        // Check if already exist url and not contain embedded code
        if($this->urlExists($file)){
            $searchFor = "<div id='wtEmbedCode'>";
            $searchDiv = '<div id="wtEmbedCode">';
            header('Content-Type: text/plain');
            $contents = file_get_contents($file);        
            $pattern1 = preg_quote($searchFor, '/');
            $pattern2 = preg_quote($searchDiv, '/');
            $pattern1 = "/^.*$pattern1.*\$/m";
            $pattern2 = "/^.*$pattern2.*\$/m";
            if (!preg_match_all($pattern1, $contents, $matches) && !preg_match_all($pattern2, $contents, $matches))
            {
                $message = "The page doesn't content embed code. Pleae add <div id=\"wtEmbedCode\"></div> to the page.";
                $this->addError($attribute, $message);
                return;
            }
            /*else{ echo "Found matches:\n"; echo implode("\n", $matches[0]);exit;}*/
        }
        
    }
    /**   * @inheritdoc     */
    public function rules(): array{
        return [
            [['forumUsername', 'forumPassword','forumApiKey','forumUrl'], 'string'],
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
        if (Websitetoolboxforum::$craft31) {
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
