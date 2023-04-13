<?php
namespace websitetoolbox\community\controllers;
use Craft;
use craft\web\Controller;
use craft\web\View;
use yii\web\Response;

class DefaultController extends Controller
{
    protected array|bool|int $allowAnonymous = ['index', 'do-something', 'webhook'];
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
    /**
     * @uses function to update plugin forum URL on change domain from wt admin(using webhook call)
     * @param forum_url - latest domain 
     */
    public function actionWebhook()
    {
        $payload = file_get_contents('php://input');
        $signature = @$_SERVER['HTTP_HMAC'];
        $secretKey = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.secretKey');
        if ($signature == $secretKey) {
            if(isset($_REQUEST['forum_url'])){
                Craft::$app->getProjectConfig()->set('plugins.websitetoolboxforum.settings.forumUrl', $_REQUEST['forum_url']);    
                $response = ['status' => 200, 'message' => 'Community host updated successfully.'];
            }else{
                $response = ['status' => 301, 'message' => 'Invalid parameter received.'];
            }
        }else{
            $response = ['status' => 400, 'message' => 'You are not authorize user.'];
        }
        return json_encode($response, true);                
    }
}