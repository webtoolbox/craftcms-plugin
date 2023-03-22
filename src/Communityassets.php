<?php 
namespace websitetoolbox\community;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class Communityassets extends AssetBundle
{
    public function init()
    {
        // define the path that your publishable resources live
        $this->sourcePath = '@websitetoolbox/community/resources';

        // define the dependencies
        $this->depends = [
            CpAsset::class,
        ];

        // define the relative path to CSS/JS files that should be registered with the page
        // when this asset bundle is registered
        $this->css = [
            'style.css',
        ];

        parent::init();
    }
}