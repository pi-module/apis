<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link            http://code.pialog.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://pialog.org
 * @license         http://pialog.org/license.txt BSD 3-Clause License
 */

namespace Module\Apis\Controller\Front;

use Pi;
use Pi\Mvc\Controller\ActionController;

/**
 * @author Hossein Azizabadi <azizabadi@faragostaresh.com>
 */
class PageController extends ActionController
{
    public function viewAction()
    {
        // Set result
        $result = array(
            'status' => 0,
            'message' => '',
        );
        // Set template
        $this->view()->setTemplate(false)->setLayout('layout-content');
        // Get info from url
        $module = $this->params('module');
        $token = $this->params('token');
        // Check module
        if (Pi::service('module')->isActive('page')) {
            // Check config
            $config = Pi::service('registry')->config->read($module);
            if ($config['active_page']) {
                // Check token
                $check = Pi::api('token', 'tools')->check($token, $module, 'api');
                if ($check['status'] == 1) {




                    $id = $this->params('id');
                    $row = Pi::model('page', 'page')->find($id);
                    $content = Pi::service('markup')->compile(
                        $row->content,
                        'html'
                    );
                    $content = strip_tags($content,"<b><strong><i><p><ul><li><ol><h2><h3><h4>");
                    $content = str_replace("</p>\r\n\r\n<p>", "<br />", $content);

                    $result['title'] = $row->title;
                    $result['content'] = $content;




                    $result['status'] = 1;
                    $result['message'] = 'Its work !';
                    return array($result);
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
}