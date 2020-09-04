<?php
/**
 * Website Toolbox Forum plugin for Craft CMS 3.x
 *
 * Single Sign On plugin for CraftCMS
 *
 * @link      https://websitetoolbox.com/
 * @copyright Copyright (c) 2019 websitetoolbox
 */

namespace websitetoolbox\websitetoolboxforum\assetbundles\websitetoolboxforum;
use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    websitetoolbox
 * @package   Websitetoolboxforum
 * @since     3.0.0
 */
class WebsitetoolboxforumAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */

    public function init()
    {
        $this->sourcePath = "@websitetoolbox/websitetoolboxforum/assetbundles/websitetoolboxforum/dist";
         $this->depends = [
            CpAsset::class,
        ];
         
        $this->js = [
            'js/Websitetoolboxforum.js',
              
        ];
        
        parent::init();
    }
}

