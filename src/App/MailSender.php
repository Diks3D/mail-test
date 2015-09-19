<?php

namespace App;

use Zend\Http\PhpEnvironment\Request,
    Zend\Http\PhpEnvironment\Response,
    Zend\View\Model\ViewModel,
    Zend\View\Renderer\PhpRenderer,
    Zend\View\Resolver,
    Zend\Mail\Message,
    Zend\Mail\Transport\Sendmail,
    Zend\Mime;

use App\Form\Mail as MailForm,
    App\Model\FileStorage;
//use App\Form\InputFilter\Mail as MailValidator;

class MailSender
{
    /** @var Zend\Http\PhpEnvironment\Request */
    var $request;
    /** @var Zend\Http\PhpEnvironment\Response */
    var $response;
    /** @var Zend\View\Renderer\PhpRenderer */
    var $view;
    /** @var App\Model\FileStorage */
    var $fileStorage = null;
    
    /**
     * Consruct instance of mail sender class
     */
    public function __construct()
    {
        $this->request = new Request();
        $this->response = new Response();
        
        $this->view = $this->prepareView();
        $this->fileStorage = new FileStorage(ROOT_PATH . '/public/storage');
    }
    
    /**
     * Set php render, path of rendering templates and layout template
     * 
     * @return Zend\View\Renderer\PhpRenderer
     */
    protected function prepareView()
    {
        $resolver = new Resolver\AggregateResolver();
        $map = new Resolver\TemplateMapResolver(array(
            'layout' => __DIR__ . '/view/layout.phtml',
            'index' => __DIR__ . '/view/index.phtml',
        ));
        $resolver->attach($map);
        
        $renderer = new PhpRenderer();
        $renderer->setResolver($resolver);
        
        return $renderer;
    }
    
    /**
     * Add child view model to layout and render all to html format
     * 
     * @param ViewModel $viewModel
     * @return string
     */
    protected function render(ViewModel $viewModel)
    {
        $layoutModel = new ViewModel();
        $layoutModel->setTemplate('layout');
        
        $layoutModel->addChild($viewModel, 'content');
        
        foreach ($layoutModel as $child) {
            if ($child->terminate()) {
                continue;
            }
            $child->setOption('has_parent', true);
            $result = $this->view->render($child);
            
            $child->setOption('has_parent', null);
            $capture = $child->captureTo();
            if (!empty($capture)) {
                if ($child->isAppend()) {
                    $oldResult = $child->{$capture};
                    $layoutModel->setVariable($capture, $oldResult . $result);
                } else {
                    $layoutModel->setVariable($capture, $result);
                }
            }
        }

        return $this->view->render($layoutModel);
    }

    /**
     * Processing incoming HTTP request,
     * Sending mail if form is correct,
     * and return response from application
     */
    public function handle()
    {
        $form = new MailForm();
        
        if($this->request->isXmlHttpRequest()){
            
            $statusCode = null;
            $jsonContent = json_encode(array('message' => 'Something wrong'));
            $this->response->getHeaders()->addHeaders(array('Content-Type' => 'application/json'));
            
            $action = $this->request->getPost('action');
            switch($action){
                case 'clear':
                    $this->fileStorage->clearStorage();
                    $jsonContent = json_encode(array('message' => 'Store files cleared'));
                    break;
                case 'upload-files':
                    $uploadOk = true;
                    $files = $this->request->getFiles('files', false);
                    $uploadedFiles = array();
                    if ($files || count($files) !== 0){
                        foreach($files as $uploadedFileInfo){
                            if($uploadedFileInfo['error'] !== 0){
                                $statusCode = 400;
                                $jsonContent = json_encode(array('message' => 'One of uploaded file is wrong'));
                                break;
                            } else {
                                $uploadedFiles[] = array(
                                    'id' => $this->fileStorage->addFile($uploadedFileInfo['tmp_name']),
                                    'name' => $uploadedFileInfo['name'],
                                );
                            }
                        }
                    } else {
                        $uploadOk = false;
                        $statusCode = 400;
                        $jsonContent = json_encode(array('message' => 'Upload container is empty'));
                    }
                    //If all uploaded files ok - recive array with files names and ids
                    if($uploadOk){
                        $jsonContent = json_encode(array(
                            'message' => 'All uploaded files ok',
                            'files' => $uploadedFiles,
                        ));
                    } else {
                        //Else remove last uploaded files
                        foreach($uploadedFiles as $file){
                            $this->fileStorage->removeFile($file['id']);
                        }
                    }
                    break;
                case 'send':
                    $postData = $this->request->getPost();
                    $form->setData($postData);
                    if ($form->isValid()) {
                        try {
                            if ($this->sendMail($postData)) {
                                $this->fileStorage->clearStorage();
                                $jsonContent = json_encode(array(
                                    'code' => 1,
                                    'message' => 'Email already sent'
                                ));
                            }
                        } catch (\Zend\Mail\Exception\RuntimeException $e) {
                            $statusCode = 500;
                            $jsonContent = json_encode(array('message' => 'Server error, can not send email'));
                        }
                    } else {
                        $jsonContent = json_encode(array(
                            'code' => 0,
                            'message' => 'Fix errors in some fields',
                            'errors' => $form->getMessages(),
                        ));
                    }
                    break;
                default:
                    $statusCode = 400;
                    $jsonContent = json_encode(array('message' => 'Invalid request'));
            }
            
            //Set status code if set
            if($statusCode){
                $this->response->setStatusCode(400);
            }
            //Set JSON content to response
            $this->response->setContent($jsonContent);
            return $this->response;
        }
        
        $this->fileStorage->clearStorage();
        $viewModel = new ViewModel(array('form' => $form));
        $viewModel->setTemplate('index');
        
        $htmlContent = $this->render($viewModel);

        $this->response->setContent($htmlContent);
        
        return $this->response;
    }
    
    /**
     * Send mail with form data and uploaded attached files
     * 
     * @param array $formData
     */
    protected function sendMail($formData)
    {
        $mail = new Message();
        $mail->setEncoding("UTF-8");

        $text = new Mime\Part(strip_tags($formData['mailContent']));
        $text->type = "text/plain";

        $content = strip_tags($formData['mailContent'], '<b><strong><i><br><p><pre><hr><h1><h2><h3><h4><h5><h6><h7>');
        $html = new Mime\Part($content);
        $html->type = "text/html";
        
        $body = new Mime\Message();
        $body->setParts(array($text, $html));
        
        foreach ($formData['uploadedFiles'] as $fileInfo) {
            $fileFullPath = $this->fileStorage->getFile($fileInfo['id']);
            //Skip attacment if file is missing
            if(!$fileFullPath){
                continue;
            }
            
            $fileContent = fopen($fileFullPath, 'r');
            $attachment = new Mime\Part($fileContent);
            $attachment->type = 'application/octet-stream';
            $attachment->filename = $fileInfo['name'];
            $attachment->disposition = Mime\Mime::DISPOSITION_ATTACHMENT;
            $attachment->encoding = Mime\Mime::ENCODING_BASE64;
            
            $body->addPart($attachment);
        }

        $mail->setFrom('info@diks3d-web-lab.ru');
        $mail->addTo($formData['mailAddress']);
        $mail->setSubject($formData['mailSubject']);
        $mail->setBody($body);

        $transport = new Sendmail();
        $transport->send($mail);
        
        return true;
    }

}