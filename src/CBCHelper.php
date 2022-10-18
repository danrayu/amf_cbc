<?php

namespace Drupal\amf_cbc;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\butils\BUtils;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

/**
 * Service for sending posts to API.
 */
class CBCHelper {

  const UPDATE_MASTER = 0;
  const UPDATE_REGULAR = 1;
  const UPDATE_TESTER = 2;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Drupal\butils\BUtils definition.
   *
   * @var \Drupal\butils\BUtils
   */
  protected $butils;

  /**
   * Service constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   Logger service.
   * @param \Drupal\butils\BUtils $butils
   *   Logger service.
   */
  public function __construct(LoggerChannelInterface $logger, BUtils $butils) {
    $this->logger = $logger;
    $this->butils = $butils;
  }

  /**
   * Send request to change master password in the CBC API.
   *
   * @param int $user_to_update
   *   User type as int.
   *
   * @throws \Exception
   */
  public function taskPwdUpdate(int $user_to_update = 1): int {
    $config = \Drupal::service('config.factory')->getEditable('amf_cbc.settings');
    // Get uri link.
    $api_uri = $config->get('api-uri');
    $data = [];
    $year_today = date('Y');
    $month_today = date('Y-m');
    // Whether the function result is successful.
    $status = 2;

    // Set settings for which user to update.
    if ($user_to_update == 0) {
      $data['master_change_pwd'] = 1;
      $pwd_date_config = 'm-pwd-change-date';
      $pwd_name_config = 'm-user-pwd';
    }
    elseif ($user_to_update == 1) {
      $pwd_date_config = 'pwd-change-date';
      $pwd_name_config = 'user-pwd';
    }
    else {
      $pwd_date_config = 'pwd-change-date-tester';
      $pwd_name_config = 'tester-pwd';
    }

    // Generate new password.
    $pwd_new = $year_today . "va3t4no" . bin2hex(md5(($month_today . "avl")));
    // Get old password.
    $pwd_old = $config->get($pwd_name_config);

    // If pwds don't match, a new month has come, so time to update the pwd.
    if ($pwd_new != $pwd_old) {
      $status = 0;
      // Merge data for xml header with 'master_change_pwd' status.
      $data = array_merge($this->headerData(), $data);
      // Encode new pwd.
      $data['new_pwd'] = bin2hex(md5($pwd_new));

      // Render the xml request.
      $xmlstring = $this->renderXmlRequest($data, 'amf_msr_pwd_change_request');
      // Send the requet to the API and recieve a response.
      $response = $this->postToApi($xmlstring, $api_uri);

      // Check if there is a response.
      if (!empty($response['PWD_STATUS']) && !empty($response['STATUS'] == 1)) {
        $config->set($pwd_name_config, $pwd_new)->save();
        $config->set($pwd_date_config, $month_today)->save();
        $status = 1;
      }
      else {
        $this->logger->error('CBC Error: ' . ($response['ERROR_DESCRIPT'] ?? 'Request failed without error.'));
      }
    }
    return $status;
  }

  /**
   * Request API to add user.
   *
   * @param string $new_user_id
   *   ID for new user. 8-20 characters, alphanumeric.
   * @param string $new_user_name
   *   Name of the user.
   * @param string $new_user_ip
   *   The only ip from which user can request credit.
   * @param string $new_user_pwd
   *   If a password value is not given, a random one will be generated.
   * @param bool $test
   *   Indicates if the function is being tested.
   *
   * @return int
   *   If the request succeeds, return true.
   */
  public function addUserRequest($new_user_id, $new_user_name, $new_user_ip, $new_user_pwd = '', $test = FALSE) : int {
    $config = \Drupal::service('config.factory')->getEditable('amf_cbc.settings');
    $api_uri = $config->get('api-uri');

    // Check for existing user to avoid overwriting.
    if ($test) {
      if ($config->get('tester-id') !== NULL) {
        return 2;
      }
    }
    else {
      if ($config->get('user-id') !== NULL) {
        return 2;
      }
    }

    // Get data for xml header.
    $data = $this->headerData();

    // If no new pwd is given as argument, generate one.
    if ($new_user_pwd == '') {
      $new_user_pwd = "idm_cbc" . date('Y-m');
    }

    $data['user_pwd'] = bin2hex(md5($new_user_pwd));
    $data['user_ip'] = $new_user_ip;
    $data['user_name'] = $new_user_name;
    $data['uid'] = $new_user_id;

    // Render the xml request.
    $xmlstring = $this->renderXmlRequest($data, 'amf_add_user_request');
    // Send the requet to the API and recieve a response.
    $response = $this->postToApi($xmlstring, $api_uri);

    if (!empty($response['STATUS']) ||
      $response['ERROR_DESCRIPT'] == 'This user: auto_market_f1  already exists, unable to proceed.') {
      if ($test) {
        $config->set('tester-name', $new_user_name)->save();
        $config->set('tester-pwd', $new_user_pwd)->save();
        $config->set('tester-id', $new_user_id)->save();
        $config->set('tester-ip', $new_user_ip)->save();
      }
      else {
        $config->set('user-pwd', $new_user_pwd)->save();
        $config->set('user-ip', $new_user_ip)->save();
        $config->set('user-id', $new_user_id)->save();
        $config->set('user-name', $new_user_name)->save();
        $config->set('pwd-change-date', date('Y-m-d'))->save();
      }
      $success = 1;
    }
    else {
      $this->logger->error('CBC Error: ' . ($response['ERROR_DESCRIPT'] ?? 'Request failed without error.'));
      $success = 0;
    }

    return $success;
  }

  /**
   * Attempts to pull credit from api.
   *
   * @return array
   *   If the request succeeds, return credit app_id and guid.
   */
  public function pullCreditFromApi(array $data, $test = FALSE) : array {
    return $this->dataRichRequestTemplate('amf_pull_credit_request', $data, $test);
  }

  /**
   * Attempts to recall credit from api.
   *
   * @return array
   *   If the request succeeds, return credit app_id and guid.
   */
  public function recallCreditFromApi(array $data, $test = FALSE) : array {
    return $this->dataRichRequestTemplate('amf_credit_recall_request', $data, $test);
  }

  /**
   * Attempts to push client data to api.
   *
   * @return array
   *   If the request succeeds, return credit app_id and guid.
   */
  public function pushDataToApi(array $data, $test = FALSE): array {
    return $this->dataRichRequestTemplate('amf_data_push_request', $data, $test);
  }

  /**
   * API request template for data push and credit pull requests.
   *
   * @return array
   *   If the request succeeds, return credit app_id and guid.
   */
  public function dataRichRequestTemplate(string $template_name, array $data, $test = FALSE) : array {
    $config = \Drupal::service('config.factory')->getEditable('amf_cbc.settings');
    $api_uri = $config->get('api-uri');

    // Merge user and header data with data from the financial form.
    $data = array_merge($this->getProfileData($this->butils, $test), $data);

    // Prepare salary data.
    if (!empty($data['submission_data']['weekly_salary'])) {
      $data['monthly_salary'] = (int) $data['submission_data']['weekly_salary'] * 4;
    }
    elseif (!empty($data['submission_data']['biweekly_salary'])) {
      $data['monthly_salary'] = (int) $data['submission_data']['biweekly_salary'] * 2;
    }

    $xmlstring = $this->renderXmlRequest($data, $template_name);
    $response = $this->postToApi($xmlstring, $api_uri);

    // Check if action successful and save changes.
    $credit_credentials = [];
    if (!empty($response)) {
      if (empty($response['STATUS'])) {
        $this->logger->error('CBC Error: ' . ($response['ERROR_DESCRIPT'] ?? 'Request failed without error.'));
      }
      else {
        $credit_credentials['app_id'] = $response['APP_ID'];
        if ($template_name != 'amf_data_push_request') {
          $credit_credentials['credit_guid'] = $response['CREDITREPORT']['BUREAU_TYPE']['CREDIT_GUID'];
        }
      }
    }
    else {
      $this->logger->error('CBC Error: ' . 'No CBC response recieved.');
    }

    return $credit_credentials;
  }

  /**
   * Calls all the api requests.
   *
   * @return bool
   *   Returns true if data push to API worked.
   */
  public function executeRequests(array $data): bool {
    // Check if master password needs to be updated.
    $this->taskPwdUpdate(CBCHelper::UPDATE_MASTER);

    // Check if user exists.
    $this->addUserRequest('auto_market_f1', 'AMF API User', $_SERVER['SERVER_ADDR']);

    // Check if regular user password needs to be updated.
    $this->taskPwdUpdate();

    // Push data to API.
    $result = $this->pushDataToApi($data);
    if (!empty($result)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Returns array with appended header data for API request.
   *
   * @return array
   *   Same array with added header data.
   */
  protected function headerData(bool $test = FALSE): array {
    $data = [];
    $config = \Drupal::config('amf_cbc.settings');

    if ($test) {
      $pwd = $config->get('tester-pwd');
      $user_id = $config->get('tester-id');
    }
    else {
      $pwd = $config->get('user-pwd');
      $user_id = $config->get('user-id');
    }
    $cus_id = $config->get('cus-id');
    $m_pwd = $config->get('m-user-pwd');
    $m_user_id = $config->get('m-user-id');

    $data['master_uid'] = $m_user_id;
    $data['master_pwd'] = bin2hex(md5($m_pwd));
    $data['user_id'] = $user_id;
    $data['user_pwd'] = isset($pwd) ? bin2hex(md5($pwd)) : $pwd;
    $data['cus_id'] = $cus_id;
    return $data;
  }

  /**
   * Posts requet to API and returns the response as an array.
   *
   * @param string $xml
   *   XML string containing the request.
   * @param string $api_uri
   *   Uri of target api.
   *
   * @return array
   *   Array version of response.
   */
  protected function postToApi(string $xml, string $api_uri): array {
    $client = \Drupal::httpClient();
    // Make a xml post request to API.
    $request = $client->request('POST', $api_uri, ['body' => $xml]);
    // Get response from API.
    $api_response = $request->getBody();
    // Return response as array.
    $encoder = new XmlEncoder();
    return $encoder->decode($api_response, 'xml');
  }

  /**
   * Renders template into xml request.
   *
   * @param array $data
   *   Variables for constructing twig tempaltes of xml requests.
   * @param string $render_theme
   *   Theme for rendering the array.
   */
  protected function renderXmlRequest(array $data, string $render_theme) {
    $build = [
      '#theme' => $render_theme,
      '#data' => $data,
    ];
    $xml = \Drupal::service('renderer')->renderPlain($build);
    return \Drupal::service('butils')->cleanHtml($xml);
  }

  /**
   * Gets the data coming from the profile form.
   *
   * @param \Drupal\butils\BUtils $butils
   *   BUtils instance.
   * @param bool $test
   *   BUtils instance.
   *
   * @return array
   *   Array of data for template.
   */
  protected function getProfileData(BUtils $butils, bool $test = FALSE) : array {
    // Get data for xml header from config.
    $data = $this->headerData($test);

    // Get user data.
    $user_data = $butils->getProfile(\Drupal::currentUser()->id(), 'customer');
    $data['fname'] = $user_data->address->given_name ?? '';
    $data['lname'] = $user_data->address->family_name ?? '';
    $data['add_str'] = $user_data->address->address_line1 ?? '';
    $data['add_city'] = $user_data->address->locality ?? '';
    $data['add_pcode'] = $user_data->address->postal_code ?? '';
    $data['add_state'] = $user_data->address->country_code ?? '';
    $data['phone_no'] = $user_data->field_phone->value ?? '';
    $data['email'] = \Drupal::currentUser()->getEmail();
    return $data;
  }

}
