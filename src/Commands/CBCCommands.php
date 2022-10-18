<?php

namespace Drupal\amf_cbc\Commands;

use Drush\Commands\DrushCommands;

/**
 * Commands for testing the CBC module.
 *
 * @package Drupal\drush9_custom_commands\Commands
 */
class CBCCommands extends DrushCommands {

  /**
   * Drush command that displays the given text.
   *
   * @param string $text
   *   Argument with message to be displayed.
   * @param array $options
   *   Argument with options for command.
   *
   * @command amf_cbc:test-message
   * @aliases amf-msg
   * @option uppercase
   *   Uppercase the message.
   * @usage amf_cbc:test-message --uppercase drupal8
   */
  public function message(
    $text = 'Hello world!',
    array $options = ['uppercase' => FALSE]
  ) {
    if ($options['uppercase']) {
      $text = strtoupper($text);
    }
    $this->output()->writeln($text);
  }

  /**
   * Tests requests for the cbc api.
   *
   * @param string $ip
   *   IP of the tester server's location.
   *
   * @command amf_cbc:test-api-requests
   * @aliases atar
   * @usage amf_cbc:test-api-requests local
   */
  public function testRequests(string $ip) {
    if ($ip == 'server') {
      $ip = $_SERVER['SERVER_ADDR'];
    }
    else {
      $ip = '84.43.136.108';
    }
    $config = \Drupal::config('amf_cbc.settings');
    $cbc_app = \Drupal::service('amf_cbc');

    $this->testPwdChange($cbc_app, $cbc_app::UPDATE_MASTER);
    $this->testPwdChange($cbc_app, $cbc_app::UPDATE_TESTER);

    $data = $this->getTestFormData();
    $result = $this->testPushDataToApi($cbc_app, $data);
    $data['app_id'] = $result['app_id'];

    $result = $this->testCreditPull($cbc_app, $data);
    $data['credit_guid'] = $result['credit_guid'];

    $this->testCreditRecall($cbc_app, $data);
  }

  /**
   * Tests password change function for the cbc api.
   */
  protected function testPwdChange($cbc_app, $which_user) {
    $result_pwd = $cbc_app->taskPwdUpdate($which_user);
    $user_type = '';
    if ($which_user == 0) {
      $user_type = 'master';
    }
    elseif ($which_user == 2) {
      $user_type = 'tester';
    }
    if ($result_pwd == 2) {
      $msg = 'Password already up to date.';
    }
    elseif ($result_pwd == 1) {
      $msg = 'Successfuly changed password.';
    }
    else {
      $msg = 'Failed. No api response or error.';
    }
    $this->output()->writeln(
      'PWD change request: ' . time() . ' | ' . 'user type: ' . $user_type . ' | STATUS ' . $result_pwd . ' (' . $msg . ')');
  }

  /**
   * Tests user creation function for the cbc api.
   */
  protected function testUserAdd($cbc_app, $ip) {
    $result = $cbc_app->addUserRequest('amf_test_user1', 'AMF Test User', $ip, '', TRUE);
    if ($result == 2) {
      $msg = 'User already exists.';
    }
    elseif ($result == 1) {
      $msg = 'Successfuly created user.';
    }
    else {
      $msg = 'Failed. No api response or error.';
    }
    $this->output()->writeln(
      'User add request: ' . time() . ' | STATUS ' . $result . ' (' . $msg . ')');
  }

  /**
   * Tests daya push function for the cbc api.
   */
  protected function testPushDataToApi($cbc_app, $data) {
    $credit_credentials = $cbc_app->pushDataToApi($data, TRUE);
    if (!empty($credit_credentials)) {
      $result = 1;
    }
    else {
      $result = 0;
    }
    if ($result == 1) {
      $msg = 'Successfuly pushed data.';
    }
    else {
      $msg = 'Failed. No api response or error.';
    }
    $this->output()->writeln(
      'Data push request: ' . time() . ' | STATUS ' . $result . ' (' . $msg . ')');
    return $credit_credentials;
  }

  /**
   * Tests credit pull function for the cbc api.
   */
  protected function testCreditPull($cbc_app, $data) {
    $credit_credentials = $cbc_app->pullCreditFromApi($data, TRUE);
    if (!empty($credit_credentials)) {
      $result = 1;
    }
    else {
      $result = 0;
    }
    if ($result == 1) {
      $msg = 'Successfuly pulled credit.';
    }
    else {
      $msg = 'Failed. No api response or error.';
    }
    $this->output()->writeln(
      'Credit pull request: ' . time() . ' | STATUS ' . $result . ' (' . $msg . ')');
    return $credit_credentials;
  }

  /**
   * Tests credit recall function for the cbc api.
   */
  protected function testCreditRecall($cbc_app, $data) {
    $credit_credentials = $cbc_app->recallCreditFromApi($data, TRUE);
    if (!empty($credit_credentials)) {
      $result = 1;
    }
    else {
      $result = 0;
    }
    if ($result == 1) {
      $msg = 'Successfuly recalled credit.';
    }
    else {
      $msg = 'Failed. No api response or error.';
    }
    $this->output()->writeln(
      'Credit recall request: ' . time() . ' | STATUS ' . $result . ' (' . $msg . ')');
    return $credit_credentials;
  }

  /**
   * Tests user creation functions for the cbc api.
   */
  protected function getTestFormData(): array {
    $data = [];
    $data['fname'] = 'Test';
    $data['lname'] = 'Tester';
    $data['add_str'] = 'Test str n1';
    $data['add_city'] = 'West Tester';
    $data['add_pcode'] = '33601';
    $data['ssn'] = '000-00-0000 ';
    $data['add_state'] = 'TS';
    $data['phone_no'] = '0891434489';
    $data['email'] = 'test.amf.cbc@gmail.com';
    return $data;
  }

}
