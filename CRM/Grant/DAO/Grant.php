<?php
/*
+--------------------------------------------------------------------+
| CiviCRM version 4.3                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
require_once 'CRM/Core/DAO.php';
require_once 'CRM/Utils/Type.php';
class CRM_Grant_DAO_Grant extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_grant';
  /**
   * static instance to hold the field values
   *
   * @var array
   * @static
   */
  static $_fields = null;
  /**
   * static instance to hold the keys used in $_fields for each field.
   *
   * @var array
   * @static
   */
  static $_fieldKeys = null;
  /**
   * static instance to hold the FK relationships
   *
   * @var string
   * @static
   */
  static $_links = null;
  /**
   * static instance to hold the values that can
   * be imported
   *
   * @var array
   * @static
   */
  static $_import = null;
  /**
   * static instance to hold the values that can
   * be exported
   *
   * @var array
   * @static
   */
  static $_export = null;
  /**
   * static value to see if we should log any modifications to
   * this table in the civicrm_log table
   *
   * @var boolean
   * @static
   */
  static $_log = true;
  /**
   * Unique Grant id
   *
   * @var int unsigned
   */
  public $id;
  /**
   * Contact ID of contact record given grant belongs to.
   *
   * @var int unsigned
   */
  public $contact_id;
  /**
   * Grant Program ID of grant program record given grant belongs to.
   *
   * @var int unsigned
   */
  public $grant_program_id;
  /**
   * Date on which grant application was received by donor.
   *
   * @var date
   */
  public $application_received_date;
  /**
   * Date on which grant decision was made.
   *
   * @var date
   */
  public $decision_date;
  /**
   * Date on which grant money transfer was made.
   *
   * @var date
   */
  public $money_transfer_date;
  /**
   * Date on which grant report is due.
   *
   * @var date
   */
  public $grant_due_date;
  /**
   * Yes/No field stating whether grant report was received by donor.
   *
   * @var boolean
   */
  public $grant_report_received;
  /**
   * Type of grant. Implicit FK to civicrm_option_value in grant_type option_group.
   *
   * @var int unsigned
   */
  public $grant_type_id;
  /**
   * Requested grant amount, in default currency.
   *
   * @var float
   */
  public $amount_total;
  /**
   * Requested grant amount, in original currency (optional).
   *
   * @var float
   */
  public $amount_requested;
  /**
   * Granted amount, in default currency.
   *
   * @var float
   */
  public $amount_granted;
  /**
   * 3 character string, value from config setting or input via user.
   *
   * @var string
   */
  public $currency;
  /**
   * Grant rationale.
   *
   * @var text
   */
  public $rationale;
  /**
   * Id of Grant status.
   *
   * @var int unsigned
   */
  public $status_id;
  /**
   * Id of Grant Rejected Reason.
   *
   * @var int unsigned
   */
  public $grant_rejected_reason_id;
  /**
   * Id of Grant Incomplete Reason.
   *
   * @var int unsigned
   */
  public $grant_incomplete_reason_id;
  /**
   *
   * @var string
   */
  public $assessment;
  /**
   * FK to Financial Type.
   *
   * @var int unsigned
   */
  public $financial_type_id;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_grant
   */
  function __construct()
  {
    $this->__table = 'civicrm_grant';
    parent::__construct();
  }
  /**
   * return foreign links
   *
   * @access public
   * @return array
   */
  function links()
  {
    if (!(self::$_links)) {
      self::$_links = array(
        new CRM_Core_EntityReference(self::getTableName() , 'contact_id', 'civicrm_contact', 'id') ,
        new CRM_Core_EntityReference(self::getTableName() , 'grant_program_id', 'civicrm_grant_program', 'id') ,
        new CRM_Core_EntityReference(self::getTableName() , 'financial_type_id', 'civicrm_financial_type', 'id') ,
      );
    }
    return self::$_links;
  }
  /**
   * returns all the column names of this table
   *
   * @access public
   * @return array
   */
  static function &fields()
  {
    if (!(self::$_fields)) {
      self::$_fields = array(
        'grant_id' => array(
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Grant ID') ,
          'required' => true,
          'import' => true,
          'where' => 'civicrm_grant.id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'grant_contact_id' => array(
          'name' => 'contact_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Contact ID') ,
          'required' => true,
          'export' => true,
          'where' => 'civicrm_grant.contact_id',
          'headerPattern' => '',
          'dataPattern' => '',
          'FKClassName' => 'CRM_Contact_DAO_Contact',
        ) ,
        'grant_program_id' => array(
          'name' => 'grant_program_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Grant Program ID') ,
          'required' => true,
          'export' => true,
          'where' => 'civicrm_grant.grant_program_id',
          'headerPattern' => '',
          'dataPattern' => '',
          'FKClassName' => 'CRM_Grant_DAO_GrantProgram',
        ) ,
        'application_received_date' => array(
          'name' => 'application_received_date',
          'type' => CRM_Utils_Type::T_DATE,
          'title' => ts('Application received date') ,
          'export' => true,
          'where' => 'civicrm_grant.application_received_date',
          'headerPattern' => '',
          'dataPattern' => '',
        ) ,
        'decision_date' => array(
          'name' => 'decision_date',
          'type' => CRM_Utils_Type::T_DATE,
          'title' => ts('Decision date') ,
          'import' => true,
          'where' => 'civicrm_grant.decision_date',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'money_transfer_date' => array(
          'name' => 'money_transfer_date',
          'type' => CRM_Utils_Type::T_DATE,
          'title' => ts('Grant Money transfer date') ,
          'import' => true,
          'where' => 'civicrm_grant.money_transfer_date',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'grant_due_date' => array(
          'name' => 'grant_due_date',
          'type' => CRM_Utils_Type::T_DATE,
          'title' => ts('Grant Due Date') ,
        ) ,
        'grant_report_received' => array(
          'name' => 'grant_report_received',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Grant report received') ,
          'import' => true,
          'where' => 'civicrm_grant.grant_report_received',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'grant_type_id' => array(
          'name' => 'grant_type_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Grant Type Id') ,
          'required' => true,
          'export' => false,
          'where' => 'civicrm_grant.grant_type_id',
          'headerPattern' => '',
          'dataPattern' => '',
          'pseudoconstant' => array(
            'name' => 'grantType',
            'optionGroupName' => 'grant_type',
          )
        ) ,
        'amount_total' => array(
          'name' => 'amount_total',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => ts('Total Amount') ,
          'required' => true,
          'import' => true,
          'where' => 'civicrm_grant.amount_total',
          'headerPattern' => '',
          'dataPattern' => '/^\d+(\.\d{2})?$/',
          'export' => true,
        ) ,
        'amount_requested' => array(
          'name' => 'amount_requested',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => ts('Amount Requested') ,
        ) ,
        'amount_granted' => array(
          'name' => 'amount_granted',
          'type' => CRM_Utils_Type::T_MONEY,
          'title' => ts('Amount granted') ,
          'import' => true,
          'where' => 'civicrm_grant.amount_granted',
          'headerPattern' => '',
          'dataPattern' => '/^\d+(\.\d{2})?$/',
          'export' => true,
        ) ,
        'currency' => array(
          'name' => 'currency',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Grant Currency') ,
          'required' => true,
          'maxlength' => 3,
          'size' => CRM_Utils_Type::FOUR,
          'pseudoconstant' => array(
            'table' => 'civicrm_currency',
            'keyColumn' => 'name',
            'labelColumn' => 'full_name',
            'nameColumn' => 'numeric_code',
          )
        ) ,
        'rationale' => array(
          'name' => 'rationale',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => ts('Grant Rationale') ,
          'rows' => 4,
          'cols' => 60,
          'import' => true,
          'where' => 'civicrm_grant.rationale',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'grant_status_id' => array(
          'name' => 'status_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Grant Status Id') ,
          'required' => true,
          'import' => true,
          'where' => 'civicrm_grant.status_id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => false,
          'pseudoconstant' => array(
            'optionGroupName' => 'grant_status',
          )
        ) ,
        'grant_rejected_reason_id' => array(
          'name' => 'grant_rejected_reason_id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => true,
          'import' => true,
          'where' => 'civicrm_grant.grant_rejected_reason_id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => false,
        ) ,
        'grant_incomplete_reason_id' => array(
          'name' => 'grant_incomplete_reason_id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => true,
          'import' => true,
          'where' => 'civicrm_grant.grant_incomplete_reason_id',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => false,
        ) ,
        'assessment' => array(
          'name' => 'assessment',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Assessment') ,
          'required' => true,
          'size' => CRM_Utils_Type::TWO,
          'import' => true,
          'where' => 'civicrm_grant.assessment',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => false,
        ) ,
        'financial_type_id' => array(
          'name' => 'financial_type_id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Financial Type') ,
          'default' => 'NULL',
          'FKClassName' => 'CRM_Financial_DAO_FinancialType',
        ) ,
      );
    }
    return self::$_fields;
  }
  /**
   * Returns an array containing, for each field, the arary key used for that
   * field in self::$_fields.
   *
   * @access public
   * @return array
   */
  static function &fieldKeys()
  {
    if (!(self::$_fieldKeys)) {
      self::$_fieldKeys = array(
        'id' => 'grant_id',
        'contact_id' => 'grant_contact_id',
        'application_received_date' => 'application_received_date',
        'decision_date' => 'decision_date',
        'money_transfer_date' => 'money_transfer_date',
        'grant_due_date' => 'grant_due_date',
        'grant_report_received' => 'grant_report_received',
        'grant_type_id' => 'grant_type_id',
        'amount_total' => 'amount_total',
        'amount_requested' => 'amount_requested',
        'amount_granted' => 'amount_granted',
        'currency' => 'currency',
        'rationale' => 'rationale',
        'status_id' => 'grant_status_id',
        'financial_type_id' => 'financial_type_id',
      );
    }
    return self::$_fieldKeys;
  }
  /**
   * returns the names of this table
   *
   * @access public
   * @static
   * @return string
   */
  static function getTableName()
  {
    return self::$_tableName;
  }
  /**
   * returns if this table needs to be logged
   *
   * @access public
   * @return boolean
   */
  function getLog()
  {
    return self::$_log;
  }
  /**
   * returns the list of fields that can be imported
   *
   * @access public
   * return array
   * @static
   */
  static function &import($prefix = false)
  {
    if (!(self::$_import)) {
      self::$_import = array();
      $fields = self::fields();
      foreach($fields as $name => $field) {
        if (CRM_Utils_Array::value('import', $field)) {
          if ($prefix) {
            self::$_import['grant'] = & $fields[$name];
          } else {
            self::$_import[$name] = & $fields[$name];
          }
        }
      }
    }
    return self::$_import;
  }
  /**
   * returns the list of fields that can be exported
   *
   * @access public
   * return array
   * @static
   */
  static function &export($prefix = false)
  {
    if (!(self::$_export)) {
      self::$_export = array();
      $fields = self::fields();
      foreach($fields as $name => $field) {
        if (CRM_Utils_Array::value('export', $field)) {
          if ($prefix) {
            self::$_export['grant'] = & $fields[$name];
          } else {
            self::$_export[$name] = & $fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
