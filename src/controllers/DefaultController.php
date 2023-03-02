<?php
namespace websitetoolbox\websitetoolboxcommunity\controllers;
use Craft;
use craft\web\Controller;
use craft\web\View;
use yii\web\Response;

class DefaultController extends Controller
{
    protected array|bool|int $allowAnonymous = ['index', 'do-something'];
    // Front end action /your/route
    public function actionIndex(): Response
    {
        $variables = [
            'key' => 'value'
        ];
        return $this->renderTemplate(
            'websitetoolboxcommunity/forum',
            $variables,
            View::TEMPLATE_MODE_CP
        );
    }
}