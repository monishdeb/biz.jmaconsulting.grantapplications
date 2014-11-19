<?php
define('DRAFT_STATUS_ID', 8);
define('EMPLOYEE_OF_ID', 5);
require_once 'grantapplications.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function grantapplications_civicrm_config(&$config) {
  _grantapplications_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function grantapplications_civicrm_xmlMenu(&$files) {
  _grantapplications_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function grantapplications_civicrm_install() {
  _grantapplications_civix_civicrm_install();

  $smarty = CRM_Core_Smarty::singleton();
  $smarty->assign('currentDirectoryPath', __DIR__);
  CRM_Utils_File::sourceSQLFile(CIVICRM_DSN, $smarty->fetch(__DIR__ . '/sql/civicrm_msg_template.tpl'), NULL, TRUE);
  grantapplications_addRemoveMenu(TRUE);
  return TRUE;
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function grantapplications_civicrm_uninstall() {
  grantapplications_enableDisableNavigationMenu(2);
  return _grantapplications_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function grantapplications_civicrm_enable() {
  grantapplications_enableDisableNavigationMenu(1);
  return _grantapplications_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function grantapplications_civicrm_disable() {
  grantapplications_enableDisableNavigationMenu(0);
  return _grantapplications_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function grantapplications_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _grantapplications_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function grantapplications_civicrm_managed(&$entities) {
  return _grantapplications_civix_civicrm_managed($entities);
}

function grantapplications_civicrm_validate($formName, &$fields, &$files, &$form) {
  $errors = array();
  if ($formName == 'CRM_Grant_Form_Grant_Confirm') {
    $form->_errors = array();
  }
  if ($formName == 'CRM_Grant_Form_Grant_Main' && CRM_Utils_Array::value('grant_id', $fields)) {
    $grantType = CRM_Core_DAO::getFieldValue("CRM_Grant_DAO_Grant", $fields['grant_id'], "grant_type_id");
    $groupTree = CRM_Core_BAO_CustomGroup::getTree("Grant", $this, $fields['grant_id'], 0, $grantType);
    foreach ($groupTree as $field => $value) {
      if (isset($value['fields'])) {
        foreach ($value['fields'] as $key => $f) {
          if (CRM_Utils_Array::value('html_type', $f) == 'File' && isset($f['customValue'][1]['fid'])) {
            $form->setElementError('custom_' . $key, NULL);
          }
        }
      }
    }
    // On Behalf
    $ssParams['id'] = CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_saved_search WHERE form_values LIKE "%\"grant_id\";i:'.$fields['grant_id'].'%"');
    CRM_Contact_BAO_SavedSearch::retrieve($ssParams, $savedSearch);
    $grantParams = unserialize($savedSearch['form_values']);
    $subType = CRM_Contact_BAO_ContactType::subTypeInfo('Organization', TRUE);
    foreach ($subType as $key => $value) {
      $gTree[] = CRM_Core_BAO_CustomGroup::getTree("Organization", $this, $grantParams['contactID'], NULL, $key);
    }
    foreach ($gTree as $flds => $vs) {
      foreach ($vs as $fld => $v) {
        if (isset($v['fields'])) {
          foreach ($v['fields'] as $k => $f) {
            if (CRM_Utils_Array::value('html_type', $f) == 'File' && isset($f['customValue'][1]['fid'])) {
              $form->_errors['onbehalf[custom_'.$k.']'] = '';
              $form->setElementError('onbehalf[custom_'.$k.']', NULL);
            }
          }
        }
      }
    }
  }
  if (($formName == 'CRM_Grant_Form_Grant_Main' ||  $formName == 'CRM_Grant_Form_Grant_Confirm') 
    && $form->_values['is_draft'] == 1 && (CRM_Utils_Array::value('_qf_Main_save', $fields) == 'Save as Draft' || $form->_params['is_draft'] == 1)) {
    foreach($form->_fields as $name => $values) {
      $form->setElementError($name, NULL);
      $form->_errors = array();
    }
  }
  if ($formName == "CRM_UF_Form_Field" && CRM_Core_Permission::access('CiviGrant') 
    && ($form->getVar('_action') != CRM_Core_Action::DELETE)) {
    $fieldType = $fields['field_name'][0];
    $errorField = FALSE;
    //get the group type.
    $groupType = CRM_Core_BAO_UFGroup::calculateGroupType($form->getVar('_gid'), FALSE, CRM_Utils_Array::value('field_id', $fields));
    if ($fieldType == "Activity" || $fieldType == "Participant" || $fieldType == "Contribution" || $fieldType =="Membership") {
      if (in_array('Grant', $groupType)) {
        $errors['field_name'] = ts('The profile has a grant field already, and this field is not a contact or grant field.');
      }
    }
    elseif ($fieldType == "Grant") {
      if ( in_array('Membership', $groupType) || 
        in_array('Activity', $groupType) || 
        in_array('Participant', $groupType) || 
        in_array('Contribution', $groupType) ) {
        $errors['field_name'] = ts('A grant field can only be added to a profile that has only contact and grant fields. This profile has fields that are not contact or grant fields');
      }
    }
  }
  return $errors;
}

function grantapplications_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Grant_Form_Grant_Confirm') {
    
    // fix attachment info
    if (CRM_Utils_Array::value('fileFields', $form->_fields)) {
      foreach ($form->_fields['fileFields'] as $key => $value) {
        if (CRM_Utils_Array::value('fileID', $value)) {
          $url = CRM_Utils_System::url('civicrm/file',
            'reset=1&id='.$value['fileID'].'&eid='.$value['entityID'],
            FALSE, NULL, TRUE, TRUE
          );
          $fileType = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_File',
            $value['fileID'],
            'mime_type',
            'id'
          );  
          $fileName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_File',
            $value['fileID'],
            'uri',
            'id'
          );  
          if ($fileType == 'image/jpeg' ||
            $fileType == 'image/pjpeg' ||
            $fileType == 'image/gif' ||
            $fileType == 'image/x-png' ||
            $fileType == 'image/png'
          ) {
            $files[$key]['displayURL'] = $url;
          }
          else {
            $files[$key]['fileURL'] = $url;
          }
          $files[$key]['fileName'] = $fileName;
          $files[$key]['id'] = $key;
          $files[$key]['fileID'] = $value['fileID'];
          if (CRM_Utils_Array::value($key, $form->_params)) {
            if (in_array($form->_params[$key]['type'], array('image/jpeg', 'image/pjpeg', 'image/gif',  'image/x-png', 'image/png'))) {
              unset($files[$key]);
              $files[$key]['displayURLnew'] = $form->_params[$key]['name'];
              preg_match("/[^\/]+$/", $form->_params[$key]['name'], $matches);
              $files[$key]['fileName'] = $matches[0];
            }
            else {
              unset($files[$key]);
              $files[$key]['fileURLnew'] = $form->_params[$key]['name'];
              preg_match("/[^\/]+$/", $form->_params[$key]['name'], $matches);
              $files[$key]['fileName'] = $matches[0];
            }
          }
        }
        else {
          $files[$key]['noDisplay'] = TRUE;
          if (CRM_Utils_Array::value($key, $form->_params)) {
            if (in_array($form->_params[$key]['type'], array('image/jpeg', 'image/pjpeg', 'image/gif',  'image/x-png', 'image/png'))) {
              unset($files[$key]);
              $files[$key]['displayURLnew'] = $form->_params[$key]['name'];
              preg_match("/[^\/]+$/", $form->_params[$key]['name'], $matches);
              $files[$key]['fileName'] = $matches[0];
            }
            else {
              unset($files[$key]);
              $files[$key]['fileURLnew'] = $form->_params[$key]['name'];
              preg_match("/[^\/]+$/", $form->_params[$key]['name'], $matches);
              $files[$key]['fileName'] = $matches[0];
            }
          }
        }
      }
      $form->assign('files', $files);
    }
  }
  if ($formName == 'CRM_Grant_Form_Grant_ThankYou') { 
    // fix attachment info
    if (CRM_Utils_Array::value('fileFields', $form->_fields)) {
      foreach ($form->_fields['fileFields'] as $key => $value) {
        if (CRM_Utils_Array::value('fileID', $value)) {
          $url = CRM_Utils_System::url('civicrm/file',
            'reset=1&id='.$value['fileID'].'&eid='.$value['entityID'],
            FALSE, NULL, TRUE, TRUE
          );
          $fileType = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_File',
            $value['fileID'],
            'mime_type',
            'id'
          );  
          $fileName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_File',
            $value['fileID'],
            'uri',
            'id'
          );  
          if ($fileType == 'image/jpeg' ||
            $fileType == 'image/pjpeg' ||
            $fileType == 'image/gif' ||
            $fileType == 'image/x-png' ||
            $fileType == 'image/png'
          ) {
            $files[$key]['displayURL'] = $url;
          }
          else {
            $files[$key]['fileURL'] = $url;
          }
          $files[$key]['id'] = $key;
          $files[$key]['fileID'] = $value['fileID'];
          $files[$key]['fileName'] = $fileName;
        }
        else {
          $files[$key]['noDisplay'] = TRUE;
        }
      }
      $form->assign('files', $files);
    }
  }
  if ($formName == "CRM_Grant_Form_GrantPage_Settings" || 
    $formName == "CRM_Grant_Form_GrantPage_Custom" ||  
    $formName == "CRM_Grant_Form_GrantPage_Draft" || 
    $formName == "CRM_Grant_Form_GrantPage_ThankYou") {
    CRM_Core_Region::instance('page-body')->add(array(
       'template' => 'CRM/css/grantapplications.tpl',
    ));
  } 
  // Code to be done to avoid core editing
  if ($formName == "CRM_UF_Form_Field" && CRM_Core_Permission::access('CiviGrant')) {
    $grantFields = CRM_Grantapplications_BAO_GrantApplicationProfile::getProfileFields();
    $fields['Grant'] = $grantFields;
    // Add the grant fields to the form
    $originalFields = $form->getVar('_fields');
    $form->setVar('_fields', array_merge(CRM_Grantapplications_BAO_GrantApplicationProfile::exportableFields(), $originalFields));
    $originalSelect = $form->getVar('_selectFields');

    foreach ($fields as $key => $value) {
      foreach ($value as $key1 => $value1) {
        //CRM-2676, replacing the conflict for same custom field name from different custom group.
        if ($customFieldId = CRM_Core_BAO_CustomField::getKeyID($key1)) {
          $customGroupId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', $customFieldId, 'custom_group_id');
          $customGroupName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $customGroupId, 'title');
          $mapperFields[$key][$key1] = $value1['title'] . ' :: ' . $customGroupName;
          $selectFields[$key][$key1] = $value1['title'];
        }
        else {
          $mapperFields[$key][$key1] = $value1['title'];
          $selectFields[$key][$key1] = $value1['title'];
        }
        $hasLocationTypes[$key][$key1] = CRM_Utils_Array::value('hasLocationType', $value1);
      }
    }
    if (!empty($selectFields['Grant'])) {
      $form->setVar('_selectFields', array_merge($selectFields['Grant'], $originalSelect));
    }
    if(!empty($noSearchable)) {
      $form->assign('noSearchable', $noSearchable);
    }
    $grantArray = array(
      'text' => 'Grant',
      'attr' => array('value' => 'Grant')
    );

    foreach ($form->_elements as $eleKey => $eleVal) {
      foreach ($eleVal as $optionKey => $optionVal) {
        if ($optionKey == '_options') {
          $form->_elements[$eleKey]->_options[0]['Grant'] = 'Grant';
          $form->_elements[$eleKey]->_options[1]['Grant'] = $mapperFields['Grant'];
        }
        if ($optionKey == '_elements') {
          $form->_elements[$eleKey]->_elements[0]->_options[] = $grantArray;
        } 
        if ($optionKey == '_js') {
          $form->_elements[$eleKey]->_js .= 'hs_field_name_Grant = '. json_encode($mapperFields['Grant']) . ';';
        }
      }
    } 
    if ($form->_defaultValues && array_key_exists('field_name', $form->_defaultValues) 
      && $form->_defaultValues['field_name'][0] == 'Grant') {
      $defaults['field_name'] = $form->_defaultValues['field_name'];
      $form->setDefaults($defaults);
    }
  }
}

function grantapplications_civicrm_pageRun( &$page ) {
  if ($page->getVar('_name') == 'CRM_Contact_Page_View_UserDashBoard') {
    $cid = $page->getVar('_contactId'); 
    // Check if grant program extension is enabled
    $enabled = CRM_Grant_BAO_GrantApplicationPage::checkExtensionEnabled('biz.jmaconsulting.grantprograms');
    $smarty = CRM_Core_Smarty::singleton();
    $rels = $smarty->get_template_vars('currentRelationships');
    $actionLinks = $smarty->get_template_vars('grant_rows');
    $permissions = array(CRM_Core_Permission::VIEW);
    if (CRM_Core_Permission::check('edit grants')) {
      $permissions[] = CRM_Core_Permission::EDIT;
    }
    if (CRM_Core_Permission::check('delete in CiviGrant')) {
      $permissions[] = CRM_Core_Permission::DELETE;
    }
    $mask = CRM_Core_Action::mask($permissions);
    foreach ($actionLinks as $key => $fields) {
      if (CRM_Utils_Array::value('grant_status', $fields) != 'Draft') {
        unset($actionLinks[$key]);
        continue;
      }
      $ssID = CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_saved_search WHERE form_values LIKE "%\"grant_id\";i:'.$fields['grant_id'].'%"');
      if ($ssID) {
        $formValues = CRM_Contact_BAO_SavedSearch::getFormValues($ssID);
        $actionLinks[$key]['action'] = CRM_Core_Action::formLink(dashboardActionLinks(),
          $mask,
          array(
            'id' => $formValues['grantApplicationPageID'],
            'gid' => $fields['grant_id'],
          )
        );
      }
    } 
    $page->assign('grant_rows', $actionLinks);
    if (!empty($rels)) {
      foreach($rels as $id => $values) {
        if ($values['relationship_type_id'] != EMPLOYEE_OF_ID) {
          continue;
        }
        $query = "SELECT grant_type_id, application_received_date, amount_total, status_id, id FROM civicrm_grant WHERE contact_id = {$values['cid']} AND status_id = ".DRAFT_STATUS_ID;
        $dao = CRM_Core_DAO::executeQuery($query);
        while ($dao->fetch()) {
          $row = "";
          $row['contact_id'] = $values['cid'];
          $row['sort_name'] = $values['display_name'];
          $row['grant_type'] = current(CRM_Core_OptionGroup::values('grant_type', FALSE, FALSE, FALSE, " AND v.value = {$dao->grant_type_id}"));
          $row['grant_application_received_date'] = $dao->application_received_date;
          $row['grant_amount_total'] = $dao->amount_total;
          $row['grant_status'] = 'Draft';
          if ($enabled) {
            $row['program_id'] = CRM_Core_DAO::getFieldValue('CRM_Grant_DAO_Grant', $dao->id, 'grant_program_id');
            $row['program_name'] = current(CRM_Grant_BAO_GrantProgram::getGrantPrograms($row['program_id']));
          }
          $ssID = CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_saved_search WHERE form_values LIKE "%\"grant_id\";i:'.$dao->id.'%"');
          if ($ssID) {
            $formValues = CRM_Contact_BAO_SavedSearch::getFormValues($ssID);
            $row['action'] = CRM_Core_Action::formLink(dashboardActionLinks(),
              $mask,
              array(
                'id' => $formValues['grantApplicationPageID'],
                'gid' => $dao->id,
              )
            );
          }
          $rows[] = $row;
        }
      }
    }
    if (!empty($rows)) {
      $grantRows = $smarty->get_template_vars('grant_rows');
      $grants = array_merge($grantRows, $rows);
      $smarty->assign('grant_rows', $grants);
      $smarty->assign('enabled', $enabled);
    }
  }
  if( $page->getVar('_name') == 'CRM_Grant_Page_DashBoard') {
    //FIXME: Avoid overwriting core
      CRM_Core_Region::instance('page-body')->add(array(
        'template' => 'CRM/Grant/Page/GrantApplicationDashboard.tpl',
      ));
    return;
    $action = CRM_Utils_Request::retrieve('action', 'String',
      // default to 'browse'
      $page, FALSE, 'browse'
    );
    $breadCrumb = array(array('title' => ts('Add Grant Application Page'),
      'url' => CRM_Utils_System::url(CRM_Utils_System::currentPath(),
      'reset=1'
     ),
    ));
    // what action to take ?
    if ($action & CRM_Core_Action::ADD) {
      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url('civicrm/admin/grant/apply/settings',
        'action=add&reset=1'
      ));
    
      $controller = new CRM_Grant_Controller_GrantPage(NULL, $action);
      CRM_Utils_System::setTitle(ts('Manage Grant Application Page'));
      CRM_Utils_System::appendBreadCrumb($breadCrumb);
      return $controller->run();
    }
    elseif ($action & CRM_Core_Action::BROWSE) {
      browse();
      CRM_Core_Region::instance('page-body')->add(array(
        'template' => 'CRM/Grant/Page/GrantApplicationDashboard.tpl',
      ));
    }
    elseif ($action & CRM_Core_Action::DELETE) {
      CRM_Utils_System::appendBreadCrumb($breadCrumb);

      $session = CRM_Core_Session::singleton();
      $session->pushUserContext(CRM_Utils_System::url(CRM_Utils_System::currentPath(),
       'reset=1&action=delete'
      ));

      $id = CRM_Utils_Request::retrieve('id', 'Positive',
        $this, FALSE, 0
     );
    
      $controller = new CRM_Core_Controller_Simple('CRM_Grant_Form_GrantPage_Delete',
        'Delete Grant Application Page',
        CRM_Core_Action::DELETE
      );
      $controller->set('id', $id);
      $controller->process();
      return $controller->run();
    }
    else {
      
    }
  }
}

function browse($action = NULL) {
  $params = array();
  $query = "SELECT * from civicrm_grant_app_page WHERE 1";
  $grantPage = CRM_Core_DAO::executeQuery($query, $params, TRUE, 'CRM_Grant_DAO_GrantApplicationPage');
  $rows = array();
  $allowToDelete = CRM_Core_Permission::check('delete in CiviGrant');
  //get configure actions links.
  $configureActionLinks = configureActionLinks();
  $query = "
         SELECT  id
         FROM  civicrm_grant_app_page
         WHERE  1";
  $grantAppPage = CRM_Core_DAO::executeQuery($query, $params, TRUE, 'CRM_Grant_DAO_GrantApplicationPage');
  $grantAppPageIds = array();
  while ($grantAppPage->fetch()) {
    $grantAppIds[$grantAppPage->id] = $grantAppPage->id;
  }
  //get all section info.
  $grantAppPageSectionInfo = CRM_Grant_BAO_GrantApplicationPage::getSectionInfo($grantAppPageIds);

  while ( $grantPage->fetch() ) {
    $rows[$grantPage->id] = array();
    CRM_Core_DAO::storeValues($grantPage, $rows[$grantPage->id]);

    // form all action links
    $action = array_sum(array_keys(actionLinks()));

    //add configure actions links.
    $action += array_sum(array_keys($configureActionLinks));

    //add online grant links.
    $action += array_sum(array_keys(onlineGrantLinks()));

    if ($grantPage->is_active) {
      $action -= CRM_Core_Action::ENABLE;
    }
    else {
      $action -= CRM_Core_Action::DISABLE;
    }
         
    //CRM-4418
    if (!$allowToDelete) {
      $action -= CRM_Core_Action::DELETE;
    }
    $sectionsInfo = CRM_Utils_Array::value($grantPage->id, $grantAppPageSectionInfo, array());

    $rows[$grantPage->id]['configureActionLinks'] = CRM_Core_Action::formLink(formatConfigureLinks($sectionsInfo),
       $action,
       array('id' => $grantPage->id),
       ts('Configure'),
       TRUE,
       'grantapppage.configure.actions',
       'GrantAppPage',
       $grantPage->id
    );
    //build the online grant application links.
    $rows[$grantPage->id]['onlineGrantLinks'] = CRM_Core_Action::formLink(onlineGrantLinks(),
      $action,
      array('id' => $grantPage->id),
      ts('Grant Application (Live)'),
      FALSE,
      'grantapppage.online.links',
      'GrantAppPage',
      $grantPage->id
    );
    
    //build the normal action links.
    $rows[$grantPage->id]['action'] = CRM_Core_Action::formLink(actionLinks(),
      $action,
      array('id' => $grantPage->id),
      ts('more'),
      TRUE,
      'grantapppage.action.links',
      'GrantAppPage',
      $grantPage->id
    );
    
    $rows[$grantPage->id]['title'] = $grantPage->title;
    $rows[$grantPage->id]['is_active'] = $grantPage->is_active;
    $rows[$grantPage->id]['id'] = $grantPage->id;
      
  }
  $smarty = CRM_Core_Smarty::singleton( );
  $smarty->assign('fields', $rows);
}


function configureActionLinks() {
  // check if variable _actionsLinks is populated
  $urlString = 'civicrm/admin/grant/';
  $urlParams = 'reset=1&action=update&id=%%id%%';

  $configureActionLinks = array(
    CRM_Core_Action::ADD => array(
      'name' => ts('Info and Settings'),
      'title' => ts('Info and Settings'),
      'url' => $urlString . 'settings',
      'qs' => $urlParams,
      'uniqueName' => 'settings',
    ),
    CRM_Core_Action::FOLLOWUP => array(
      'name' => ts('Save as Draft'),
      'title' => ts('Save as Draft'),
      'url' => $urlString . 'draft',
      'qs' => $urlParams,
      'uniqueName' => 'draft',
    ),
    CRM_Core_Action::EXPORT => array(
      'name' => ts('Receipt'),
      'title' => ts('Receipt'),
      'url' => $urlString . 'thankyou',
      'qs' => $urlParams,
      'uniqueName' => 'thankyou',
    ),
    CRM_Core_Action::PROFILE => array(
      'name' => ts('Profiles'),
      'title' => ts('Profiles'),
      'url' => $urlString . 'custom',
      'qs' => $urlParams,
      'uniqueName' => 'custom',
                                      ),
    );  
  return $configureActionLinks;
}

function dashboardActionLinks() {
  $dashboardActionLinks = array(
    CRM_Core_Action::UPDATE => array(
      'name' => ts('Edit'),
      'url' => 'civicrm/grant/transact',
      'qs' => 'reset=1&id=%%id%%&gid=%%gid%%',
      'title' => ts('Edit Grant Application'),
    ),
  );
  return $dashboardActionLinks;
}

function actionLinks() {
  // check if variable _actionsLinks is populated
  // helper variable for nicer formatting
  $deleteExtra = ts('Are you sure you want to delete this Grant application page?');
  
  $actionLinks = array(
    CRM_Core_Action::DISABLE => array(
      'name' => ts('Disable'),
      'title' => ts('Disable'),
      'ref' => 'crm-enable-disable',
    ),
    CRM_Core_Action::ENABLE => array(
      'name' => ts('Enable'),
      'ref' => 'crm-enable-disable',
      'title' => ts('Enable'),
    ),
    CRM_Core_Action::DELETE => array(
      'name' => ts('Delete'),
      'url' => CRM_Utils_System::currentPath(),
      'qs' => 'reset=1&action=delete&id=%%id%%',
      'title' => ts('Delete'),
      'extra' => 'onclick = "return confirm(\'' . $deleteExtra . '\');"',
    ),
  );
  return $actionLinks;
}

function onlineGrantLinks() {
  $urlString = 'civicrm/grant/transact';
  $urlParams = 'reset=1&id=%%id%%';
  $onlineGrantLinks = array(
    CRM_Core_Action::RENEW => array(
      'name' => ts('Grant Application (Live)'),
      'title' => ts('Grant Application (Live)'),
      'url' => $urlString,
      'qs' => $urlParams,
      'fe' => TRUE,
      'uniqueName' => 'live_page',
    ),
  );
  return $onlineGrantLinks;
}

function formatConfigureLinks($sectionsInfo) {
  //build the formatted configure links.
  $formattedConfLinks = configureActionLinks();
  foreach ($formattedConfLinks as $act => & $link) {
    $sectionName = CRM_Utils_Array::value('uniqueName', $link);
    if (!$sectionName) {
      continue;
    }

    $classes = array();
    if (isset($link['class'])) {
      $classes = $link['class'];
    }

    if (!CRM_Utils_Array::value($sectionName, $sectionsInfo)) {
      $classes = array();
      if (isset($link['class'])) {
        $classes = $link['class'];
      }
      $link['class'] = array_merge($classes, array('disabled'));
    }
  }

  return $formattedConfLinks;
}

function grantapplications_addRemoveMenu($enable) {
  $config = CRM_Core_Config::singleton();
  
  $params['enableComponents'] = $config->enableComponents;
  if ($enable) {
    if (array_search('CiviGrant', $config->enableComponents)) {
      return NULL;
    }
    $params['enableComponents'][] = 'CiviGrant';
  }
  else {
    $key = array_search('CiviGrant', $params['enableComponents']);
    if ($key) {
      unset($params['enableComponents'][$key]);
    }
  }
  
  CRM_Core_BAO_Setting::setItem($params['enableComponents'],
    CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,'enable_components');
}

function grantapplications_civicrm_entityTypes(&$entityTypes) {
  $entityTypes['CRM_Grant_DAO_GrantApplicationPage'] = array(
    'name' => 'GrantApplicationPage',
    'class' => 'CRM_Grant_DAO_GrantApplicationPage',
    'table' => 'civicrm_grant_app_page',
  );
}


/**
 * function to disable/enable/delete navigation menu
 *
 * @param integer $action 
 *
 */

function grantapplications_enableDisableNavigationMenu($action) {
  $domainID = CRM_Core_Config::domainID();
  
  $enableDisableDeleteData = NULL;
  if ($action != 1) {
    $enableDisableDeleteData = CRM_Grantapplications_BAO_GrantApplicationProfile::checkRelatedExtensions();   
  }


  if ($action < 2) { 
    
    if (!$enableDisableDeleteData) {
      CRM_Core_DAO::executeQuery(
        "UPDATE civicrm_uf_group SET is_active = %1 WHERE group_type LIKE '%Grant%'", 
        array(
          1 => array($action, 'Integer'),
        )
      ); 
    }
    
    CRM_Core_DAO::executeQuery(
      "UPDATE civicrm_option_value 
       INNER JOIN civicrm_option_group ON  civicrm_option_value.option_group_id = civicrm_option_group.id
       INNER JOIN civicrm_msg_template ON civicrm_msg_template.workflow_id = civicrm_option_value.id
         SET civicrm_option_value.is_active = %1,
           civicrm_option_group.is_active = %1,
           civicrm_msg_template.is_active = %1
       WHERE civicrm_option_group.name LIKE 'msg_tpl_workflow_grant'", 
      array(
        1 => array($action, 'Integer')
      )
    ); 
    
    CRM_Core_DAO::executeQuery(
      "UPDATE civicrm_navigation SET is_active = %2 WHERE name = 'New Grant Application Page' AND domain_id = %1", 
      array(
        1 => array($domainID, 'Integer'),
        2 => array($action, 'Integer')
      )
    ); 
  }
  else {
    CRM_Core_DAO::executeQuery(
      "DELETE FROM civicrm_navigation  WHERE name = 'New Grant Application Page' AND domain_id = %1", 
      array(
        1 => array($domainID, 'Integer')
      )
    );
    
    if ($enableDisableDeleteData === NULL) {
      CRM_Core_DAO::executeQuery(
        "DELETE uj.*, uf.*, g.* FROM civicrm_uf_group g
         LEFT JOIN civicrm_uf_join uj ON uj.uf_group_id = g.id
         LEFT JOIN civicrm_uf_field uf ON uf.uf_group_id = g.id
         WHERE g.group_type LIKE '%Grant%';"
      );
    }
    $action = 0;
  }
  
  if ($enableDisableDeleteData) {
    return FALSE;
  }
  
  grantapplications_addRemoveMenu($action);
}