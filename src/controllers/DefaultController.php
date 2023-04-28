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
        $secret = Craft::$app->getProjectConfig()->get('plugins.websitetoolboxforum.settings.forumUsername');
        $postData = file_get_contents("php://input");
        $signatureHeader = $_SERVER['HTTP_X_SIGNATURE'];
        $signature = hash_hmac('sha256', $postData, $secret);
        if ($signature == $signatureHeader) {
            $data = json_decode($postData, true);
            if(isset($data['forum_url'])){
                Craft::$app->getProjectConfig()->set('plugins.websitetoolboxforum.settings.forumUrl', $data['forum_url']);    
                $response = ['status' => 200, 'message' => 'Community host updated successfully.'];
            }else{
                $response = ['status' => 301, 'message' => 'Invalid parameter received.'];
            }
        }
        else{
            $response = ['status' => 400, 'message' => 'You are not authorize user.'];
        }
        return json_encode($response, true);                
    }
}