<?php
namespace websitetoolbox\websitetoolboxforum\controllers;
use Craft;
use craft\web\Controller;
use craft\web\View;
use yii\web\Response;

class DefaultController extends Controller
{
    // Front end action /your/route
    public function actionIndex(): Response
    {
        $variables = [
            'key' => 'value'
        ];
        return $this->renderTemplate(
            'websitetoolboxforum/forum',
            $variables,
            View::TEMPLATE_MODE_CP
        );
    }
}