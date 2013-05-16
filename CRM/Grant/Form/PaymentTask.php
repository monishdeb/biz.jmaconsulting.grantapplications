<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2011
 * $Id$
 *
 */

/**
 * This class generates task actions for CiviEvent
 * 
 */
class CRM_Grant_Form_PaymentTask extends CRM_Core_Form {
  /**
   * the task being performed
   *
   * @var int
   */
  protected $_task;

  /**
   * The additional clause that we restrict the search with
   *
   * @var string
   */
  protected $_componentClause = NULL;

  /**
   * The array that holds all the component ids
   *
   * @var array
   */
  protected $_componentIds;

  /**
   * The array that holds all the grant ids
   *
   * @var array
   */
  protected $_grantIds;

  /**
   * build all the data structures needed to build the form
   *
   * @param
   * @return void
   * @access public
   */
  function preProcess() {
    self::preProcessCommon($this);
  }

  static function preProcessCommon(&$form, $useTable = FALSE) {
    $form->_grantIds = array();
        
    if (!CRM_Utils_Array::value('prid', $_GET )) {
      $values = $form->controller->exportValues('PaymentSearch');
    }

    $form->_task = CRM_Utils_Array::value('task', $values);
    $grantPaymentTasks = CRM_Grant_PaymentTask::tasks();
    if ( !empty($form->_task)) {
      $form->assign('taskName', $grantPaymentTasks[$form->_task]);
    }
    $ids = array();
    if (CRM_Utils_Array::value('radio_ts', $values) == 'ts_sel') {
      foreach ($values as $name => $value) {
        if (substr($name, 0, CRM_Core_Form::CB_PREFIX_LEN) == CRM_Core_Form::CB_PREFIX) {
          $ids[] = substr($name, CRM_Core_Form::CB_PREFIX_LEN);
        }
      }
    } 
    else {
      $queryParams = $form->get('queryParams');
      $query = new CRM_Grant_BAO_PaymentSearch( 
        $queryParams, 
        NULL, 
        NULL, 
        FALSE, 
        FALSE, 
        CRM_Grant_BAO_PaymentSearch::MODE_GRANT_PAYMENT
      );
           
      $query->_distinctComponentClause = " civicrm_payment.id";
      $query->_groupByComponentClause  = " GROUP BY civicrm_payment.id ";
      $result = $query->searchQuery(0, 0, NULL);
      while ($result->fetch()) {
        $ids[] = $result->id;
      }
    }

    if (!empty($ids)) {
      $form->_componentClause =
        ' civicrm_payment.id IN ( ' .
        implode(',', $ids) . ' ) ';
      $form->assign('totalSelectedGrants', count($ids));             
    }
        
    $form->_grantPaymentIds = $form->_componentIds = $ids;

    //set the context for redirection for any task actions
    $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $this);
    $urlParams = 'force=1';
    if (CRM_Utils_Rule::qfKey($qfKey)) {
      $urlParams .= "&qfKey=$qfKey";
    }
    $session = CRM_Core_Session::singleton();
    $session->replaceUserContext(CRM_Utils_System::url('civicrm/grant/payment/search', $urlParams));
  }

  /**
   * Given the grant id, compute the contact id
   * since its used for things like send email
   */
  public function setContactIDs() {
    $this->_contactIds =& CRM_Core_DAO::getContactIDsFromComponent($this->_grantIds,
      'civicrm_grant' 
    );
  }

  /**
   * simple shell that derived classes can call to add buttons to
   * the form with a customized title for the main Submit
   *
   * @param string $title title of the main button
   * @param string $type  button type for the form after processing
   * @return void
   * @access public
   */
  function addDefaultButtons($title, $nextType = 'next', $backType = 'back') {
    $this->addButtons(array(
      array ( 
        'type' => $nextType,
        'name' => $title,
        'isDefault' => TRUE),
      array ( 
        'type' => $backType,
        'name' => ts('Back')),
      )
    );
  }
}
