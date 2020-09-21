<?php
/**
 * Website Toolbox Forum plugin for Craft CMS 3.x
 * Single Sign On Cloud Based plugin for CraftCMS
 * @link      https://websitetoolbox.com/
 * @copyright Copyright (c) 2020 Website Toolbox
 */
namespace websitetoolbox\websitetoolboxforum\models;
use websitetoolbox\websitetoolboxforum\Websitetoolboxforum;
use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;
use yii\behaviors\AttributeTypecastBehavior;
/**
 * @author    Website Toolbox
 * @package   Websitetoolboxforum
 * @since     3.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================
    public $forumUsername = '';
    public $forumPassword = '';
    public $forumEmbedded         = '';
    public $loginUrl              = '';
    public $logOutUrl             = '';
    public $userRegistrationUrl   = '';
    public $forumOutputUrl        = '';
    // Public Methods
    // =========================================================================
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['forumUsername', 'forumPassword','forumEmbedded','loginUrl','logOutUrl','userRegistrationUrl','forumOutputUrl'], 'string'],
            [['forumUsername', 'forumPassword','forumEmbedded','loginUrl','logOutUrl','userRegistrationUrl','forumOutputUrl'], 'default', 'value' => ''],
            
        ];
    }
    public function behaviors()
    {
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
                    'forumEmbedded',
                    'loginUrl',
                    'logOutUrl',
                    'userRegistrationUrl',
                    'forumOutputUrl',
                ],
            ];
        }
        return $behaviors;
    }
}
