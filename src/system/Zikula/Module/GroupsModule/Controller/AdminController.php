<?php
/**
 * Copyright Zikula Foundation 2009 - Zikula Application Framework
 *
 * This work is contributed to the Zikula Foundation under one or more
 * Contributor Agreements and licensed to You under the following license:
 *
 * @license GNU/LGPLv3 (or at your option, any later version).
 *
 * Please see the NOTICE file distributed with this source code for further
 * information regarding copyright and licensing.
 */

namespace Zikula\Module\GroupsModule\Controller;

use Zikula_View;
use ModUtil;
use SecurityUtil;
use Zikula\Module\GroupsModule\Helper\CommonHelper;
use UserUtil;
use Users_Constant;
use System;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Administrative controllers for the groups module
 */
class AdminController extends \Zikula_AbstractController
{
    /**
     * Post initialise.
     *
     * @return void
     */
    protected function postInitialize()
    {
        // In this controller we do not want caching.
        $this->view->setCaching(Zikula_View::CACHE_DISABLED);
    }

    /**
     * Main administration function
     *
     * @throws AccessDeniedException if the user doesn't have edit permission to any groups
     *
     * @return RedirectResponse
     */
    public function mainAction()
    {
        // Security check
        $any_access = false;

        // get all groups from the API
        $groups = ModUtil::apiFunc('ZikulaGroupsModule', 'user', 'getall');
        if (is_array($groups)) {
            foreach ($groups as $group) {
                if (SecurityUtil::checkPermission('ZikulaGroupsModule::', $group['gid'] . '::', ACCESS_EDIT)) {
                    $any_access = true;
                    break;
                }
            }
        }

        if (!$any_access) {
            // we found no groups that we are allowed to administer
            // return now
            throw new AccessDeniedException();
        }

        // Return the output that has been generated by this function
        return new RedirectResponse(System::normalizeUrl(ModUtil::url($this->name, 'admin', 'view')));
    }

    /**
     * View all groups
     *
     * This function creates a tabular output of all group items in the module.
     *
     * @return Response symfony response object
     *
     * @throws AccessDeniedException Thrown if the user hasn't permissions to administer any groups
     */
    public function viewAction()
    {
        // Get parameters from whatever input we need.
        $startnum = (int)$this->request->request->get('startnum', null);

        // we need this value multiple times, so we keep it
        $itemsperpage = $this->getVar('itemsperpage');

        // get the default user group
        $defaultgroup = $this->getVar('defaultgroup');

        // get the primary admin group
        $primaryadmingroup = $this->getVar('primaryadmingroup', 2);

        // The user API function is called.
        $items = ModUtil::apiFunc('ZikulaGroupsModule', 'user', 'getall',
                array('startnum' => $startnum,
                      'numitems' => $itemsperpage));

        // Setting various defines
        $groupsCommon = new CommonHelper();
        $typelabel = $groupsCommon->gtypeLabels();
        $statelabel = $groupsCommon->stateLabels();

        $groups = array();

        foreach ($items as $item) {

            if (SecurityUtil::checkPermission('ZikulaGroupsModule::', $item['gid'].'::', ACCESS_READ)) {

                // Options for the item.
                $options = array();
                if (SecurityUtil::checkPermission('ZikulaGroupsModule::', $item['gid'].'::', ACCESS_EDIT)) {

                    $editurl    = ModUtil::url('ZikulaGroupsModule', 'admin', 'modify', array('gid'     => $item['gid']));
                    $membersurl = ModUtil::url('ZikulaGroupsModule', 'admin', 'groupmembership', array('gid'     => $item['gid']));

                    $options[] = array('url' => ModUtil::url('ZikulaGroupsModule', 'admin', 'modify', array('gid'     => $item['gid'])),
                            'title'   => $this->__('Edit'),
                            'imgfile' => 'xedit.png');

                    if ((SecurityUtil::checkPermission('ZikulaGroupsModule::', $item['gid'].'::', ACCESS_DELETE))
                            && ($item['gid'] != $defaultgroup) && ($item['gid'] != $primaryadmingroup)) {
                        $options[] = array('url' => ModUtil::url('ZikulaGroupsModule', 'admin', 'delete', array('gid'     => $item['gid'])),
                                'title'   => $this->__('Delete'),
                                'imgfile' => '14_layer_deletelayer.png');
                    }

                    $options[] = array('url' => ModUtil::url('ZikulaGroupsModule', 'admin', 'groupmembership', array('gid'     => $item['gid'])),
                            'title'   => $this->__('Group membership'),
                            'imgfile' => 'agt_family.png');

                    $nbuser = ModUtil::apiFunc('ZikulaGroupsModule', 'user', 'countgroupmembers', array('gid' => $item['gid']));

                    $groups[] = array(
                        'name' => $item['name'],
                        'gid'         => $item['gid'],
                        'gtype'       => $item['gtype'],
                        'gtypelbl'    => $typelabel[$item['gtype']],
                        'description' => ((empty($item['description'])== false) ? $item['description'] : ''),
                        'prefix'      => $item['prefix'],
                        'state'       => $item['state'],
                        'statelbl'    => $statelabel[$item['state']],
                        'nbuser'      => (($nbuser <> false) ? $nbuser : 0),
                        'nbumax'      => $item['nbumax'],
                        'link'        => $item['link'],
                        'uidmaster'   => $item['uidmaster'],
                        'options'     => $options,
                        'editurl'     => $editurl,
                        'membersurl'  => $membersurl);
                }
            }
        }

        if (count($groups) == 0) {
            // groups array is empty
            throw new AccessDeniedException();
        }

        // The admin API function is called. This fetch the pending applications if any.
        // permission check for the group is done in this function
        $users = ModUtil::apiFunc('ZikulaGroupsModule', 'admin', 'getapplications',
                array('startnum' => $startnum,
                      'numitems' => $itemsperpage));

        $this->view->assign('groups',       $groups)
                   ->assign('grouptypes',   $typelabel)
                   ->assign('states',       $statelabel)
                   ->assign('useritems',    $users)
                   ->assign('defaultgroup', $defaultgroup)
                   ->assign('primaryadmingroup', $primaryadmingroup);

        // Assign the values for the smarty plugin to produce a pager
        $this->view->assign('pager', array('numitems'     => ModUtil::apiFunc('ZikulaGroupsModule', 'admin', 'countitems'),
                                           'itemsperpage' => $itemsperpage));

        // Return the output that has been generated by this function
        return $this->response($this->view->fetch('Admin/view.tpl'));
    }

    /**
     * Display a form to add a new group.
     *
     * @return Response symfony response object.
     *
     * @throws AccessDeniedException Thrown if the user doesn't have add access to the module
     */
    public function newgroupAction()
    {
        // Security check
        if (!SecurityUtil::checkPermission('ZikulaGroupsModule::', '::', ACCESS_ADD)) {
            throw new AccessDeniedException();
        }

        // Setting various defines
        $groupsCommon = new CommonHelper();
        $grouptype = $groupsCommon->gtypeLabels();
        $groupstate = $groupsCommon->stateLabels();

        $this->view->assign('grouptype',  $grouptype)
                   ->assign('groupstate', $groupstate);

        // Return the output that has been generated by this function
        return $this->response($this->view->fetch('Admin/new.tpl'));
    }

    /**
     * Create a new group
     *
     * This function takes input from newgroupAction().
     *
     *       string $name        the name of the group to be created
     *       int    $gtype       the group type
     *       bool   $state       the group state
     *       int    $nbumax      the maximum of users
     *       string $description the group description
     *                      }
     *
     * @return RedirectResponse
     */
    public function createAction()
    {
        $this->checkCsrfToken();

        // Get parameters from whatever input we need.
        $name = $this->request->request->get('name', null);
        $gtype = $this->request->request->get('gtype', null);
        $state = $this->request->request->get('state', null);
        $nbumax = $this->request->request->get('nbumax', null);
        $description = $this->request->request->get('description', null);

        // The API function is called.
        $check = ModUtil::apiFunc('ZikulaGroupsModule', 'admin', 'getgidbyname', array('name' => $name));

        if ($check != false) {
            // Group already exists
            $this->request->getSession()->getFlashBag()->add('error', $this->__('Error! There is already a group with that name.'));
            return new RedirectResponse(System::normalizeUrl(ModUtil::url($this->name, 'admin', 'view')));
        } else {
            $gid = ModUtil::apiFunc('ZikulaGroupsModule', 'admin', 'create',
                    array('name'        => $name,
                          'gtype'       => $gtype,
                          'state'       => $state,
                          'nbumax'      => $nbumax,
                          'description' => $description));

            // The return value of the function is checked here
            if ($gid != false) {
                // Success
                $this->request->getSession()->getFlashBag()->add('status', $this->__('Done! Created the group.'));
            }
        }

        // This function generated no output
        return new RedirectResponse(System::normalizeUrl(ModUtil::url($this->name, 'admin', 'view')));
    }

    /**
     * Modify a group.
     *
     *       int $gid      the id of the group to be modified
     *
     * @return Response symfony response object.
     *
     * @throws NotFoundHttpException Thrown if the requested group isn't found
     * @throws AccessDeniedException Thrown if the user doesn't have edit access to the group
     */
    public function modifyAction()
    {
        // Get parameters from whatever input we need.
        $gid = (int)$this->request->query->get('gid', null);

        // get group
        $item = ModUtil::apiFunc('ZikulaGroupsModule', 'user', 'get', array('gid' => $gid));

        if (!$item) {
            throw new NotFoundHttpException($this->__('Sorry! No such group found.'));
        }

        // Security check
        if (!SecurityUtil::checkPermission('ZikulaGroupsModule::', $item['gid'].'::', ACCESS_EDIT)) {
            throw new AccessDeniedException();
        }

        // assign the item
        $this->view->assign('item', $item);

        // Setting various defines
        $groupsCommon = new CommonHelper();
        $grouptype = $groupsCommon->gtypeLabels();
        $groupstate = $groupsCommon->stateLabels();

        $this->view->assign('grouptype',  $grouptype)
                   ->assign('groupstate', $groupstate);

        // Return the output that has been generated by this function
        return $this->response($this->view->fetch('Admin/modify.tpl'));
    }

    /**
     * Update a group
     *
     *       int    $gid      the id of the group to be modified.
     *       int    $objectid generic object id mapped onto gid if present.
     *       string $name     the name of the group to be updated.
     *                    }
     *
     * @return RedirectResponse
     */
    public function updateAction()
    {
        $this->checkCsrfToken();

        // Get parameters from whatever input we need.
        $gid = (int)$this->request->request->get('gid', null);
        $name = $this->request->request->get('name', null);
        $gtype = $this->request->request->get('gtype', null);
        $state = $this->request->request->get('state', null);
        $nbumax = $this->request->request->get('nbumax', null);
        $description = $this->request->request->get('description', null);

        // The API function is called.
        if (ModUtil::apiFunc('ZikulaGroupsModule', 'admin', 'update',
                array('gid' => $gid,
                      'name'        => $name,
                      'gtype'       => $gtype,
                      'state'       => $state,
                      'nbumax'      => $nbumax,
                      'description' => $description))) {
            // Success
            $this->request->getSession()->getFlashBag()->add('status', $this->__('Done! Saved group changes.'));
        }

        // This function generated no output
        return new RedirectResponse(System::normalizeUrl(ModUtil::url($this->name, 'admin', 'view')));
    }

    /**
     * Delete group.
     *
     *       int  $gid          the id of the item to be deleted
     *       bool $confirmation confirmation that this item can be deleted
     *                      }
     *
     * @return Response|void response object if no confirmation, void otherwise
     *
     * @throws NotFoundHttpException Thrown if the requested group is not found
     * @throws AccessDeniedException Thrown if the user doesn't have delete access to the group
     */
    public function deleteAction()
    {
        // Get parameters from whatever input we need.
        $gid = (int)$this->request->query->get('gid', null);
        $confirmation = (bool)$this->request->request->get('confirmation', null);

        // The user API function is called.
        $item = ModUtil::apiFunc('ZikulaGroupsModule', 'user', 'get', array('gid' => $gid));

        if (!$item) {
            throw new NotFoundHttpException($this->__('Sorry! No such group found.'));
        }

        // Security check
        if (!SecurityUtil::checkPermission('ZikulaGroupsModule::', $item['gid'].'::', ACCESS_DELETE)) {
            throw new AccessDeniedException();
        }

        // get the user default group - we do not allow its deletion
        $defaultgroup = $this->getVar('defaultgroup');
        if ($item['gid'] == $defaultgroup) {
            $this->request->getSession()->getFlashBag()->add('error', $this->__('Error! You cannot delete the default user group.'));
            return new RedirectResponse(System::normalizeUrl(ModUtil::url($this->name, 'admin', 'view')));
        }

        // Check for confirmation.
        if (empty($confirmation)) {

            // No confirmation yet - display a suitable form to obtain confirmation
            // of this action from the user

            // Add a hidden variable for the item id.
            $this->view->assign('item', $item);

            // Return the output that has been generated by this function
            return $this->response($this->view->fetch('Admin/delete.tpl'));
        }

        // If we get here it means that the user has confirmed the action

        $this->checkCsrfToken();

        // The API function is called.
        if (ModUtil::apiFunc('ZikulaGroupsModule', 'admin', 'delete', array('gid' => $gid))) {
            // Success
            $this->request->getSession()->getFlashBag()->add('status', $this->__('Done! Deleted the group.'));
        }

        // This function generated no output
        return new RedirectResponse(System::normalizeUrl(ModUtil::url($this->name, 'admin', 'view')));
    }

    /**
     * Display all members of a group.
     *
     *       int    $gid      the id of the group to list membership for
     *       int    $startnum the start item number for the pager
     *       string $letter   the letter from the alpha filter
     *                      }
     *
     * @return Response symfony response object
     *
     * @throws AccessDeniedException Thrown if the user doesn't have edit access to the group
     */
    public function groupmembershipAction()
    {
        // Get parameters from whatever input we need.
        $gid = (int)$this->request->query->get('gid', null);
        $startnum = (int)$this->request->query->get('startnum', null);
        $letter = $this->request->query->get('letter', null);

        // The user API function is called.
        $group = ModUtil::apiFunc('ZikulaGroupsModule', 'user', 'get',
                array('gid'      => $gid,
                      'startnum' => $startnum,
                      'numitems' => $this->getVar('itemsperpage')));

        // Security check
        if (!SecurityUtil::checkPermission('ZikulaGroupsModule::', $group['gid'].'::', ACCESS_EDIT)) {
            throw new AccessDeniedException();
        }

        // assign the group to the template
        $this->view->assign('group', $group);

        $users = $group['members'];

        $currentUid = UserUtil::getVar('uid');
        $defaultGroup = $this->getVar('defaultgroup', 0);
        $primaryAdminGroup = $this->getVar('primaryadmingroup', 0);

        $groupmembers = array();

        if (is_array($users) && SecurityUtil::checkPermission('ZikulaGroupsModule::', $group['gid'].'::', ACCESS_EDIT)) {
            foreach ($users as $user) {
                $options = array();

                if (($user['uid'] == $currentUid)
                    && (($group['gid'] == $defaultGroup) || ($group['gid'] == $primaryAdminGroup))) {
                    $options[] = array();
                } else {
                    $options[] = array(
                        'url'     => ModUtil::url('ZikulaGroupsModule', 'admin', 'removeuser', array('gid' => $group['gid'], 'uid' => $user['uid'])),
                        'imgfile' => 'editdelete.png',
                        'uid'     => $user['uid'],
                        'title'   => $this->__('Remove user from group')
                    );
                }

                $groupmembers[] = array(
                    'uname'   => UserUtil::getVar('uname', $user['uid'], null, UserUtil::isRegistration($user['uid'])),
                    'name'    => UserUtil::getVar('name', $user['uid'], null, UserUtil::isRegistration($user['uid'])),
                    'email'    => UserUtil::getVar('email', $user['uid'], null, UserUtil::isRegistration($user['uid'])),
                    'uid'     => $user['uid'],
                    'options' => $options
                );
            }
        }

        // sort alphabetically.
        $sortAarr = array();
        foreach ($groupmembers as $res) {
            $sortAarr[] = strtolower($res['uname']);
        }
        array_multisort($sortAarr, SORT_ASC, $groupmembers);

        $this->view->assign('groupmembers', $groupmembers);

        // check for a letter parameter
        if (empty($letter) && strlen($letter) != 1) {
            $letter = '*';
        }

        switch ($letter) {
            case '*':
                // read allusers
                $field = '';
                $expression = '';
                break;

            default:
                $field = 'uname';
                $expression = $letter . '%';
        }

        $users = UserUtil::getAll('uname', 'ASC', null, null, '', $field, $expression);

        $allusers = array();
        foreach ($users as $user) {
            if ($user['uid'] == 0 || strtolower($user['uname']) == 'anonymous' || strtolower($user['uname']) == 'guest'
                    || $user['uname'] == $this->getVar(Users_Constant::MODVAR_ANONYMOUS_DISPLAY_NAME)
                    ) {
                continue;
            }
            $alias = '';
            if (!empty($user['name'])) {
                $alias = ' (' . $user['name'] . ')';
            }
            $allusers[$user['uid']] = $user['uname'] . $alias;
        }

        // Now lets remove the users that are currently part of the group
        // flip the array so we have the user id's as the key
        // this makes the array the same is the group members array
        // from the get function
        $flippedusers = array_flip($allusers);
        // now lets diff the array
        $diffedusers = array_diff($flippedusers, array_keys($group['members']));
        // now flip the array back
        $allusers = array_flip($diffedusers);
        // sort the users by user name
        natcasesort($allusers);

        // assign the users not in the group to the template
        $this->view->assign('uids', $allusers);

        // Assign the values for the smarty plugin to produce a pager
        $this->view->assign('pager', array('numitems'     => ModUtil::apiFunc('ZikulaGroupsModule', 'user', 'countgroupmembers', array('gid' => $gid)),
                                           'itemsperpage' => $this->getVar( 'itemsperpage')));

        // Return the output that has been generated by this function
        return $this->response($this->view->fetch('Admin/groupmembership.tpl'));
    }

    /**
     * Add user(s) to a group.
     *
     *       int   $gid The id of the group
     *       mixed $uid The id of the user (int) or an array of userids
     *                    }
     *
     * @return RedirectResponse
     */
    public function adduserAction()
    {
        $this->checkCsrfToken();

        // Get parameters from whatever input we need.
        $gid = (int)$this->request->request->get('gid', null);
        $uid = $this->request->request->get('uid', null);

        // The API function is called.
        if (is_array($uid)) {
            $total_users_added = 0;
            $total_users_notadded = 0;

            foreach($uid as $id) {
                if (!ModUtil::apiFunc('ZikulaGroupsModule', 'admin', 'adduser', array('gid' => $gid, 'uid' => $id))) {
                    $total_users_notadded++;
                } else {
                    $total_users_added++;
                }
            }

            if ($total_users_added > 0) {
                $this->request->getSession()->getFlashBag()->add('status', $this->_fn('Done! %s user was added to the group.', 'Done! %s users were added to the group.', $total_users_added, $total_users_added));
            }
            if ($total_users_notadded > 0) {
                $this->request->getSession()->getFlashBag()->add('error', $this->_fn('Error! %s user was not added to the group.', 'Error! %s users were not added to the group.', $total_users_notadded, $total_users_notadded));
                return new RedirectResponse(System::normalizeUrl(ModUtil::url($this->name, 'admin', 'groupmembership', array('gid' => $gid))));
            }
        } else {
            if (!ModUtil::apiFunc('ZikulaGroupsModule', 'admin', 'adduser', array('gid' => $gid, 'uid' => $uid))) {
                $this->request->getSession()->getFlashBag()->add('error', $this->__('Error! A problem occurred and the user was not added to the group.'));
                return new RedirectResponse(System::normalizeUrl(ModUtil::url($this->name, 'admin', 'groupmembership', array('gid' => $gid))));
            } else {

                $this->request->getSession()->getFlashBag()->add('status', $this->__('Done! The user was added to the group.'));
            }
        }

        // This function generated no output
        return new RedirectResponse(System::normalizeUrl(ModUtil::url($this->name, 'admin', 'groupmembership', array('gid' => $gid))));
    }

    /**
     * Remove a user from a group.
     *
     *       int $gid The id of the group
     *       int $uid The id of the user
     *                  }
     *
     * @return mixed Response|void symfony repsonse object if confirmation isn't provided, void otherwise
     *
     * @throws AccessDeniedException Thrown if the user doesn't have edit access to the group
     */
    public function removeuserAction()
    {
        // Get parameters from whatever input we need.
        $gid = (int)$this->request->query->get('gid', null);
        $uid = (int)$this->request->query->get('uid', null);
        $confirmation = (bool)$this->request->request->get('confirmation', null);
        if (!SecurityUtil::checkPermission('ZikulaGroupsModule::', $gid.'::', ACCESS_EDIT)) {
            throw new AccessDeniedException();
        }

        // Check for confirmation.
        if (empty($confirmation)) {
            // No confirmation yet - display a suitable form to obtain confirmation
            // of this action from the user

            $group = ModUtil::apiFunc('ZikulaGroupsModule', 'user', 'get', array('gid' => $gid));

            // Add a hidden variable for the item id.
            $this->view->assign('gid', $gid)
                       ->assign('uid', $uid)
                       ->assign('group', $group)
                       ->assign('uname', UserUtil::getVar('uname', $uid));

            // Return the output that has been generated by this function
            return $this->response($this->view->fetch('Admin/removeuser.tpl'));
        }

        $this->checkCsrfToken();

        // The API function is called.
        if (ModUtil::apiFunc('ZikulaGroupsModule', 'admin', 'removeuser', array('gid' => $gid, 'uid' => $uid))) {
            // Success
            $this->request->getSession()->getFlashBag()->add('status', $this->__('Done! The user was removed from the group.'));
        } else {
            $this->request->getSession()->getFlashBag()->add('error', $this->__('Error! A problem occurred while attempting to remove the user. The user has not been removed from the group.'));
            return new RedirectResponse(System::normalizeUrl(ModUtil::url($this->name, 'admin', 'groupmembership', array('gid' => $gid))));
        }

        // This function generated no output
        return new RedirectResponse(System::normalizeUrl(ModUtil::url($this->name, 'admin', 'groupmembership', array('gid' => $gid))));
    }

    /**
     * display a list of group applications
     *
     * @return Response symfony response object
     *
     * @throws \InvalidArgumentException Thrown if either the gid or userid parameters are not provided or
     *                                          if the action parameter isn't one of 'deny' or 'accept'
     */
    public function userpendingAction()
    {
        $gid = (int)$this->request->query->get('gid', null);
        $userid = (int)$this->request->query->get('userid', null);
        $action = $this->request->query->get('action', null);

        if (empty($gid) || empty($userid)) {
            throw new \InvalidArgumentException(__('Invalid arguments received'));
        }

        if ($action != 'deny' && $action != 'accept') {
            throw new \InvalidArgumentException(__('Invalid arguments received'));
        }

        $appinfo = ModUtil::apiFunc('ZikulaGroupsModule', 'admin', 'getapplicationinfo', array('gid' => $gid, 'userid' => $userid));

        $sendoptions = array(
            0 => $this->__('None'),
            1 => $this->__('E-mail')
        );

        $this->view->assign('userid',      $userid)
                   ->assign('gid',         $gid)
                   ->assign('action',      $action)
                   ->assign('sendoptions', $sendoptions)
                   ->assign('application', $appinfo['application']);

        return $this->response($this->view->fetch('Admin/userpending.tpl'));
    }

    /**
     * update group applications
     *
     * @return RedirectResponse
     *
     * @throws \InvalidArgumentException Thrown if any of the tag, gid or userid parameters aren't provided or
     *                                          if the requested action isn't one of 'deny' or 'accept'
     * @throws \RuntimeException Thrown if the requested action couldn't be carried out
     */
    public function userupdateAction()
    {
        $this->checkCsrfToken();

        $action = $this->request->request->get('action', null);

        if ($action != 'deny' && $action != 'accept') {
            throw new \InvalidArgumentException(__('Invalid arguments array received'));
        }

        $tag = $this->request->request->get('tag', null);
        $sendtag = $this->request->request->get('sendtag', null);
        $reason = $this->request->request->get('reason', null);
        $gid = (int)$this->request->request->get('gid', null);
        $userid = (int)$this->request->request->get('userid', null);

        if (empty($tag) || empty($gid) || empty($userid)) {
            throw new \InvalidArgumentException(__('Invalid arguments array received'));
        }

        $group = ModUtil::apiFunc('ZikulaGroupsModule', 'user', 'get', array('gid' => $gid));

        if ($action == 'deny') {

            $reasontitle = $this->__f('Concerning your %s group membership application', $group['name']);

            if (empty($reason)) {
                // Get Default TEXT
                $reason = $this->__('Sorry! This is a message to inform you with regret that your application for membership of the aforementioned private group has been rejected.');
            }

        } elseif ($action == 'accept') {

            $reasontitle = $this->__f('Done! The user has been added to the %s group.', $group['name']);

            if (empty($reason)) {
                // Get Default TEXT
                $reason = $this->__('Done! Your application has been accepted. You have been granted all the privileges assigned to the group of which you are now member.');
            }

        }

        $result = ModUtil::apiFunc('ZikulaGroupsModule', 'admin', 'pendingaction',
                array('userid'      => $userid,
                      'gid'         => $gid,
                      'sendtag'     => $sendtag,
                      'reason'      => $reason,
                      'reasontitle' => $reasontitle,
                      'action'      => $action));

        if (!$result) {
            if ($action == 'deny') {
                $this->request->getSession()->getFlashBag()->add('error', $this->__("Error! Could not execute 'Reject' action."));
            } else {
                $this->request->getSession()->getFlashBag()->add('error', $this->__("Error! Could not execute 'Accept' action."));
            }
            return new RedirectResponse(System::normalizeUrl(ModUtil::url($this->name, 'admin', 'view')));
        }

        if ($action == 'accept') {
            $this->request->getSession()->getFlashBag()->add('status', $this->__('Done! The user was added to the group.'));
        } else {
            $this->request->getSession()->getFlashBag()->add('status', $this->__("Done! The user's application for group membership has been rejected."));
        }

        return new RedirectResponse(System::normalizeUrl(ModUtil::url($this->name, 'admin', 'view')));
    }

    /**
     * Display a form to modify configuration parameters of the module.
     *
     * @return Response symfony response object
     *
     * @throws AccessDeniedException Thrown if the user doesn't have admin access to the module
     */
    public function modifyconfigAction()
    {
        // Security check
        if (!SecurityUtil::checkPermission('ZikulaGroupsModule::', '::', ACCESS_ADMIN)) {
            throw new AccessDeniedException();
        }

        // get all groups from the API
        $groups = ModUtil::apiFunc('ZikulaGroupsModule', 'user', 'getall');

        // build an array suitable for html_options
        $groupslist = array();
        foreach ($groups as $group) {
            $groupslist[$group['gid']] = $group['name'];
        }

        // assign the list of existing groups
        $this->view->assign('groups', $groupslist);

        // Return the output that has been generated by this function
        return $this->response($this->view->fetch('Admin/modifyconfig.tpl'));
    }

    /**
     * Update the module configuration
     *
     * @return RedirectResponse
     */
    public function updateconfigAction()
    {
        $this->checkCsrfToken();

        // Security check
        if (!SecurityUtil::checkPermission('ZikulaGroupsModule::', '::', ACCESS_ADMIN)) {
            throw new AccessDeniedException();
        }

        // Update module variables.
        $itemsperpage = (int)$this->request->request->get('itemsperpage', 25);
        $this->setVar('itemsperpage', $itemsperpage);

        $defaultgroup = (int)$this->request->request->get('defaultgroup', 1);
        $this->setVar('defaultgroup', $defaultgroup);

        $mailwarning = (bool)$this->request->request->get('mailwarning', false);
        $this->setVar('mailwarning', $mailwarning);

        $hideclosed = (bool)$this->request->request->get('hideclosed', false);
        $this->setVar('hideclosed', $hideclosed);

        // the module configuration has been updated successfuly
        $this->request->getSession()->getFlashBag()->add('status', $this->__('Done! Saved module configuration.'));

        // This function generated no output
        return new RedirectResponse(System::normalizeUrl(ModUtil::url($this->name, 'admin', 'view')));
    }
}
