<?php
/**
 * Pi Engine (http://piengine.org)
 *
 * @link            http://code.piengine.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://piengine.org
 * @license         http://piengine.org/license.txt BSD 3-Clause License
 */

namespace Module\Apis\Controller\Front;

use Pi;
use Pi\Authentication\Result;
use Pi\Mvc\Controller\ActionController;

/**
 * @author Hossein Azizabadi <azizabadi@faragostaresh.com>
 */
class UserController extends ActionController
{
    public function checkAction()
    {
        // Set result
        $result = [
            'status'  => 0,
            'message' => '',
        ];
        // Set template
        $this->view()->setTemplate(false)->setLayout('layout-content');
        // Get info from url
        $module = $this->params('module');
        $token  = $this->params('token');
        $order  = $this->params('order');
        // Check module
        if (Pi::service('module')->isActive('user')) {
            // Check config
            $config = Pi::service('registry')->config->read($module);
            if ($config['active_user']) {
                // Check token
                $check = Pi::api('token', 'tools')->check($token, $module, 'api');
                if ($check['status'] == 1) {

                    // Load language
                    Pi::service('i18n')->load(['module/user', 'default']);

                    // Get session id
                    $id = $this->params('id', '');

                    // Check id set or not
                    if (!empty($id)) {
                        // Start session
                        $session = Pi::model('session')->find($id);
                        if ($session) {
                            // Old method for pi 2.4.0
                            /*
                            session_id($id);
                            Pi::service('session')->manager()->start();
                            */
                            // New method for pi 2.5.0
                            $session = $session->toArray();
                            Pi::service('session')->manager()->start(false, $session['id']);
                        }
                    }

                    // Check user has identity
                    if (Pi::service('user')->hasIdentity()) {
                        // Get user
                        $user = Pi::user()->get(Pi::user()->getId(), [
                            'id', 'identity', 'name', 'email',
                        ]);
                        // Set result
                        $result = [
                            'check'     => 1,
                            'uid'       => $user['id'],
                            'identity'  => $user['identity'],
                            'email'     => $user['email'],
                            'name'      => $user['name'],
                            'avatar'    => Pi::service('user')->avatar($user['id'], 'large', false),
                            'sessionid' => Pi::service('session')->getId(),
                        ];
                        // Set order info
                        if ($order == 1 && Pi::service('module')->isActive('order')) {
                            // Load language
                            Pi::service('i18n')->load(['module/order', 'default']);

                            $credit   = Pi::api('credit', 'order')->getCredit();
                            $invoices = Pi::api('invoice', 'order')->getInvoiceFromUser($user['id'], true);
                            $score    = Pi::api('invoice', 'order')->getInvoiceScore($user['id']);

                            $result['credit_amount']      = $credit['amount'];
                            $result['credit_amount_view'] = $credit['amount_view'];
                            $result['invoice_count']      = count($invoices);
                            $result['invoice_count_view'] = _number(count($invoices));
                            $result['score_type']         = $score['type'];
                            $result['score_amount']       = $score['amount'];
                            $result['score_amount_view']  = Pi::api('api', 'order')->viewPrice($score['amount']);
                        }
                    } else {
                        $result = [
                            'check'     => 0,
                            'uid'       => Pi::user()->getId(),
                            'identity'  => Pi::user()->getIdentity(),
                            'email'     => '',
                            'name'      => '',
                            'avatar'    => '',
                            'sessionid' => Pi::service('session')->getId(),
                        ];
                    }
                    return $result;
                } else {
                    return $check;
                }
            } else {
                return $result;
            }
        } else {
            return $result;
        }
    }

    public function loginAction()
    {
        // Set result
        $result = [
            'status'  => 0,
            'message' => '',
        ];
        // Set template
        $this->view()->setTemplate(false)->setLayout('layout-content');
        // Get info from url
        $module = $this->params('module');
        $token  = $this->params('token');
        // Check module
        if (Pi::service('module')->isActive('user')) {
            // Check config
            $config = Pi::service('registry')->config->read($module);
            if ($config['active_user']) {
                // Check token
                $check = Pi::api('token', 'tools')->check($token, $module, 'api');
                if ($check['status'] == 1) {

                    // Load language
                    Pi::service('i18n')->load(['module/user', 'default']);

                    if (Pi::service('user')->hasIdentity()) {
                        // Get user
                        $user = Pi::user()->get(Pi::user()->getId(), [
                            'id', 'identity', 'name', 'email',
                        ]);
                        // Set result
                        $result = [
                            'check'     => 1,
                            'uid'       => $user['id'],
                            'identity'  => $user['identity'],
                            'email'     => $user['email'],
                            'name'      => $user['name'],
                            'avatar'    => Pi::service('user')->avatar($user['id'], 'large', false),
                            'sessionid' => Pi::service('session')->getId(),
                            'message'   => __('You are login to system before'),
                        ];
                    } else {

                        // Check post array set or not
                        if (!$this->request->isPost()) {
                            // Set result
                            $result = [
                                'check'     => 0,
                                'uid'       => Pi::user()->getId(),
                                'identity'  => Pi::user()->getIdentity(),
                                'email'     => '',
                                'name'      => '',
                                'avatar'    => '',
                                'sessionid' => Pi::service('session')->getId(),
                                'message'   => __('Post request not set'),
                            ];
                        } else {
                            // Get from post
                            $post       = $this->request->getPost();
                            $identity   = $post['identity'];
                            $credential = $post['credential'];
                            // Do login
                            $result = $this->doLogin($identity, $credential);
                        }
                    }

                    return $result;
                } else {
                    return $check;
                }
            } else {
                return $result;
            }
        } else {
            return $result;
        }
    }

    public function logoutAction()
    {
        // Set result
        $result = [
            'status'  => 0,
            'message' => '',
        ];
        // Set template
        $this->view()->setTemplate(false)->setLayout('layout-content');
        // Get info from url
        $module = $this->params('module');
        $token  = $this->params('token');
        // Check module
        if (Pi::service('module')->isActive('user')) {
            // Check config
            $config = Pi::service('registry')->config->read($module);
            if ($config['active_user']) {
                // Check token
                $check = Pi::api('token', 'tools')->check($token, $module, 'api');
                if ($check['status'] == 1) {

                    // Load language
                    Pi::service('i18n')->load(['module/user', 'default']);

                    // Get user id
                    $uid = Pi::user()->getId();
                    // Logout user actions
                    Pi::service('session')->manager()->destroy();
                    Pi::service('user')->destroy();
                    Pi::service('event')->trigger('logout', $uid);
                    // Set retrun array
                    $result = [
                        'message' => __('You logged out successfully.'),
                        'logout'  => 1,
                    ];

                    return $result;
                } else {
                    return $check;
                }
            } else {
                return $result;
            }
        } else {
            return $result;
        }
    }

    public function profileAction()
    {
        // Set result
        $result = [
            'status'  => 0,
            'message' => '',
        ];
        // Set template
        $this->view()->setTemplate(false)->setLayout('layout-content');
        // Get info from url
        $module = $this->params('module');
        $token  = $this->params('token');
        $order  = $this->params('order');
        // Check module
        if (Pi::service('module')->isActive('user')) {
            // Check config
            $config = Pi::service('registry')->config->read($module);
            if ($config['active_user']) {
                // Check token
                $check = Pi::api('token', 'tools')->check($token, $module, 'api');
                if ($check['status'] == 1) {

                    // Load language
                    Pi::service('i18n')->load(['module/user', 'default']);

                    // Get session id
                    $id = $this->params('id', '');
                    // Check id set or not
                    if (!empty($id)) {
                        // Start session
                        $session = Pi::model('session')->find($id);
                        if ($session) {
                            // Old method for pi 2.4.0
                            /*
                            session_id($id);
                            Pi::service('session')->manager()->start();
                            */
                            // New method for pi 2.5.0
                            $session = $session->toArray();
                            Pi::service('session')->manager()->start(false, $session['id']);
                        }
                    }
                    if (Pi::service('user')->hasIdentity()) {
                        $fields = [
                            'id', 'identity', 'name', 'email', 'first_name', 'last_name', 'id_number', 'phone', 'mobile',
                            'address1', 'address2', 'country', 'state', 'city', 'zip_code', 'company',
                        ];
                        // Find user
                        $uid               = Pi::user()->getId();
                        $user              = Pi::user()->get($uid, $fields);
                        $user['avatar']    = Pi::service('avatar')->get($user['id'], 'xxlarge', false);
                        $user['uid']       = $uid;
                        $user['check']     = 1;
                        $user['sessionid'] = Pi::service('session')->getId();

                        if (Pi::service('module')->isActive('support')) {
                            $user['support'] = Pi::api('ticket', 'support')->getCount($uid);
                        }

                        // Set order info
                        if ($order == 1 && Pi::service('module')->isActive('order')) {
                            // Load language
                            Pi::service('i18n')->load(['module/order', 'default']);

                            $credit   = Pi::api('credit', 'order')->getCredit();
                            $invoices = Pi::api('invoice', 'order')->getInvoiceFromUser($uid, true);
                            $score    = Pi::api('invoice', 'order')->getInvoiceScore($uid);


                            $user['credit_amount']      = $credit['amount'];
                            $user['credit_amount_view'] = $credit['amount_view'];
                            $user['invoice_count']      = count($invoices);
                            $user['invoice_count_view'] = _number(count($invoices));
                            $user['score_type']         = $score['type'];
                            $user['score_amount']       = $score['amount'];
                            $user['score_amount_view']  = Pi::api('api', 'order')->viewPrice($score['amount']);
                        }

                        $result = [];
                        foreach ($user as $key => $value) {
                            $result[$key] = ($value == null) ? '' : $value;
                        }
                    } else {
                        $result = [
                            'check'     => 0,
                            'uid'       => Pi::user()->getId(),
                            'identity'  => Pi::user()->getIdentity(),
                            'email'     => '',
                            'name'      => '',
                            'avatar'    => '',
                            'sessionid' => Pi::service('session')->getId(),
                        ];
                    }

                    // json output
                    return $result;

                } else {
                    return $check;
                }
            } else {
                return $result;
            }
        } else {
            return $result;
        }
    }

    public function registerAction()
    {
        // Set result
        $result = [
            'status'  => 0,
            'message' => __('Error on register'),
        ];
        // Set template
        $this->view()->setTemplate(false)->setLayout('layout-content');
        // Get info from url
        $module = $this->params('module');
        $token  = $this->params('token');
        // Check module
        if (Pi::service('module')->isActive('user')) {
            // Check config
            $config = Pi::service('registry')->config->read($module);
            if ($config['active_register']) {
                // Check token
                $check = Pi::api('token', 'tools')->check($token, $module, 'api');
                if ($check['status'] == 1) {

                    // Load language
                    Pi::service('i18n')->load(['module/user', 'default']);

                    $values  = [];
                    $request = [];
                    if (isset($_POST) && !empty($_POST)) {
                        $request = $_POST;
                    }
                    if (isset($_GET) && !empty($_GET)) {
                        $request = $_GET;
                    }
                    foreach ($request as $key => $value) {
                        $key   = _escape($key);
                        $value = _escape($value);
                        if (!empty($value)) {
                            $values[$key] = $value;
                        }
                    }

                    // Check mobile force set on register form
                    if (!isset($values['mobile']) || empty($values['mobile']) || !is_numeric($values['mobile'])) {
                        return $result;
                    }
                    // Check email force set on register form
                    if (!isset($values['email']) || empty($values['email'])) {
                        // $values['email'] = '';
                        return $result;
                    }
                    // Set email as identity if not set on register form
                    if (!isset($values['identity']) || empty($values['identity'])) {
                        $values['identity'] = $values['mobile'];
                    }
                    // Set name if not set on register form
                    if (!isset($values['name']) || empty($values['name'])) {
                        if (isset($values['first_name']) || isset($values['last_name'])) {
                            $values['name'] = $values['first_name'] . ' ' . $values['last_name'];
                        } else {
                            $values['name'] = $values['identity'];
                        }
                    }
                    // Set values
                    $values['last_modified'] = time();
                    $values['ip_register']   = Pi::user()->getIp();

                    // Check mobile is duplicated
                    $where = ['identity' => $values['identity']];
                    $count = Pi::model('user_account')->count($where);
                    if ($count) {
                        $result = [
                            'status'  => 0,
                            'message' => __('This mobile number is taken before by another user'),
                        ];
                        return $result;
                    }

                    // Add user
                    $uid = Pi::api('user', 'user')->addUser($values);
                    if (!$uid || !is_int($uid)) {
                        $result = [
                            'status'  => 0,
                            'message' => __('User account was not saved.'),
                        ];
                    } else {
                        // Set user role
                        Pi::api('user', 'user')->setRole($uid, 'member');

                        // Active user
                        $status = Pi::api('user', 'user')->activateUser($uid);
                        if ($status) {
                            // Target activate user event
                            Pi::service('event')->trigger('user_activate', $uid);

                            // Set result
                            $result = [
                                'status'  => 1,
                                'message' => __('Your account create and activate. please login to system'),
                            ];
                            return $result;
                        }
                    }

                    return $result;
                } else {
                    return $check;
                }
            } else {
                return $result;
            }
        } else {
            return $result;
        }
    }

    public function editAction()
    {
        // Set result
        $result = [
            'status'  => 0,
            'message' => __('Error on register'),
        ];
        // Set template
        $this->view()->setTemplate(false)->setLayout('layout-content');
        // Get info from url
        $module = $this->params('module');
        $token  = $this->params('token');
        // Check module
        if (Pi::service('module')->isActive('user')) {
            // Check config
            $config = Pi::service('registry')->config->read($module);
            if ($config['active_register']) {
                // Check token
                $check = Pi::api('token', 'tools')->check($token, $module, 'api');
                if ($check['status'] == 1) {


                    // Load language
                    Pi::service('i18n')->load(['module/user', 'default']);

                    $values  = [];
                    $request = [];
                    if (isset($_POST) && !empty($_POST)) {
                        $request = $_POST;
                    }
                    if (isset($_GET) && !empty($_GET)) {
                        $request = $_GET;
                    }
                    foreach ($request as $key => $value) {
                        $key          = _escape($key);
                        $value        = _strip($value);
                        $values[$key] = $value;
                    }

                    // Set uid
                    if (!empty($values['uid'])) {
                        $uid = $values['uid'];
                        unset($values['uid']);
                    } else {
                        return $result;
                    }

                    // Fields
                    $fields = Pi::api('user', 'user')->getFields($uid, 'profile');
                    if (isset($values['credential']) && !isset($fields['credential'])) {
                        $fields['credential'] = '';
                    }


                    // Set just needed fields
                    foreach (array_keys($values) as $key) {
                        if (!in_array($key, array_keys($fields))) {
                            unset($values[$key]);
                        }
                    }
                    // From user module
                    $values['last_modified'] = time();
                    // Set first and last name as name
                    if (isset($values['first_name']) || isset($values['last_name'])) {
                        $values['name'] = $values['first_name'] . ' ' . $values['last_name'];
                    }
                    // Set mobile as identity
                    if (isset($values['mobile']) || !empty($values['mobile'])) {
                        $values['identity'] = $values['mobile'];
                    }


                    // Check mobile is duplicated
                    $where = [
                        'identity' => $values['identity'],
                        'id <> ?'  => $uid,
                    ];
                    $count = Pi::model('user_account')->count($where);
                    if ($count) {
                        $result = [
                            'status'  => 0,
                            'message' => __('This mobile number is taken before by another user'),
                        ];
                        return $result;
                    }

                    $status = Pi::api('user', 'user')->updateUser($uid, $values);
                    if ($status == 1) {
                        Pi::service('event')->trigger('user_update', $uid);
                    }

                    $result = [
                        'status'  => 1,
                        'message' => __('Your account information update'),
                    ];


                    return $result;
                } else {
                    return $check;
                }
            } else {
                return $result;
            }
        } else {
            return $result;
        }
    }

    public function doLogin($identity, $credential)
    {
        // Set return array
        $return = [
            'login'        => 0,
            'error'        => 0,
            'check'        => 0,
            'userid'       => 0,
            'uid'          => 0,
            'message'      => '',
            'sessionid'    => '',
            'identity'     => '',
            'email'        => '',
            'name'         => '',
            'first_name'   => '',
            'last_name'    => '',
            'id_number'    => '',
            'phone'        => '',
            'mobile'       => '',
            'address1'     => '',
            'address2'     => '',
            'country'      => '',
            'state'        => '',
            'city'         => '',
            'zip_code'     => '',
            'company'      => '',
            'company_id'   => '',
            'company_vat'  => '',
            'your_gift'    => '',
            'your_post'    => '',
            'company_type' => '',
            'latitude'     => '',
            'longitude'    => '',
            'avatar'       => '',
            'support'      => '',
        ];

        // Set field
        $config = Pi::service('registry')->config->read('api');

        // try login
        $result = Pi::service('authentication')->authenticate(
            $identity,
            $credential,
            $config['login_field']
        );
        $result = $this->verifyResult($result);

        // Check login is valid
        if ($result->isValid()) {
            $uid = (int)$result->getData('id');
            // Bind user information
            if (Pi::service('user')->bind($uid)) {
                Pi::service('session')->setUser($uid);
                $rememberMe = 14 * 86400;
                Pi::service('session')->manager()->rememberme($rememberMe);
                // Unset login session
                if (isset($_SESSION['PI_LOGIN'])) {
                    unset($_SESSION['PI_LOGIN']);
                }
                // Set user login event
                $args = [
                    'uid'           => $uid,
                    'remember_time' => $rememberMe,
                ];
                Pi::service('event')->trigger('user_login', $args);
                // Get user information
                $fields = [
                    'id', 'identity', 'name', 'email', 'first_name', 'last_name', 'id_number', 'phone', 'mobile',
                    'address1', 'address2', 'country', 'state', 'city', 'zip_code', 'company', 'company_id', 'company_vat',
                    'your_gift', 'your_post', 'company_type', 'latitude', 'longitude',
                ];
                // Find user
                $user = Pi::user()->get($uid, $fields);
                // Set return array
                $return['message']      = __('You have logged in successfully');
                $return['login']        = 1;
                $return['sessionid']    = Pi::service('session')->getId();
                $return['check']        = 1;
                $return['userid']       = $user['id'];
                $return['uid']          = $user['id'];
                $return['identity']     = $user['identity'];
                $return['email']        = $user['email'];
                $return['name']         = $user['name'];
                $return['first_name']   = isset($user['first_name']) ? $user['first_name'] : '';
                $return['last_name']    = isset($user['last_name']) ? $user['last_name'] : '';
                $return['id_number']    = isset($user['id_number']) ? $user['id_number'] : '';
                $return['phone']        = isset($user['phone']) ? $user['phone'] : '';
                $return['mobile']       = isset($user['mobile']) ? $user['mobile'] : '';
                $return['address1']     = isset($user['address1']) ? $user['address1'] : '';
                $return['address2']     = isset($user['address2']) ? $user['address2'] : '';
                $return['country']      = isset($user['country']) ? $user['country'] : '';
                $return['state']        = isset($user['state']) ? $user['state'] : '';
                $return['city']         = isset($user['city']) ? $user['city'] : '';
                $return['zip_code']     = isset($user['zip_code']) ? $user['zip_code'] : '';
                $return['company']      = isset($user['company']) ? $user['company'] : '';
                $return['company_id']   = isset($user['company_id']) ? $user['company_id'] : '';
                $return['company_vat']  = isset($user['company_vat']) ? $user['company_vat'] : '';
                $return['your_gift']    = isset($user['your_gift']) ? $user['your_gift'] : '';
                $return['your_post']    = isset($user['your_post']) ? $user['your_post'] : '';
                $return['company_type'] = isset($user['company_type']) ? $user['company_type'] : '';
                $return['latitude']     = isset($user['latitude']) ? $user['latitude'] : '';
                $return['longitude']    = isset($user['longitude']) ? $user['longitude'] : '';
                $return['avatar']       = Pi::service('user')->avatar($user['id'], 'medium', false);
                if (Pi::service('module')->isActive('support')) {
                    $user['support'] = Pi::api('ticket', 'support')->getCount($uid);
                }
            } else {
                $return['error']   = 1;
                $return['message'] = __('Bind error');
            }
        } else {
            $return['error']   = 1;
            $return['message'] = __('Authentication is not valid');
        }

        return $return;
    }

    protected function verifyResult(Result $result)
    {
        return $result;
    }
}