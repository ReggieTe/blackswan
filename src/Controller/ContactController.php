<?php 
namespace App\Controller;

use App\Entity\Contact ;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

class ContactController extends AbstractController
{
    public $formVariables;
    
  /**
     * @Route("/contact", name="contact", methods="GET")
     */
    public function index(Request $request): Response
    {
       $formVariables = array(            
            "name" => array(
                "value" => $request->query->get("name"),
                "error" => "Name required.Please try again",
                "invalid" => "Invalid name.Please try again",
                "pattern" => "/^[A-Za-z0-9 ]{2,60}$/"),            
            "phone" => array(
                "value" => $request->query->get("phone"),
                "error" => "phone required.Please try again",
                "invalid" => "invalid phone.Please try again",
                "exist" => "Phone is already taken.Please try again",
                "pattern" => "/^[+0-9]{6,60}$/"),
            "email" => array(
                "value" => $request->query->get("email"),
                "error" => "Email required.Please try again",
                "invalid" => "Invalid email.Please try again",
                "exist" => "Email is already taken.Please try again",
                "pattern" => "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/"),
            "message" => array(
                    "value" => $request->query->get("message"),
                    "error" => "Message required.Please try again",
                    "invalid" => "invalid message.Please try again",
                    "pattern" => "/^[A-Za-z ]{2,60}$/"
                ),
            );
            $this->formVariables = $formVariables;
        
        foreach($formVariables as $key => $variable)
        {
            if (!empty($variable['value'])) {
                if (!preg_match($variable['pattern'], $variable['value'])) {
                    return $this->page($variable['invalid']);
                }
            }else{
                return $this->page($variable['error']);
            } 
        }
        //Save details
        if($this->save())
        {   //Send Email
            if($this->sendMail())
            {
                //Reset
                $this->resetForm();
                return $this->page("Message sent successfully"); 
            }
            return $this->page("Failed to process form ,Try again later"); 
        }
        return $this->page("Failed to process form ,Try again later");                
    }
   
    /**
     * @Route("/contact/save")
     */
    public function save()
    {

        $entityManger = $this->getDoctrine()->getManager();
        $contact = new Contact();
        $contact->setName($this->formVariables['name']['value']);
        $contact->setPhone($this->formVariables['phone']['value']);
        $contact->setEmail($this->formVariables['email']['value']);
        $contact->setMessage($this->formVariables['message']['value']);
        $entityManger->persist($contact);
        $entityManger->flush();
        return true;
    }

    /**
     * @Route("/contact/add", name="add_contact", methods={"GET"})
     */
    public function add(Request $request): JsonResponse
    {
        $formVariables = array(            
            "name" => array(
                "value" => !empty($request->query->get('name'))?$request->query->get('name'):'',
                "error" => "Name required.Please try again",
                "invalid" => "Invalid name.Please try again",
                "pattern" => "/^[A-Za-z0-9 ]{2,60}$/"),            
            "phone" => array(
                "value" => !empty($request->query->get('phone'))?$request->query->get('phone'):'',
                "error" => "phone required.Please try again",
                "invalid" => "invalid phone.Please try again",
                "exist" => "Phone is already taken.Please try again",
                "pattern" => "/^[+0-9]{6,60}$/"),
            "email" => array(
                "value" => !empty($request->query->get('email'))?$request->query->get('email'):'',
                "error" => "Email required.Please try again",
                "invalid" => "Invalid email.Please try again",
                "exist" => "Email is already taken.Please try again",
                "pattern" => "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/"),
            "message" => array(
                    "value" => !empty($request->query->get('message'))?$request->query->get('message'):'',
                    "error" => "Message required.Please try again",
                    "invalid" => "invalid message.Please try again",
                    "pattern" => "/^[A-Za-z ]{2,60}$/"
                ),
            );
            $this->formVariables = $formVariables;
        
        foreach($formVariables as $key => $variable)
        {
            if (!empty($variable['value'])) {
                if (!preg_match($variable['pattern'], $variable['value'])) {
                    return $this->apiResponse($variable['invalid']);
                }
            }else{
                   return $this->apiResponse($variable['error']);            
            } 
        }
        
        if($this->save())
        {
            return $this->apiResponse("Message sent successfully",true);
        }
        return $this->apiResponse();   
    }

    public function apiResponse($message="Error processing data.Try again",$success="false")
    {
        return new JsonResponse(['success' => $success,'message' => $message], Response::HTTP_CREATED);
    } 
    public function page($message="")
    {
        return $this->render('contact/index.html.twig',[ "message" => $message,"form"=>$this->formVariables]);
    }
    public function resetForm()
    {
        foreach($this->formVariables as $key => $value)
        {
            $this->formVariables[$key]['value']=''; 
        }
    }
    public function sendMail()
    {
        return true;
        $mail = new PHPMailer(true);
        try {
            //Server settings
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
            $mail->isSMTP();                                            //Send using SMTP
            $mail->Host       = 'smtp.example.com';                     //Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
            $mail->Username   = 'user@example.com';                     //SMTP username
            $mail->Password   = 'secret';                               //SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         //Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
            $mail->Port       = 587;                                    //TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

            //Recipients
            $mail->setFrom($this->formVariables['email']['value'], $this->formVariables['name']['value']);
            $mail->addAddress('admin@blackswan.com', 'Admin');     //Admin copy
            $mail->addAddress($this->formVariables['email']['value'], $this->formVariables['name']['value']);     //customer copy 
            $mail->addReplyTo('admin@blackswan.com', 'Admin');
        
            //Content
            $mail->isHTML(true);                                 
            $mail->Subject = 'Form Feedback';
            $mail->Body    = $this->formVariables['message']['value'];
            $mail->AltBody = $this->formVariables['message']['value'];
            $mail->send();            
            return true;

            
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            return false;
        }       
    }
}