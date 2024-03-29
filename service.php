<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
require_once 'AuthController.php';
require_once 'WhatsAppAPIController.php';
require_once 'TelegramAPIController.php';
require_once dirname(__FILE__) . '/Model/ActivationTokenModel.php';


class ServiceController extends AuthController {

    private $successMsg;
    function __construct() {
        parent::__construct();
        $this->successMsg = "Your number has been verified successfully! ";
        $this->successMsg .="Now all you have to do is send your pictures to this number and they will automatically show up in your account! ";
        $this->successMsg .="Please note: Pictures can be sent at all times even when you are not logged into your account and they will show up the ";
        $this->successMsg .="next time you log in";
    }

    public function getNew() {
        require_once dirname(__FILE__) . '/Model/ServiceProviderModel.php';

        $serviceProvider = new ServiceProvider();

        $this->pageData['services'] = $serviceProvider->getProviderList();
        $this->loadView('service_form', $this->pageData);
    }

    public function setRemove() {

    }

    public function setActive() {

    }

    public function sendCode() {

        require_once dirname(__FILE__) . '/Model/ActivationTokenModel.php';
        $serviceProviderId = $_POST['service_provider_id'];
        $serviceUserId     = $_POST['service_user_id'];

        if ($serviceProviderId == NULL || $serviceUserId == NULL) {
            $data = [
                'status'  => FALSE,
                'message' => 'Service Name or Service ID is Empty',
            ];
            echo json_encode($data);
            die;
        }

        require_once dirname(__FILE__) . '/Model/UserServiceModel.php';

        $serviceUser = new UserService();
        $unique      = $serviceUser->findUserService($serviceProviderId, $serviceUserId);

        if ($unique > 0) {
            $deactivate  = $serviceUser->findUserService($serviceProviderId, $serviceUserId, 'deactivate');
            $serviceUser = $serviceUser->getUserIdByProviderAndService($serviceProviderId, $serviceUserId);

            if ($deactivate > 0 && $_SESSION['uId'] == $serviceUser) {
                if ($serviceProviderId == 1) {
                    $this->telegram($serviceProviderId, $serviceUserId);
                } elseif ($serviceProviderId == 2) {
                    $this->whatsApp($serviceProviderId, $serviceUserId);
                }


                /*  $data = [
                      'status'  => FALSE,
                      'message' => 'You already register this Service, you may active this by sending "Active" from your cell',
                  ];
                  echo json_encode($data);*/
                die;
            }

            $data = [
                'status'  => FALSE,
                'message' => 'We\'re sorry, this number is already registered with a different account! To unregister a number from an account simply send
                 the word "deactivate" to the ezeepix number (1646#####) and then you can add this number to a different account.',
            ];
            echo json_encode($data);
            die;
        }

        if ($serviceProviderId == 1) {
            $this->telegram($serviceProviderId, $serviceUserId);
        } elseif ($serviceProviderId == 2) {
            $this->whatsApp($serviceProviderId, $serviceUserId);
        }

    }
    public function welcomeMsg(){

            require_once dirname(__FILE__) . '/Model/ActivationTokenModel.php';
            $serviceProviderId = $_POST['service_provider_id'];
            $serviceUserId     = $_POST['service_user_id'];

            if ($serviceProviderId == NULL || $serviceUserId == NULL) {
                $data = [
                    'status'  => FALSE,
                    'message' => 'Service Name or Service ID is Empty',
                ];
                echo json_encode($data);
                die;
            }

            require_once dirname(__FILE__) . '/Model/UserServiceModel.php';

            $serviceUser = new UserService();
            $unique      = $serviceUser->findUserService($serviceProviderId, $serviceUserId);

            if ($unique > 0) {
                $deactivate  = $serviceUser->findUserService($serviceProviderId, $serviceUserId, 'deactivate');
                $serviceUser = $serviceUser->getUserIdByProviderAndService($serviceProviderId, $serviceUserId);

                if ($deactivate > 0 && $_SESSION['uId'] == $serviceUser) {
                    $data = [
                        'status'  => FALSE,
                        'message' => 'You already register this Service, you may active this by sending "Active" from your cell',
                    ];
                    echo json_encode($data);
                    die;
                }

                $data = [
                    'status'  => FALSE,
                    'message' => 'This Service ID already register with this Service Name',
                ];
                echo json_encode($data);
                die;
            }

            if ($serviceProviderId == 1) {
                $this->telegram($serviceProviderId, $serviceUserId);
            } elseif ($serviceProviderId == 2) {
                $this->whatsApp($serviceProviderId, $serviceUserId);
            }


    }
    function telegram($serviceProviderId = NULL, $serviceUserId = NULL) {
        $API = new TelegramAPIController();

        if ($serviceUserId == NULL) {
            echo "<pre>";
            print_r($API->getContactList());
            die;
        }

        $token = new ActivationToken();
        $code  = $this->getConfirmCode(6);

        $data = [
            'u_id'                => $_SESSION['uId'],
            'service_provider_id' => $serviceProviderId,
            'token'               => $code,
        ];

        if ($token->insert($data) < 0) {
            $data = [
                'status'  => FALSE,
                'message' => 'Code Sending Failed !',
            ];
            echo json_encode($data);
            die;
        }

        $contacts = $API->addContact($serviceUserId);

        if (empty($contacts)) {
            $data = [
                'status'  => FALSE,
                'message' => 'Phone number not Found in Telegram !',
            ];
            echo json_encode($data);
            die;
        }

        $API->target  = $contacts[0]->print_name;
        $API->message = 'Thank you for choosing ezeepix your verification code is : ' . $code;
        $status       = $API->sendMessage($API->target, $API->message);

        if ($status) {
            $data = [
                'status'  => TRUE,
                'message' => 'Verification Code Sent Successfully!',
            ];
            echo json_encode($data);
        } else {
            $data = [
                'status'  => FALSE,
                'message' => 'Verification Code Sent Failed! Try again',
            ];
            echo json_encode($data);
        }

    }

    function whatsApp($serviceProviderId, $serviceUserId) {

        $API   = new WhatsAppAPIController();
        $token = new ActivationToken();
        $code  = $this->getConfirmCode(6);
        $data  = [
            'u_id'                => $_SESSION['uId'],
            'service_provider_id' => $serviceProviderId,
            'token'               => $code,
        ];

        if ($token->insert($data) < 0) {
            $data = [
                'status'  => FALSE,
                'message' => 'Code Sending Failed !',
            ];
            echo json_encode($data);
            die;
        }

        $API->target  = $serviceUserId;

        $u =  $API->target;
        if (!is_array($u)) {
            $u = [$u];
        }
        $numbers = [];
        foreach ($u as $number) {
            if ($number[0] != '+') {
                //add leading +
                $number = "+$number";
            }
            $numbers[] = $number;
        }



        $API->message = 'Thank you for choosing ezeepix your verification code is : ' . $code;
        $status       = $API->sendMessage($API->target, $API->message);

        if ($status != NULL) {
            $data = [
                'status'  => TRUE,
                'message' => 'Verification Code Send Successfully',
            ];
            echo json_encode($data);
        } else {
            $data = [
                'status'  => FALSE,
                'message' => 'Verification Code Send Failed Try again',
            ];
            echo json_encode($data);
        }
    }
    function whatsAppWellComeMsg($serviceUserId) {

        $API   = new WhatsAppAPIController();


        $API->target  = $serviceUserId;
        $status       = $API->sendMessage($API->target,$this->successMsg);

        return ($status != NULL);
    }
    public function submitCode() {
        require_once dirname(__FILE__) . '/Model/ActivationTokenModel.php';

        $serviceProviderId = $_POST['service_provider_id'];
        $serviceUserId     = $_POST['service_user_id'];
        $uId               = $_SESSION['uId'];
        $token             = $_POST['token'];

        $checkToken = new ActivationToken();

        $result = $checkToken->checkToken($serviceProviderId, $uId, $token);

        if ($result <= 0) {
            $data = [
                'status'  => FALSE,
                'message' => 'Your Code is Invalid or Expired',
            ];
            echo json_encode($data);
            die;
        }

        require_once dirname(__FILE__) . '/Model/UserServiceModel.php';

        $userService = new UserService();
        $data        = [
            'service_user_id'     => $serviceUserId,
            'service_provider_id' => $serviceProviderId,
            'u_id'                => $uId,
            'active'              => 1,
        ];

        $flag=$userService->checkAndUpdate($data);



        if($flag==1){
            $data = [
                'status'  => TRUE,
                'message' => 'Your ID Verified SuccessFully !'
            ];
            if ($serviceProviderId == 2) {
                $this->whatsAppWellComeMsg($serviceUserId);
            }
            echo json_encode($data);
            die;
        }



        $insertId = $userService->insert($data);

        if ($insertId <= 0) {
            $data = [
                'status'  => FALSE,
                'message' => 'System failed to register your Service'
            ];
            echo json_encode($data);
            die;
        }

        $data = [
            'status'  => TRUE,
            'message' => 'Your ID Verified SuccessFully !'
        ];
        if ($serviceProviderId == 2) {
            $this->whatsAppWellComeMsg($serviceUserId);
        }
        echo json_encode($data);
    }

    function getConfirmCode($length) {
        $characters       = '0123456789abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $code             = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, $charactersLength - 1)];
        }

        return $code;
    }

    function deactiveUserService() {
        $serviceProviderId = $_POST['service_provider_id'];
        $service_user_id   = $_POST['service_user_id'];

        require_once dirname(__FILE__) . '/Model/UserServiceModel.php';

        $userService   = new UserService();
        $selectService = $userService->haveServiceIdByu_id($serviceProviderId, $service_user_id, $this->userInfo['uId']);

        if (empty($selectService)) {
            $this->respData['status'] = FALSE;
            $this->respData['msg']    = "You don't have to privilege to do the operation !";
            echo json_encode($this->respData);

            return;
        } elseif (!$selectService['active']) {
            $this->respData['status'] = FALSE;
            $this->respData['msg']    = "Your Service already deactivated !";
            echo json_encode($this->respData);
            die;
        }

        $status = $userService->deactivateService($serviceProviderId, $service_user_id, 0);

        if ($status > 0) {
            $this->respData['status'] = TRUE;
            $this->respData['msg']    = "Your account has been deactivated !";
        } else {
            $this->respData['status'] = FALSE;
            $this->respData['msg']    = "System unable to deactivated your service try again later !";
        }
        echo json_encode($this->respData);
    }


    function activateService() {
        $serviceProviderId = $_POST['service_provider_id'];
        $service_user_id   = $_POST['service_user_id'];

        require_once dirname(__FILE__) . '/Model/UserServiceModel.php';

        $userService   = new UserService();
        $selectService = $userService->haveServiceIdByu_id($serviceProviderId, $service_user_id, $this->userInfo['uId']);

        if (empty($selectService)) {
            $this->respData['status'] = FALSE;
            $this->respData['msg']    = "You don't have to privilege to do the operation !";
            echo json_encode($this->respData);

            return;
        } elseif ($selectService['active']) {
            $this->respData['status'] = FALSE;
            $this->respData['msg']    = "Your Service already activated !";
            echo json_encode($this->respData);
            die;
        }

        $status = $userService->activateService($serviceProviderId, $service_user_id, 1);

        if ($status > 0) {
            $this->respData['status'] = TRUE;
            $this->respData['msg']    = "Your account has been activated !";
        } else {
            $this->respData['status'] = FALSE;
            $this->respData['msg']    = "System unable to activated your service try again later !";
        }
        echo json_encode($this->respData);
    }


    public function process() {
        $method = (isset($_GET['r'])) ? $_GET['r'] : "";
        switch ($method) {
            case 'sendCode';
                $this->sendCode();
                break;
            case 'submitCode';
                $this->submitCode();
                break;
            case 'new';
                $this->getNew();
                break;
            case 'remove';
                $this->setRemove();
                break;
            case 'test';
                $this->telegram();
                break;
            case 'deactiveUserService';
                $this->deactiveUserService();
                break;
            case 'activateUserService';
                $this->activateService();
                break;
            default;
                header('Location: dashboard.php?r=index');
                break;
        }
        session_write_close();
    }
}

$service = new ServiceController();
$service->process();