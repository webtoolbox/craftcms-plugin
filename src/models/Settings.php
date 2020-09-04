<?php
/**
 * Website Toolbox Forum plugin for Craft CMS 3.x
 *
 * Single Sign On Cloud plugin for CraftCMS
 *
 * @link      https://websitetoolbox.com/
 * @copyright Copyright (c) 2019 Website Toolbox
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

    /**
     * @var string Websitetoolbox Forums jsConnect Client ID
     */
    public $forumUsername = '';

    /**
     * @var string Websitetoolbox Forums jsConnect Secret
     */
    public $forumPassword = '';

    /**
     * @var string The hash algorithm to be ued when signing requests
     */
    public $forumEmbedded         = '';
    public $loginUrl              = '';
    public $logOutUrl             = '';
    public $userRegistrationUrl   = '';
   
    public $forumApiKey           = '';
    public $forumOutputUrl        = '';
    public $forumUrl              = '';
    
 
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['forumUsername', 'forumPassword','forumEmbedded','loginUrl','logOutUrl','userRegistrationUrl','forumOutputUrl','forumUrl','forumApiKey'], 'string'],
            [['forumUsername', 'forumPassword','forumEmbedded','loginUrl','logOutUrl','userRegistrationUrl','forumOutputUrl','forumUrl','forumApiKey'], 'default', 'value' => ''],
            
        ];
    }

    /**
     * @inheritdoc
     */
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
                    'forumUrl',
                    'forumApiKey',
                    'forumUrl',
                    'forumApiKey'
                ],
            ];
        }

        return $behaviors;
    }
}
